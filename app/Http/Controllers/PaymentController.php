<?php

namespace App\Http\Controllers;

use App\Support\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function index()
    {
        [$part1s, $scopeLabel] = $this->resolveScopedPart1s();
        $members = $part1s->isEmpty()
            ? collect()
            : DB::table('part2s')
                ->whereIn('part1_id', $part1s->keys())
                ->orderByDesc('created_at')
                ->get();

        // Generate schedules if missing
        foreach ($part1s as $part1) {
            $this->ensurePaymentSchedule($part1, $members->firstWhere('part1_id', $part1->id));
        }

        $pending = DB::table('payments')
            ->whereIn('part1_id', $part1s->keys())
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->get()
            ->groupBy('part1_id');

        $histories = DB::table('payments')
            ->whereIn('part1_id', $part1s->keys())
            ->orderByDesc('due_date')
            ->get()
            ->groupBy('part1_id');

        return View::make('payment', [
            'members' => $members,
            'part1s' => $part1s,
            'pendingPayments' => $pending,
            'paymentHistories' => $histories,
            'scopeLabel' => $scopeLabel,
        ]);
    }

    public function pay(Request $request, int $paymentId)
    {
        $request->validate([
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $payment = DB::table('payments')->where('id', $paymentId)->first();
        abort_unless($payment, 404);

        $part1 = DB::table('part1s')->where('id', $payment->part1_id)->first();
        abort_unless($part1, 404);
        abort_unless($this->canAccessPart1((int) $part1->id), 403);

        $partners = DB::table('insurance_partners')
            ->where('active', true)
            ->orderBy('sort_order')
            ->limit(2)
            ->get();

        $gross = (float) $payment->amount;
        $deductions = [];
        $insuranceTotal = 0.0;

        foreach ($partners as $partner) {
            $amount = $partner->amount !== null ? (float) $partner->amount : 0.0;
            if ($amount <= 0) continue;
            $insuranceTotal += $amount;
            $deductions[] = [
                'id' => $partner->id,
                'name' => $partner->name,
                'amount' => $amount,
            ];
        }

        $insuranceTotal = round($insuranceTotal, 2);

        $mode = strtolower(trim((string) ($part1->mode_of_payment ?? 'monthly')));
        $modeKey = match ($mode) {
            'monthly', '' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semi-annual', 'semi annual' => 'Semi-Annual',
            'annual', 'yearly' => 'Annual',
            'one-time', 'one time', 'one_time' => null,
            default => 'Monthly',
        };

        $sequence = DB::table('payments')
            ->where('part1_id', $payment->part1_id)
            ->orderBy('due_date')
            ->orderBy('id')
            ->pluck('id');
        $position = $sequence->search($paymentId);
        $paymentNumber = $position === false ? 1 : ((int) $position + 1);

        $tier = match ($modeKey) {
            'Quarterly' => $paymentNumber === 1 ? 'first_quarter' : ($paymentNumber <= 4 ? 'quarters_2_4' : 'quarters_5_20'),
            'Semi-Annual' => $paymentNumber === 1 ? 'first_semi' : ($paymentNumber === 2 ? 'semis_2_2' : 'semis_3_10'),
            'Annual' => $paymentNumber === 1 ? 'first_year' : 'years_2_5',
            default => $paymentNumber === 1 ? 'first_month' : ($paymentNumber <= 12 ? 'months_2_12' : 'months_13_60'),
        };

        $roleDeductionTotal = 0.0;
        $roles = ['collector', 'agent', 'manager', 'others'];
        $percentByRole = [];
        if ($modeKey) {
            $percentByRole = DB::table('percentage_settings')
                ->where('mode', $modeKey)
                ->where('tier', $tier)
                ->whereIn('role', $roles)
                ->pluck('percent', 'role')
                ->toArray();
        }

        foreach ($roles as $role) {
            $percent = isset($percentByRole[$role]) ? (float) $percentByRole[$role] : 0.0;
            if ($percent <= 0) {
                continue;
            }

            $roleAmount = round($gross * ($percent / 100), 2);
            if ($roleAmount <= 0) {
                continue;
            }

            $roleDeductionTotal += $roleAmount;
            $deductions[] = [
                'id' => null,
                'name' => ucfirst($role) . ' Percentage',
                'amount' => $roleAmount,
                'meta' => [
                    'percent' => $percent,
                    'tier' => $tier,
                    'mode' => $modeKey,
                    'role' => $role,
                ],
            ];
        }

        $totalDeductions = round($insuranceTotal + $roleDeductionTotal, 2);
        $totalDeductions = min($gross, $totalDeductions);
        $netAmount = max(0, round($gross - $totalDeductions, 2));

        DB::table('payments')->where('id', $paymentId)->update([
            'status' => 'paid',
            'paid_at' => now(),
            'reference' => $request->input('reference'),
            'notes' => $request->input('notes'),
            'insurance_total' => $totalDeductions > 0 ? $totalDeductions : null,
            'net_amount' => $totalDeductions > 0 ? $netAmount : null,
            'insurance_breakdown' => ! empty($deductions) ? json_encode($deductions) : null,
            'updated_at' => now(),
        ]);

        // Update part1 payment_status for quick badge
        DB::table('part1s')->where('id', $payment->part1_id)->update([
            'payment_status' => 'paid',
            'updated_at' => now(),
        ]);

        $member = DB::table('part2s')->where('part1_id', $part1->id)->first();
        $this->ensureLegacyRecurringPayment($part1, $member, $payment->due_date ?? null);
        AuditLogger::log('payment.mark_paid', 'payment', $paymentId, [
            'part1_id' => (int) $payment->part1_id,
            'gross_amount' => $gross,
            'deductions' => $totalDeductions,
            'net_amount' => $netAmount,
            'reference' => $request->input('reference'),
        ]);

        return Redirect::route('payment')->with('status', 'Payment recorded.');
    }

    private function resolveScopedPart1s(): array
    {
        $role = strtolower((string) (auth()->user()->role ?? ''));
        $userId = (int) auth()->id();
        $query = DB::table('part1s');
        $scopeLabel = 'All records';

        if (in_array($role, ['collector', 'agent', 'manager'], true)) {
            $column = $role . '_user_id';
            $assignmentIds = DB::table('member_assignments')
                ->where($column, $userId)
                ->pluck('id');
            if ($assignmentIds->isEmpty()) {
                return [collect(), 'My role records'];
            }
            $query->whereIn('member_assignment_id', $assignmentIds);
            $scopeLabel = 'My role records';
        } elseif ($role === 'encoder') {
            $query->where('created_by_user_id', $userId);
            $scopeLabel = 'My encoded records';
        }

        return [$query->orderByDesc('created_at')->get()->keyBy('id'), $scopeLabel];
    }

    private function canAccessPart1(int $part1Id): bool
    {
        $role = strtolower((string) (auth()->user()->role ?? ''));
        $userId = (int) auth()->id();

        if ($role === 'encoder') {
            return DB::table('part1s')
                ->where('id', $part1Id)
                ->where('created_by_user_id', $userId)
                ->exists();
        }

        if (in_array($role, ['collector', 'agent', 'manager'], true)) {
            $column = $role . '_user_id';
            return DB::table('part1s')
                ->join('member_assignments', 'member_assignments.id', '=', 'part1s.member_assignment_id')
                ->where('part1s.id', $part1Id)
                ->where("member_assignments.$column", $userId)
                ->exists();
        }

        return true;
    }

    private function ensurePaymentSchedule($part1, $member): void
    {
        if (!$part1) return;
        $existing = DB::table('payments')->where('part1_id', $part1->id)->count();
        if ($existing > 0) {
            $this->ensureLegacyRecurringPayment($part1, $member);
            return;
        }

        $plan = strtolower(trim($part1->plan_type ?? ''));
        $mode = strtolower(trim($part1->mode_of_payment ?? 'monthly'));
        $start = Carbon::parse($part1->due_date ?? $part1->application_date ?? Carbon::today());
        $rows = [];

        // Determine contract total, recurring amount, interval, and total months
        $defaults = $this->loadPlanDefaults();

        $meta = $defaults[$plan] ?? ['contract' => ($part1->gross_contact_price ?? 0), 'premium' => ($part1->amount ?? 0), 'months' => 12, 'legacy_monthly' => 0];
        $contract = $part1->gross_contact_price ?? $meta['contract'] ?? 0;
        $premium = $part1->amount ?? $meta['premium'] ?? 0;
        $totalMonths = $this->parseMonths($part1->terms_of_payment, $meta['months']);

        if ($plan === 'legacy care') {
            $rows[] = [
                'part1_id' => $part1->id,
                'part2_id' => $member?->id,
                'due_date' => $start->toDateString(),
                'amount' => $contract,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $monthlyAmount = max(0, (float) ($meta['legacy_monthly'] ?? 0));
            if ($monthlyAmount > 0) {
                $rows[] = [
                    'part1_id' => $part1->id,
                    'part2_id' => $member?->id,
                    'due_date' => (clone $start)->addMonth()->toDateString(),
                    'amount' => $monthlyAmount,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('payments')->insert($rows);
            return;
        }

        $intervalMonths = match ($mode) {
            'quarterly' => 3,
            'semi-annual', 'semi annual' => 6,
            'annual', 'yearly' => 12,
            'one-time', 'one time', 'one_time' => $totalMonths,
            default => 1, // monthly
        };

        $periods = max(1, (int) ceil($totalMonths / $intervalMonths));
        $amountPerPeriod = $this->computePeriodAmount($premium, $contract, $intervalMonths, $mode);

        for ($i = 0; $i < $periods; $i++) {
            $due = (clone $start)->addMonths($i * $intervalMonths);
            $rows[] = [
                'part1_id' => $part1->id,
                'part2_id' => $member?->id,
                'due_date' => $due->toDateString(),
                'amount' => $amountPerPeriod,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            DB::table('payments')->insert($rows);
        }
    }

    private function ensureLegacyRecurringPayment($part1, $member, ?string $anchorDate = null): void
    {
        $plan = strtolower(trim((string) ($part1->plan_type ?? '')));
        if ($plan !== 'legacy care') {
            return;
        }

        $defaults = $this->loadPlanDefaults();
        $meta = $defaults[$plan] ?? ['legacy_monthly' => 0];
        $monthlyAmount = max(0, (float) ($meta['legacy_monthly'] ?? 0));
        if ($monthlyAmount <= 0) {
            return;
        }

        $start = Carbon::parse($part1->due_date ?? $part1->application_date ?? Carbon::today())->toDateString();
        $hasPendingRecurring = DB::table('payments')
            ->where('part1_id', $part1->id)
            ->whereDate('due_date', '>', $start)
            ->whereIn('status', ['pending', 'overdue'])
            ->exists();

        if ($hasPendingRecurring) {
            return;
        }

        $lastDue = DB::table('payments')
            ->where('part1_id', $part1->id)
            ->max('due_date');

        $anchor = $anchorDate ? Carbon::parse($anchorDate) : ($lastDue ? Carbon::parse($lastDue) : Carbon::parse($start));
        if ($anchor->toDateString() <= $start) {
            $anchor = Carbon::parse($start);
        }

        DB::table('payments')->insert([
            'part1_id' => $part1->id,
            'part2_id' => $member?->id,
            'due_date' => (clone $anchor)->addMonth()->toDateString(),
            'amount' => $monthlyAmount,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function parseMonths(?string $terms, int $fallback): int
    {
        if (!$terms) return $fallback;
        if (preg_match('/(\\d+)\\s*month/i', $terms, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(\\d+)\\s*year/i', $terms, $m)) {
            return (int) $m[1] * 12;
        }
        return $fallback;
    }

    private function computePeriodAmount(float $premium, float $contract, int $intervalMonths, string $mode): float
    {
        $m = strtolower($mode);
        $base = $premium > 0 ? $premium : $contract;
        return match ($m) {
            'quarterly' => $base * 3,
            'semi-annual', 'semi annual' => $base * 6,
            'annual', 'yearly' => $base * 12,
            'one-time', 'one time', 'one_time' => $contract > 0 ? $contract : $base,
            default => $base,
        };
    }

    private function loadPlanDefaults(): array
    {
        $rows = DB::table('plan_settings')->orderBy('id')->get();
        if ($rows->isEmpty()) {
            return [
                'serenity care' => ['contract' => 30000, 'premium' => 500, 'months' => 60, 'legacy_monthly' => 0],
                'everlasting care' => ['contract' => 20000, 'premium' => 350, 'months' => 60, 'legacy_monthly' => 0],
                'legacy care' => ['contract' => 30000, 'premium' => 0, 'months' => 1, 'legacy_monthly' => 0],
            ];
        }

        $defaults = [];
        foreach ($rows as $row) {
            $defaults[strtolower($row->name)] = [
                'contract' => (int) $row->contract_amount,
                'premium' => (int) $row->premium_amount,
                'months' => (int) $row->default_months,
                'legacy_monthly' => (int) ($row->legacy_monthly_amount ?? 0),
            ];
        }

        return $defaults;
    }
}
