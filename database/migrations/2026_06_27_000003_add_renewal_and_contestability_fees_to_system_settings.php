<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        $registrationFee = DB::table('system_settings')
            ->where('key', 'registration_fee')
            ->value('value') ?? '300';

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'renewal_fee'],
            [
                'value' => (string) max(0, (int) $registrationFee),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'contestability_fee'],
            [
                'value' => '0',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('system_settings')) {
            return;
        }

        DB::table('system_settings')
            ->whereIn('key', ['renewal_fee', 'contestability_fee'])
            ->delete();
    }
};
