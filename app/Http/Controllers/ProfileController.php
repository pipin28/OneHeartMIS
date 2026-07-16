<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        abort_unless($user, 401);

        $role = strtolower((string) ($user->role ?? ''));
        $summary = [
            'assigned_members' => null,
            'percentage_this_month' => null,
            'percentage_last_month' => null,
            'percentage_monthly_history' => [],
        ];

        $roleColumn = match ($role) {
            'agent' => 'agent_user_id',
            'manager' => 'manager_user_id',
            default => null,
        };

        if ($roleColumn) {
            $assignmentIds = DB::table('member_assignments')
                ->where($roleColumn, $user->id)
                ->pluck('id');

            $part1Ids = $assignmentIds->isEmpty()
                ? collect()
                : DB::table('part1s')
                    ->whereIn('member_assignment_id', $assignmentIds)
                    ->pluck('id');

            $assignedMembers = $part1Ids->isEmpty()
                ? 0
                : DB::table('part2s')->whereIn('part1_id', $part1Ids)->count();

            $now = now();
            $thisMonthStart = (clone $now)->startOfMonth();
            $thisMonthEnd = (clone $now)->endOfMonth();
            $lastMonthStart = (clone $now)->subMonthNoOverflow()->startOfMonth();
            $lastMonthEnd = (clone $now)->subMonthNoOverflow()->endOfMonth();

            $summary['assigned_members'] = $assignedMembers;
            $summary['percentage_this_month'] = $this->sumRolePercentageDeductions(
                $part1Ids->all(),
                $role,
                $thisMonthStart,
                $thisMonthEnd
            );
            $summary['percentage_last_month'] = $this->sumRolePercentageDeductions(
                $part1Ids->all(),
                $role,
                $lastMonthStart,
                $lastMonthEnd
            );
            $summary['percentage_monthly_history'] = $this->monthlyRolePercentageHistory(
                $part1Ids->all(),
                $role
            );
        }

        return View::make('profile', [
            'user' => $user,
            'summary' => $summary,
        ]);
    }

    private function sumRolePercentageDeductions(array $part1Ids, string $role, $from = null, $to = null): float
    {
        if (empty($part1Ids)) {
            return 0.0;
        }

        $paymentsQuery = DB::table('payments')
            ->whereIn('part1_id', $part1Ids)
            ->where('status', 'paid')
            ->whereNotNull('insurance_breakdown');

        if ($from && $to) {
            $paymentsQuery
                ->whereNotNull('paid_at')
                ->whereBetween('paid_at', [$from, $to]);
        }

        $payments = $paymentsQuery->get();

        $total = 0.0;
        $roleLabel = ucfirst($role);

        foreach ($payments as $payment) {
            $rows = json_decode($payment->insurance_breakdown, true);
            if (! is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                $meta = $row['meta'] ?? [];
                $rowRole = $meta['role'] ?? null;
                $hasPercent = isset($meta['percent']) && (float) $meta['percent'] > 0;
                if (! $hasPercent) {
                    continue;
                }
                if ($rowRole !== null && $rowRole !== $role) {
                    continue;
                }
                if ($rowRole === null && stripos((string) ($row['name'] ?? ''), $roleLabel) === false) {
                    continue;
                }
                $total += (float) ($row['amount'] ?? 0);
            }
        }

        return round($total, 2);
    }

    private function monthlyRolePercentageHistory(array $part1Ids, string $role): array
    {
        if (empty($part1Ids)) {
            return [];
        }

        $payments = DB::table('payments')
            ->whereIn('part1_id', $part1Ids)
            ->where('status', 'paid')
            ->whereNotNull('paid_at')
            ->whereNotNull('insurance_breakdown')
            ->orderBy('paid_at', 'desc')
            ->get();

        $roleLabel = ucfirst($role);
        $monthly = [];

        foreach ($payments as $payment) {
            $rows = json_decode($payment->insurance_breakdown, true);
            if (! is_array($rows)) {
                continue;
            }

            $monthKey = Carbon::parse($payment->paid_at)->format('Y-m');
            if (! isset($monthly[$monthKey])) {
                $monthly[$monthKey] = 0.0;
            }

            foreach ($rows as $row) {
                $meta = $row['meta'] ?? [];
                $rowRole = $meta['role'] ?? null;
                $hasPercent = isset($meta['percent']) && (float) $meta['percent'] > 0;
                if (! $hasPercent) {
                    continue;
                }
                if ($rowRole !== null && $rowRole !== $role) {
                    continue;
                }
                if ($rowRole === null && stripos((string) ($row['name'] ?? ''), $roleLabel) === false) {
                    continue;
                }
                $monthly[$monthKey] += (float) ($row['amount'] ?? 0);
            }
        }

        if (empty($monthly)) {
            return [];
        }

        krsort($monthly);
        $result = [];
        foreach ($monthly as $monthKey => $amount) {
            $result[] = [
                'month_key' => $monthKey,
                'month' => Carbon::createFromFormat('Y-m', $monthKey)->format('F Y'),
                'amount' => round($amount, 2),
            ];
        }

        return $result;
    }
}
