<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments') || ! Schema::hasTable('part1s')) {
            return;
        }

        $registrationFee = (float) (DB::table('system_settings')
            ->where('key', 'registration_fee')
            ->value('value') ?? 300);

        DB::table('part1s')
            ->select(['id', 'application_date'])
            ->orderBy('id')
            ->chunkById(100, function ($part1s) use ($registrationFee) {
                foreach ($part1s as $part1) {
                    $dueDate = $part1->application_date ?: now()->toDateString();

                    $hasInitialRegistrationFee = DB::table('payments')
                        ->where('part1_id', $part1->id)
                        ->where('payment_type', 'registration_renewal')
                        ->whereDate('due_date', '<=', $dueDate)
                        ->exists();

                    if ($hasInitialRegistrationFee) {
                        continue;
                    }

                    $part2Id = DB::table('part2s')->where('part1_id', $part1->id)->value('id');

                    DB::table('payments')->insert([
                        'part1_id' => $part1->id,
                        'part2_id' => $part2Id,
                        'due_date' => $dueDate,
                        'amount' => max(0, $registrationFee),
                        'payment_type' => 'registration_renewal',
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
