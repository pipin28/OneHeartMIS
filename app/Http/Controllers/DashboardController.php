<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        [$part1s, $scopeLabel] = $this->resolveScopedPart1s();
        $part1Ids = $part1s->keys()->all();
        $members = $this->loadMembersByPart1($part1Ids);

        $payments = empty($part1Ids)
            ? collect()
            : DB::table('payments')
                ->whereIn('part1_id', $part1Ids)
                ->orderBy('due_date')
                ->get();

        $today = Carbon::today();
        $startMonth = $today->copy()->startOfMonth();
        $endMonth = $today->copy()->endOfMonth();

        $paidMonth = $payments->filter(function ($row) use ($startMonth, $endMonth) {
            if (strtolower((string) $row->status) !== 'paid' || !$row->paid_at) {
                return false;
            }
            $paid = Carbon::parse($row->paid_at);
            return $paid->between($startMonth, $endMonth);
        });

        $dueMonth = $payments->filter(function ($row) use ($startMonth, $endMonth) {
            $due = Carbon::parse($row->due_date);
            return $due->between($startMonth, $endMonth);
        });

        $paidDueMonth = $dueMonth->filter(fn($row) => strtolower((string) $row->status) === 'paid');
        $overdue = $payments->filter(function ($row) use ($today) {
            return strtolower((string) $row->status) !== 'paid' && Carbon::parse($row->due_date)->lt($today);
        });
        $dueToday = $payments->filter(function ($row) use ($today) {
            return strtolower((string) $row->status) !== 'paid' && Carbon::parse($row->due_date)->isSameDay($today);
        });

        $summary = [
            'members_count' => $part1s->count(),
            'collected_month_net' => round((float) $paidMonth->sum(fn($r) => (float) ($r->net_amount ?? $r->amount)), 2),
            'collected_month_gross' => round((float) $paidMonth->sum(fn($r) => (float) $r->amount), 2),
            'collected_month_count' => $paidMonth->count(),
            'due_today_amount' => round((float) $dueToday->sum(fn($r) => (float) $r->amount), 2),
            'due_today_count' => $dueToday->count(),
            'overdue_amount' => round((float) $overdue->sum(fn($r) => (float) $r->amount), 2),
            'overdue_count' => $overdue->count(),
            'collection_efficiency' => $dueMonth->count() > 0
                ? round(($paidDueMonth->count() / $dueMonth->count()) * 100, 2)
                : 0.0,
        ];

        $collectionTrend = $this->buildCollectionTrend($payments);
        $statusMix = $this->buildStatusMix($payments);
        $planMix = $this->buildPlanMix($part1s);
        $recentPayments = $this->buildRecentPayments($paidMonth, $members, $part1s);
        $upcomingDues = $this->buildUpcomingDues($payments, $members, $part1s);

        return view('dashboard', [
            'scopeLabel' => $scopeLabel,
            'summary' => $summary,
            'collectionTrend' => $collectionTrend,
            'statusMix' => $statusMix,
            'planMix' => $planMix,
            'recentPayments' => $recentPayments,
            'upcomingDues' => $upcomingDues,
            'lastUpdated' => now()->format('M d, Y h:i A'),
        ]);
    }

    private function resolveScopedPart1s(): array
    {
        $role = strtolower((string) (auth()->user()->role ?? ''));
        $userId = (int) auth()->id();
        $query = DB::table('part1s');
        $scopeLabel = 'All records';

        if (in_array($role, ['agent', 'manager'], true)) {
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

    private function loadMembersByPart1(array $part1Ids): array
    {
        if (empty($part1Ids)) {
            return [];
        }

        return DB::table('part2s')
            ->whereIn('part1_id', $part1Ids)
            ->orderByDesc('id')
            ->get()
            ->unique('part1_id')
            ->mapWithKeys(function ($member) {
                $name = trim(($member->first_name ?? '') . ' ' . ($member->midle_name ?? '') . ' ' . ($member->surname ?? ''));
                return [(int) $member->part1_id => $name];
            })
            ->all();
    }

    private function buildCollectionTrend(Collection $payments): array
    {
        $today = Carbon::today();
        $labels = [];
        $values = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = $today->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();
            $labels[] = $month->format('M Y');
            $values[] = round((float) $payments
                ->filter(function ($row) use ($month, $monthEnd) {
                    if (strtolower((string) $row->status) !== 'paid' || !$row->paid_at) {
                        return false;
                    }
                    $paidAt = Carbon::parse($row->paid_at);
                    return $paidAt->between($month, $monthEnd);
                })
                ->sum(fn($row) => (float) ($row->net_amount ?? $row->amount)), 2);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function buildStatusMix(Collection $payments): array
    {
        $statusCounts = ['Paid' => 0, 'Pending' => 0, 'Overdue' => 0];
        $today = Carbon::today();

        foreach ($payments as $row) {
            $status = strtolower((string) $row->status);
            if ($status === 'paid') {
                $statusCounts['Paid']++;
                continue;
            }
            if (Carbon::parse($row->due_date)->lt($today)) {
                $statusCounts['Overdue']++;
                continue;
            }
            $statusCounts['Pending']++;
        }

        return [
            'labels' => array_keys($statusCounts),
            'values' => array_values($statusCounts),
        ];
    }

    private function buildPlanMix(Collection $part1s): array
    {
        $mix = $part1s
            ->map(fn($row) => (string) ($row->plan_type ?: 'Unknown'))
            ->countBy()
            ->sortDesc();

        return [
            'labels' => $mix->keys()->values()->all(),
            'values' => $mix->values()->all(),
        ];
    }

    private function buildRecentPayments(Collection $paidMonth, array $members, Collection $part1s): array
    {
        return $paidMonth
            ->sortByDesc(fn($row) => $row->paid_at ?? $row->updated_at)
            ->take(8)
            ->map(function ($row) use ($members, $part1s) {
                return [
                    'member' => $members[$row->part1_id] ?? ('Member #' . $row->part1_id),
                    'plan' => $part1s->get($row->part1_id)->plan_type ?? '-',
                    'paid_at' => $row->paid_at,
                    'amount' => round((float) ($row->net_amount ?? $row->amount), 2),
                ];
            })
            ->values()
            ->all();
    }

    private function buildUpcomingDues(Collection $payments, array $members, Collection $part1s): array
    {
        $today = Carbon::today();
        $next30 = $today->copy()->addDays(30);

        return $payments
            ->filter(function ($row) use ($today, $next30) {
                if (strtolower((string) $row->status) === 'paid') {
                    return false;
                }
                $due = Carbon::parse($row->due_date);
                return $due->between($today, $next30);
            })
            ->sortBy('due_date')
            ->take(10)
            ->map(function ($row) use ($members, $part1s, $today) {
                $due = Carbon::parse($row->due_date);
                return [
                    'member' => $members[$row->part1_id] ?? ('Member #' . $row->part1_id),
                    'plan' => $part1s->get($row->part1_id)->plan_type ?? '-',
                    'due_date' => $row->due_date,
                    'days_left' => $today->diffInDays($due, false),
                    'amount' => round((float) $row->amount, 2),
                ];
            })
            ->values()
            ->all();
    }
}
