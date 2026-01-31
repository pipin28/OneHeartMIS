<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PaymentController extends Controller
{
    public function index()
    {
        $members = DB::table('part2s')->orderByDesc('created_at')->get();

        $part1s = DB::table('part1s')
            ->whereIn('id', $members->pluck('part1_id'))
            ->get()
            ->keyBy('id');

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

        DB::table('payments')->where('id', $paymentId)->update([
            'status' => 'paid',
            'paid_at' => now(),
            'reference' => $request->input('reference'),
            'notes' => $request->input('notes'),
            'updated_at' => now(),
        ]);

        // Update part1 payment_status for quick badge
        DB::table('part1s')->where('id', $payment->part1_id)->update([
            'payment_status' => 'paid',
            'updated_at' => now(),
        ]);

        return Redirect::route('payment')->with('status', 'Payment recorded.');
    }

    private function ensurePaymentSchedule($part1, $member): void
    {
        if (!$part1) return;
        $existing = DB::table('payments')->where('part1_id', $part1->id)->count();
        if ($existing > 0) return;

        $plan = strtolower(trim($part1->plan_type ?? ''));
        $mode = strtolower(trim($part1->mode_of_payment ?? 'monthly'));
        $start = Carbon::parse($part1->application_date ?? Carbon::today());
        $rows = [];

        // Determine contract total, recurring amount, interval, and total months
        $defaults = $this->loadPlanDefaults();

        $meta = $defaults[$plan] ?? ['contract' => ($part1->gross_contact_price ?? 0), 'premium' => ($part1->amount ?? 0), 'months' => 12];
        $contract = $part1->gross_contact_price ?? $meta['contract'] ?? 0;
        $premium = $part1->amount ?? $meta['premium'] ?? 0;
        $totalMonths = $this->parseMonths($part1->terms_of_payment, $meta['months']);
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
                'part2_id' => $member?->id ?? 0,
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
                'serenity care' => ['contract' => 30000, 'premium' => 500, 'months' => 60],
                'everlasting care' => ['contract' => 20000, 'premium' => 350, 'months' => 60],
                'legacy care' => ['contract' => 30000, 'premium' => 0, 'months' => 1],
            ];
        }

        $defaults = [];
        foreach ($rows as $row) {
            $defaults[strtolower($row->name)] = [
                'contract' => (int) $row->contract_amount,
                'premium' => (int) $row->premium_amount,
                'months' => (int) $row->default_months,
            ];
        }

        return $defaults;
    }
}
