<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mode = 'Semi-Annual';
        $roles = ['collector', 'agent', 'manager', 'others'];

        foreach ($roles as $role) {
            $old = DB::table('percentage_settings')
                ->where('mode', $mode)
                ->where('role', $role)
                ->where('tier', 'semis_2_10')
                ->first();

            if ($old) {
                $exists = DB::table('percentage_settings')
                    ->where('mode', $mode)
                    ->where('role', $role)
                    ->where('tier', 'semis_3_10')
                    ->exists();

                if (! $exists) {
                    DB::table('percentage_settings')->insert([
                        'mode' => $mode,
                        'role' => $role,
                        'tier' => 'semis_3_10',
                        'percent' => $old->percent,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        DB::table('percentage_settings')
            ->where('mode', $mode)
            ->whereIn('tier', ['semis_2_10', 'semis_11_30'])
            ->delete();
    }

    public function down(): void
    {
        $mode = 'Semi-Annual';
        $roles = ['collector', 'agent', 'manager', 'others'];

        foreach ($roles as $role) {
            $new = DB::table('percentage_settings')
                ->where('mode', $mode)
                ->where('role', $role)
                ->where('tier', 'semis_3_10')
                ->first();

            if ($new) {
                $exists = DB::table('percentage_settings')
                    ->where('mode', $mode)
                    ->where('role', $role)
                    ->where('tier', 'semis_2_10')
                    ->exists();

                if (! $exists) {
                    DB::table('percentage_settings')->insert([
                        'mode' => $mode,
                        'role' => $role,
                        'tier' => 'semis_2_10',
                        'percent' => $new->percent,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        foreach ($roles as $role) {
            $exists = DB::table('percentage_settings')
                ->where('mode', $mode)
                ->where('role', $role)
                ->where('tier', 'semis_11_30')
                ->exists();

            if (! $exists) {
                DB::table('percentage_settings')->insert([
                    'mode' => $mode,
                    'role' => $role,
                    'tier' => 'semis_11_30',
                    'percent' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::table('percentage_settings')
            ->where('mode', $mode)
            ->whereIn('tier', ['semis_2_2', 'semis_3_10'])
            ->delete();
    }
};
