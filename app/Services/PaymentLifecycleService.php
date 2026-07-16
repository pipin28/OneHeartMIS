<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentLifecycleService
{
    public function sync(iterable $part1Ids): array
    {
        $ids = collect($part1Ids)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $payments = DB::table('payments')
            ->whereIn('part1_id', $ids)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->groupBy('part1_id');
        $manualInactive = DB::table('part1s')
            ->whereIn('id', $ids)
            ->pluck('manual_inactive_at', 'id');
        $contestability = DB::table('part1s')
            ->whereIn('id', $ids)
            ->pluck('contestability_at', 'id');
        $claimed = DB::table('part1s')
            ->whereIn('id', $ids)
            ->pluck('claimed_at', 'id');
        $memberStatuses = DB::table('part1s')
            ->whereIn('id', $ids)
            ->pluck('member_status', 'id');

        $states = [];
        foreach ($ids as $part1Id) {
            $state = $this->resolveState(
                $payments->get($part1Id, collect()),
                ! empty($manualInactive[$part1Id] ?? null)
                    || strtolower((string) ($memberStatuses[$part1Id] ?? '')) === 'inactive',
                ! empty($contestability[$part1Id] ?? null)
            );
            $states[$part1Id] = $state;

            DB::table('part1s')
                ->where('id', $part1Id)
                ->update([
                    'payment_status' => $state['payment_status'],
                    'member_status' => ! empty($claimed[$part1Id] ?? null)
                        ? 'claimed'
                        : ($state['payment_status'] === 'inactive' ? 'inactive' : 'active'),
                    'updated_at' => now(),
                ]);
        }

        return $states;
    }

    public function states(iterable $part1Ids): array
    {
        $ids = collect($part1Ids)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $payments = DB::table('payments')
            ->whereIn('part1_id', $ids)
            ->orderBy('due_date')
            ->orderBy('id')
            ->get()
            ->groupBy('part1_id');
        $manualInactive = DB::table('part1s')
            ->whereIn('id', $ids)
            ->pluck('manual_inactive_at', 'id');
        $contestability = DB::table('part1s')
            ->whereIn('id', $ids)
            ->pluck('contestability_at', 'id');
        $claimed = DB::table('part1s')
            ->whereIn('id', $ids)
            ->pluck('claimed_at', 'id');
        $memberStatuses = DB::table('part1s')
            ->whereIn('id', $ids)
            ->pluck('member_status', 'id');

        return $ids
            ->mapWithKeys(function ($part1Id) use ($payments, $manualInactive, $contestability, $claimed, $memberStatuses) {
                $state = $this->resolveState(
                    $payments->get($part1Id, collect()),
                    ! empty($manualInactive[$part1Id] ?? null)
                        || strtolower((string) ($memberStatuses[$part1Id] ?? '')) === 'inactive',
                    ! empty($contestability[$part1Id] ?? null)
                );
                $state['member_status'] = ! empty($claimed[$part1Id] ?? null)
                    ? 'claimed'
                    : ($state['payment_status'] === 'inactive' ? 'inactive' : 'active');

                return [$part1Id => $state];
            })
            ->all();
    }

    private function resolveState(Collection $payments, bool $manualInactive = false, bool $contestability = false): array
    {
        $today = Carbon::today();
        $paid = $payments->filter(fn($row) => strtolower((string) $row->status) === 'paid');
        $unpaid = $payments->reject(fn($row) => strtolower((string) $row->status) === 'paid');

        $lastPaidAt = $paid
            ->pluck('paid_at')
            ->filter()
            ->map(fn($value) => Carbon::parse($value))
            ->sort()
            ->last();

        $nextUnpaidDue = $unpaid
            ->pluck('due_date')
            ->filter()
            ->map(fn($value) => Carbon::parse($value)->startOfDay())
            ->sort()
            ->first();

        $daysOverdue = $nextUnpaidDue ? $nextUnpaidDue->diffInDays($today, false) : null;
        $daysWithoutPayment = max(0, (int) ($daysOverdue ?? 0));

        $hasSchedule = $payments->isNotEmpty();
        $isFullyPaid = $hasSchedule && $unpaid->isEmpty();
        $hasPaid = $paid->isNotEmpty();
        $isUnpaid = $hasSchedule && ! $hasPaid;

        $rowState = 'normal';
        $paymentStatus = 'pending';

        if ($manualInactive) {
            $rowState = 'inactive';
            $paymentStatus = 'inactive';
        } elseif ($contestability) {
            $paymentStatus = $isFullyPaid ? 'paid' : 'pending';
        } elseif ($isFullyPaid) {
            $paymentStatus = 'paid';
        } elseif ($daysWithoutPayment >= 30) {
            $rowState = 'inactive';
            $paymentStatus = 'inactive';
        } elseif ($daysWithoutPayment >= 25) {
            $rowState = 'danger';
            $paymentStatus = 'overdue';
        } elseif ($isUnpaid) {
            $rowState = 'unpaid';
            $paymentStatus = 'pending';
        } elseif ($unpaid->contains(fn($row) => strtolower((string) $row->status) === 'overdue')) {
            $paymentStatus = 'overdue';
        }

        return [
            'row_state' => $rowState,
            'payment_status' => $paymentStatus,
            'days_without_payment' => $daysWithoutPayment,
            'last_paid_at' => $lastPaidAt?->toDateTimeString(),
            'next_unpaid_due_date' => $nextUnpaidDue?->toDateString(),
            'paid_count' => $paid->count(),
            'total_count' => $payments->count(),
        ];
    }
}
