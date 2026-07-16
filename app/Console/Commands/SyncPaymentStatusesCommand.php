<?php

namespace App\Console\Commands;

use App\Services\PaymentLifecycleService;
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

        $part1Ids = DB::table('payments')->distinct()->pluck('part1_id');
        app(PaymentLifecycleService::class)->sync($part1Ids);

        $this->info("Updated to overdue: $toOverdue");
        $this->info("Updated to pending: $toPending");
        $this->info('Part1 status sync complete.');

        return self::SUCCESS;
    }
}
