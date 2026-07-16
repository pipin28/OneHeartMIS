<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $reportDate = $this->resolveDailyReportDate($request);
        [$part1s, $scopeLabel] = $this->resolveScopedPart1s($request);
        $part1Ids = $part1s->keys()->all();

        $paidRows = empty($part1Ids)
            ? collect()
            : DB::table('payments')
                ->leftJoin('part1s', 'part1s.id', '=', 'payments.part1_id')
                ->leftJoin('part2s', 'part2s.part1_id', '=', 'payments.part1_id')
                ->leftJoin('member_assignments', 'member_assignments.id', '=', 'part1s.member_assignment_id')
                ->leftJoin('users as added_by_user', 'added_by_user.id', '=', DB::raw('COALESCE(part1s.added_by_user_id, part1s.created_by_user_id)'))
                ->whereIn('payments.part1_id', $part1Ids)
                ->where('payments.status', 'paid')
                ->whereNotNull('payments.paid_at')
                ->whereDate('payments.paid_at', $reportDate->toDateString())
                ->orderBy('payments.paid_at')
                ->orderBy('payments.id')
                ->get([
                    'payments.id',
                    'payments.part1_id',
                    'payments.due_date',
                    'payments.amount',
                    'payments.insurance_total',
                    'payments.net_amount',
                    'payments.paid_at',
                    'payments.reference',
                    'payments.payment_type',
                    'part1s.reference_number',
                    'part1s.plan_type',
                    'part1s.mode_of_payment',
                    'part1s.terms_of_payment',
                    'added_by_user.name as added_by_name',
                    'part2s.surname',
                    'part2s.first_name',
                    'part2s.midle_name',
                    'member_assignments.agent_name',
                    'member_assignments.manager_name',
                ])
                ->unique('id')
                ->values();

        $detailRows = $paidRows->map(function ($row) {
            $gross = (float) $row->amount;
            $deductions = (float) ($row->insurance_total ?? 0);
            $net = $row->net_amount !== null ? (float) $row->net_amount : max(0, $gross - $deductions);
            $name = trim(($row->first_name ?? '') . ' ' . ($row->midle_name ?? '') . ' ' . ($row->surname ?? ''));

            return [
                'time' => $row->paid_at ? Carbon::parse($row->paid_at)->format('h:i A') : '-',
                'paid_at' => $row->paid_at,
                'member' => $name !== '' ? $name : 'Member #' . $row->part1_id,
                'reference_number' => $row->reference_number ?: '-',
                'added_by' => $row->added_by_name ?: '-',
                'payment_reference' => $row->reference ?: '-',
                'payment_type' => $this->formatPaymentTypeLabel($row->payment_type),
                'plan_type' => $row->plan_type ?: '-',
                'mode_of_payment' => $row->mode_of_payment ?: '-',
                'agent' => $row->agent_name ?: '-',
                'manager' => $row->manager_name ?: '-',
                'gross' => round($gross, 2),
                'deductions' => round($deductions, 2),
                'net' => round($net, 2),
            ];
        });

        $typeTotals = $detailRows
            ->groupBy('payment_type')
            ->map(function (Collection $rows, string $type) {
                return [
                    'type' => $type,
                    'count' => $rows->count(),
                    'gross' => round((float) $rows->sum('gross'), 2),
                    'deductions' => round((float) $rows->sum('deductions'), 2),
                    'net' => round((float) $rows->sum('net'), 2),
                ];
            })
            ->sortBy('type')
            ->values();

        $summary = [
            'transactions' => $detailRows->count(),
            'gross' => round((float) $detailRows->sum('gross'), 2),
            'deductions' => round((float) $detailRows->sum('deductions'), 2),
            'net' => round((float) $detailRows->sum('net'), 2),
        ];

        $subscriberModes = $this->buildSubscriberModeCounts($part1s);

        return view('report', [
            'reportDate' => $reportDate->toDateString(),
            'reportDateLabel' => $reportDate->format('F d, Y'),
            'scopeLabel' => $scopeLabel,
            'lastUpdated' => now()->format('M d, Y h:i A'),
            'summary' => $summary,
            'typeTotals' => $typeTotals,
            'detailRows' => $detailRows,
            'subscriberModes' => $subscriberModes,
        ]);
    }

    public function oldIndex(Request $request)
    {
        [$startDate, $endDate, $preset] = $this->resolveDateRange($request);
        [$part1s, $scopeLabel] = $this->resolveScopedPart1s($request);
        $part1Ids = $part1s->keys()->all();
        $asOf = Carbon::today()->lessThanOrEqualTo($endDate) ? Carbon::today() : $endDate->copy();

        $payments = empty($part1Ids)
            ? collect()
            : DB::table('payments')
                ->whereIn('part1_id', $part1Ids)
                ->orderBy('due_date')
                ->get();

        $paymentsInDueRange = $payments->filter(function ($row) use ($startDate, $endDate) {
            return $this->betweenDates($row->due_date, $startDate, $endDate);
        });

        $paidInRange = $payments->filter(function ($row) use ($startDate, $endDate) {
            if (strtolower((string) $row->status) !== 'paid') {
                return false;
            }
            return $this->betweenTimestamps($row->paid_at, $startDate, $endDate);
        });

        $outstandingRows = $payments->filter(function ($row) use ($asOf) {
            $status = strtolower((string) $row->status);
            return $status !== 'paid' && Carbon::parse($row->due_date)->lessThanOrEqualTo($asOf);
        });

        $collectionGross = (float) $paidInRange->sum(fn($row) => (float) $row->amount);
        $collectionDeductions = (float) $paidInRange->sum(fn($row) => (float) ($row->insurance_total ?? 0));
        $collectionNet = (float) $paidInRange->sum(fn($row) => (float) ($row->net_amount ?? $row->amount));

        $totalDueCount = $paymentsInDueRange->count();
        $paidDueCount = $paymentsInDueRange->filter(fn($row) => strtolower((string) $row->status) === 'paid')->count();
        $collectionEfficiency = $totalDueCount > 0 ? round(($paidDueCount / $totalDueCount) * 100, 2) : 0.0;

        $outstandingAmount = (float) $outstandingRows->sum(fn($row) => (float) $row->amount);
        $paidAmountInDueRange = (float) $paymentsInDueRange
            ->filter(fn($row) => strtolower((string) $row->status) === 'paid')
            ->sum(fn($row) => (float) ($row->net_amount ?? $row->amount));
        $unpaidAmountInDueRange = (float) $paymentsInDueRange
            ->filter(fn($row) => strtolower((string) $row->status) !== 'paid')
            ->sum(fn($row) => (float) $row->amount);

        $aging = [
            '1_30' => 0.0,
            '31_60' => 0.0,
            '61_plus' => 0.0,
        ];
        $today = Carbon::today();
        foreach ($outstandingRows as $row) {
            $days = Carbon::parse($row->due_date)->diffInDays($today);
            $amount = (float) $row->amount;
            if ($days <= 30) {
                $aging['1_30'] += $amount;
                continue;
            }
            if ($days <= 60) {
                $aging['31_60'] += $amount;
                continue;
            }
            $aging['61_plus'] += $amount;
        }

        $paymentsByPart1 = $payments->groupBy('part1_id');

        $planBreakdown = $part1s->map(function ($part1) use ($paymentsByPart1, $paidInRange) {
            $plan = (string) ($part1->plan_type ?: 'Unknown');
            $part1Payments = $paymentsByPart1->get($part1->id, collect());
            $outstanding = $part1Payments
                ->filter(fn($p) => strtolower((string) $p->status) !== 'paid')
                ->sum(fn($p) => (float) $p->amount);
            $collected = $paidInRange
                ->where('part1_id', $part1->id)
                ->sum(fn($p) => (float) ($p->net_amount ?? $p->amount));

            return [
                'plan' => $plan,
                'members' => 1,
                'collected' => (float) $collected,
                'outstanding' => (float) $outstanding,
            ];
        })->groupBy('plan')->map(function (Collection $rows, string $plan) {
            return [
                'plan' => $plan,
                'members' => (int) $rows->sum('members'),
                'collected' => (float) $rows->sum('collected'),
                'outstanding' => (float) $rows->sum('outstanding'),
            ];
        })->sortByDesc('collected')->values();

        $modeBreakdown = $part1s->map(function ($part1) {
            $mode = strtolower(trim((string) ($part1->mode_of_payment ?? 'unknown')));
            $mode = match ($mode) {
                'semi annual' => 'semi-annual',
                'yearly' => 'annual',
                'one time', 'one_time' => 'one-time',
                '' => 'unknown',
                default => $mode,
            };
            return $mode;
        })->countBy()->map(function ($count, $mode) {
            return ['mode' => ucwords(str_replace('-', ' ', (string) $mode)), 'count' => (int) $count];
        })->sortByDesc('count')->values();

        $deductionSummary = $this->buildDeductionSummary($paidInRange);
        $trend = $this->buildTrend($paidInRange, $startDate, $endDate);
        $membersByPart1 = $this->loadMembersByPart1($part1Ids);
        $assignmentById = $this->loadAssignmentsByPart1($part1s);
        $staffPerformance = $this->buildStaffPerformance($part1s, $paymentsByPart1, $paidInRange, $asOf);
        $topUnpaidMembers = $this->buildTopUnpaidMembers($part1s, $paymentsByPart1, $membersByPart1);
        $upcomingDue = $this->buildUpcomingDue($part1s, $paymentsByPart1, $membersByPart1);

        return view('report', [
            'startDate' => $startDate->toDateString(),
            'endDate' => $endDate->toDateString(),
            'preset' => $preset,
            'scopeLabel' => $scopeLabel,
            'lastUpdated' => now()->format('M d, Y h:i A'),
            'summary' => [
                'collection_gross' => round($collectionGross, 2),
                'collection_net' => round($collectionNet, 2),
                'collection_deductions' => round($collectionDeductions, 2),
                'outstanding' => round($outstandingAmount, 2),
                'collection_efficiency' => $collectionEfficiency,
                'paid_count' => $paidDueCount,
                'due_count' => $totalDueCount,
                'paid_amount_due_range' => round($paidAmountInDueRange, 2),
                'unpaid_amount_due_range' => round($unpaidAmountInDueRange, 2),
                'members_count' => $part1s->count(),
            ],
            'aging' => array_map(fn($v) => round((float) $v, 2), $aging),
            'planBreakdown' => $planBreakdown,
            'modeBreakdown' => $modeBreakdown,
            'deductionSummary' => $deductionSummary,
            'trend' => $trend,
            'staffPerformance' => $staffPerformance,
            'topUnpaidMembers' => $topUnpaidMembers,
            'upcomingDue' => $upcomingDue,
            'csvUrl' => route('report.export', [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'preset' => $preset,
            ]),
            'memberAssignmentByPart1' => $assignmentById,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        [$part1s] = $this->resolveScopedPart1s($request);
        $part1Ids = $part1s->keys()->all();
        $membersByPart1 = $this->loadMembersByPart1($part1Ids);
        $assignments = $this->loadAssignmentsByPart1($part1s);

        $rows = empty($part1Ids)
            ? collect()
            : DB::table('payments')
                ->whereIn('part1_id', $part1Ids)
                ->whereDate('due_date', '>=', $startDate->toDateString())
                ->whereDate('due_date', '<=', $endDate->toDateString())
                ->orderBy('due_date')
                ->get();

        $filename = 'report-' . $startDate->format('Ymd') . '-' . $endDate->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($rows, $part1s, $membersByPart1, $assignments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Due Date',
                'Paid At',
                'Status',
                'Member',
                'Plan Type',
                'Contribution',
                'Terms',
                'Unit Name',
                'Agent',
                'Unit Manager',
                'Sales Associate',
                'Contact',
                'Amount',
                'Deductions',
                'Net',
                'Reference',
            ]);

            foreach ($rows as $row) {
                $part1 = $part1s->get($row->part1_id);
                $member = $membersByPart1[$row->part1_id] ?? null;
                $assignment = $assignments[$row->part1_id] ?? null;
                fputcsv($handle, [
                    $row->due_date,
                    $row->paid_at,
                    ucfirst(strtolower((string) $row->status)),
                    $member['name'] ?? '-',
                    $part1->plan_type ?? '-',
                    $part1->mode_of_payment ?? '-',
                    $part1->terms_of_payment ?? '-',
                    $assignment->unit_name ?? '-',
                    $assignment->agent_name ?? '-',
                    $assignment->manager_name ?? '-',
                    $assignment->sales_associate ?? '-',
                    $assignment->staff_contact ?? '-',
                    number_format((float) $row->amount, 2, '.', ''),
                    number_format((float) ($row->insurance_total ?? 0), 2, '.', ''),
                    number_format((float) ($row->net_amount ?? $row->amount), 2, '.', ''),
                    $row->reference ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    private function resolveDailyReportDate(Request $request): Carbon
    {
        $date = $request->query('date');

        if ($date) {
            return Carbon::parse($date)->startOfDay();
        }

        return Carbon::today();
    }

    private function formatPaymentTypeLabel(?string $paymentType): string
    {
        return match (strtolower((string) ($paymentType ?: 'regular'))) {
            'registration_renewal' => 'Registration / Renewal',
            'contestability' => 'Contestability Fee',
            default => 'Contribution',
        };
    }

    private function buildSubscriberModeCounts(Collection $part1s): array
    {
        $counts = [
            'monthly' => 0,
            'quarterly' => 0,
            'semi_annual' => 0,
            'annual' => 0,
        ];

        foreach ($part1s as $part1) {
            $mode = strtolower(trim((string) ($part1->mode_of_payment ?? '')));
            $key = match ($mode) {
                'monthly' => 'monthly',
                'quarterly' => 'quarterly',
                'semi-annual', 'semi annual', 'semiannual' => 'semi_annual',
                'annual', 'yearly' => 'annual',
                default => null,
            };

            if ($key) {
                $counts[$key]++;
            }
        }

        return [
            ['label' => 'Monthly', 'count' => $counts['monthly']],
            ['label' => 'Quarterly', 'count' => $counts['quarterly']],
            ['label' => 'Semi-Annual', 'count' => $counts['semi_annual']],
            ['label' => 'Annual', 'count' => $counts['annual']],
        ];
    }

    private function resolveDateRange(Request $request): array
    {
        $today = Carbon::today();
        $preset = strtolower((string) $request->query('preset', 'month'));
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            return [$start, $end, 'custom'];
        }

        return match ($preset) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay(), 'today'],
            'week' => [$today->copy()->startOfWeek(), $today->copy()->endOfWeek(), 'week'],
            default => [$today->copy()->startOfMonth(), $today->copy()->endOfDay(), 'month'],
        };
    }

    private function resolveScopedPart1s(Request $request): array
    {
        $role = strtolower((string) optional(auth()->user())->role);
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

        $part1s = $query->orderByDesc('created_at')->get()->keyBy('id');
        return [$part1s, $scopeLabel];
    }

    private function buildDeductionSummary(Collection $paidInRange): array
    {
        $roles = [
            'agent' => 0.0,
            'manager' => 0.0,
            'others' => 0.0,
        ];

        foreach ($paidInRange as $row) {
            $breakdown = json_decode((string) ($row->insurance_breakdown ?? ''), true);
            if (!is_array($breakdown)) {
                continue;
            }
            foreach ($breakdown as $item) {
                $metaRole = strtolower((string) ($item['meta']['role'] ?? ''));
                if (!array_key_exists($metaRole, $roles)) {
                    continue;
                }
                $roles[$metaRole] += (float) ($item['amount'] ?? 0);
            }
        }

        return [
            'insurance_total' => round((float) $paidInRange->sum(fn($row) => (float) ($row->insurance_total ?? 0)), 2),
            'roles' => array_map(fn($v) => round((float) $v, 2), $roles),
        ];
    }

    private function buildTrend(Collection $paidInRange, Carbon $startDate, Carbon $endDate): array
    {
        $days = $startDate->diffInDays($endDate);
        $byDay = $days <= 62;
        $bucketed = [];

        foreach ($paidInRange as $row) {
            if (!$row->paid_at) {
                continue;
            }
            $date = Carbon::parse($row->paid_at);
            $key = $byDay ? $date->format('Y-m-d') : $date->format('Y-m-01');
            if (!isset($bucketed[$key])) {
                $bucketed[$key] = 0.0;
            }
            $bucketed[$key] += (float) ($row->net_amount ?? $row->amount);
        }

        $series = [];
        if ($byDay) {
            foreach (CarbonPeriod::create($startDate->copy()->startOfDay(), '1 day', $endDate->copy()->startOfDay()) as $day) {
                $key = $day->format('Y-m-d');
                $series[] = ['label' => $day->format('M d'), 'key' => $key, 'value' => round((float) ($bucketed[$key] ?? 0), 2)];
            }
        } else {
            $cursor = $startDate->copy()->startOfMonth();
            $last = $endDate->copy()->startOfMonth();
            while ($cursor->lessThanOrEqualTo($last)) {
                $key = $cursor->format('Y-m-01');
                $series[] = ['label' => $cursor->format('M Y'), 'key' => $key, 'value' => round((float) ($bucketed[$key] ?? 0), 2)];
                $cursor->addMonth();
            }
        }

        $maxValue = 0.0;
        foreach ($series as $point) {
            if ($point['value'] > $maxValue) {
                $maxValue = $point['value'];
            }
        }

        return [
            'bucket' => $byDay ? 'Daily' : 'Monthly',
            'series' => $series,
            'max' => $maxValue,
        ];
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
                return [(int) $member->part1_id => ['id' => $member->id, 'name' => $name]];
            })
            ->all();
    }

    private function loadAssignmentsByPart1(Collection $part1s): array
    {
        $assignmentIds = $part1s->pluck('member_assignment_id')->filter()->unique()->values();
        if ($assignmentIds->isEmpty()) {
            return [];
        }

        $assignmentRows = DB::table('member_assignments')
            ->whereIn('id', $assignmentIds)
            ->get()
            ->keyBy('id');

        $map = [];
        foreach ($part1s as $part1) {
            if (!$part1->member_assignment_id) {
                continue;
            }
            $map[(int) $part1->id] = $assignmentRows->get($part1->member_assignment_id);
        }
        return $map;
    }

    private function buildStaffPerformance(
        Collection $part1s,
        Collection $paymentsByPart1,
        Collection $paidInRange,
        Carbon $asOf
    ): array {
        $roles = [
            'agent' => 'agent_user_id',
            'manager' => 'manager_user_id',
        ];

        $assignmentIds = $part1s->pluck('member_assignment_id')->filter()->unique()->values();
        if ($assignmentIds->isEmpty()) {
            return ['agent' => collect(), 'manager' => collect()];
        }

        $assignments = DB::table('member_assignments')->whereIn('id', $assignmentIds)->get()->keyBy('id');
        $users = DB::table('users')->get()->keyBy('id');

        $result = [];
        foreach ($roles as $role => $column) {
            $rows = [];
            foreach ($part1s as $part1) {
                $assignment = $assignments->get($part1->member_assignment_id);
                if (!$assignment || !$assignment->{$column}) {
                    continue;
                }

                $staffUserId = (int) $assignment->{$column};
                if (!isset($rows[$staffUserId])) {
                    $rows[$staffUserId] = [
                        'name' => $users->get($staffUserId)->name ?? ucfirst($role) . ' #' . $staffUserId,
                        'members' => 0,
                        'collected' => 0.0,
                        'open' => 0,
                        'overdue' => 0,
                    ];
                }

                $rows[$staffUserId]['members']++;
                $collected = $paidInRange
                    ->where('part1_id', $part1->id)
                    ->sum(fn($p) => (float) ($p->net_amount ?? $p->amount));
                $rows[$staffUserId]['collected'] += (float) $collected;

                $openRows = $paymentsByPart1->get($part1->id, collect())->filter(function ($p) use ($asOf) {
                    return strtolower((string) $p->status) !== 'paid'
                        && Carbon::parse($p->due_date)->lessThanOrEqualTo($asOf);
                });
                $rows[$staffUserId]['open'] += $openRows->count();
                $rows[$staffUserId]['overdue'] += $openRows->filter(fn($p) => Carbon::parse($p->due_date)->lessThan(Carbon::today()))->count();
            }

            $result[$role] = collect($rows)->map(function ($row) {
                $open = (int) $row['open'];
                $overdue = (int) $row['overdue'];
                $row['collected'] = round((float) $row['collected'], 2);
                $row['overdue_rate'] = $open > 0 ? round(($overdue / $open) * 100, 2) : 0.0;
                return $row;
            })->sortByDesc('collected')->values();
        }

        return $result;
    }

    private function buildTopUnpaidMembers(Collection $part1s, Collection $paymentsByPart1, array $membersByPart1): array
    {
        $rows = [];
        foreach ($part1s as $part1) {
            $unpaid = $paymentsByPart1->get($part1->id, collect())
                ->filter(fn($p) => strtolower((string) $p->status) !== 'paid')
                ->sum(fn($p) => (float) $p->amount);

            if ($unpaid <= 0) {
                continue;
            }

            $rows[] = [
                'member' => $membersByPart1[$part1->id]['name'] ?? ('Member #' . $part1->id),
                'plan' => $part1->plan_type ?? '-',
                'unpaid' => round((float) $unpaid, 2),
            ];
        }

        usort($rows, fn($a, $b) => $b['unpaid'] <=> $a['unpaid']);
        return array_slice($rows, 0, 10);
    }

    private function buildUpcomingDue(Collection $part1s, Collection $paymentsByPart1, array $membersByPart1): array
    {
        $today = Carbon::today();
        $next7 = $today->copy()->addDays(7);
        $next30 = $today->copy()->addDays(30);
        $rows = [];

        foreach ($part1s as $part1) {
            foreach ($paymentsByPart1->get($part1->id, collect()) as $payment) {
                if (strtolower((string) $payment->status) === 'paid') {
                    continue;
                }

                $due = Carbon::parse($payment->due_date);
                if ($due->lessThan($today) || $due->greaterThan($next30)) {
                    continue;
                }

                $rows[] = [
                    'member' => $membersByPart1[$part1->id]['name'] ?? ('Member #' . $part1->id),
                    'plan' => $part1->plan_type ?? '-',
                    'due_date' => $payment->due_date,
                    'amount' => round((float) $payment->amount, 2),
                    'window' => $due->lessThanOrEqualTo($next7) ? 'Next 7 days' : 'Next 30 days',
                ];
            }
        }

        usort($rows, fn($a, $b) => strcmp($a['due_date'], $b['due_date']));
        return array_slice($rows, 0, 50);
    }

    private function betweenDates(?string $value, Carbon $startDate, Carbon $endDate): bool
    {
        if (!$value) {
            return false;
        }
        $date = Carbon::parse($value);
        return $date->greaterThanOrEqualTo($startDate->copy()->startOfDay())
            && $date->lessThanOrEqualTo($endDate->copy()->endOfDay());
    }

    private function betweenTimestamps(?string $value, Carbon $startDate, Carbon $endDate): bool
    {
        if (!$value) {
            return false;
        }
        $date = Carbon::parse($value);
        return $date->greaterThanOrEqualTo($startDate->copy()->startOfDay())
            && $date->lessThanOrEqualTo($endDate->copy()->endOfDay());
    }
}
