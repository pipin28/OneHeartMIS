<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncPaymentStatusesCommand extends Command
{
    protected $signature = 'payments:sync-status';

    protected $description = 'Sync pending/overdue payment statuses based on due date';

    public function handle(): int
    {
        $today = Carbon::today()->toDateString();

        $toOverdue = DB::table('payments')
            ->where('status', 'pending')
            ->whereDate('due_date', '<', $today)
            ->update([
                'status' => 'overdue',
                'updated_at' => now(),
            ]);

        $toPending = DB::table('payments')
            ->where('status', 'overdue')
            ->whereDate('due_date', '>=', $today)
            ->update([
                'status' => 'pending',
                'updated_at' => now(),
            ]);

        $summary = DB::table('payments')
            ->select(
                'part1_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count"),
                DB::raw("SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count")
            )
            ->groupBy('part1_id')
            ->get();

        foreach ($summary as $row) {
            $nextStatus = 'pending';
            if ((int) $row->total > 0 && (int) $row->paid_count === (int) $row->total) {
                $nextStatus = 'paid';
            } elseif ((int) $row->overdue_count > 0) {
                $nextStatus = 'overdue';
            }

            DB::table('part1s')
                ->where('id', $row->part1_id)
                ->update([
                    'payment_status' => $nextStatus,
                    'updated_at' => now(),
                ]);
        }

        $this->info("Updated to overdue: $toOverdue");
        $this->info("Updated to pending: $toPending");
        $this->info('Part1 status sync complete.');

        return self::SUCCESS;
    }
}
