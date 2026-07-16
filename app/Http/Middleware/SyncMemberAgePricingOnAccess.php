<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SyncMemberAgePricingOnAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        $this->syncOncePerDay();

        return $next($request);
    }

    private function syncOncePerDay(): void
    {
        try {
            if (! $this->hasRequiredTables()) {
                return;
            }

            $today = now()->toDateString();
            $key = 'member_age_pricing_synced_on';
            $lastSyncedOn = DB::table('system_settings')->where('key', $key)->value('value');

            if ($lastSyncedOn === $today) {
                return;
            }

            Artisan::call('members:sync-age-pricing');

            DB::table('system_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $today,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } catch (Throwable $exception) {
            Log::warning('Member age pricing sync on access failed.', [
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function hasRequiredTables(): bool
    {
        foreach (['system_settings', 'part1s', 'part2s', 'payments'] as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
