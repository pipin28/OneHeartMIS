<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('part2_residential_addresses')) {
            return;
        }

        if (! Schema::hasColumn('part2_residential_addresses', 'complete_address')) {
            DB::statement('ALTER TABLE `part2_residential_addresses` ADD `complete_address` TEXT NULL AFTER `part2_id`');
        }

        if (Schema::hasColumn('part2_residential_addresses', 'street')) {
            DB::table('part2_residential_addresses')
                ->select(['id', 'lot_house_numer', 'street', 'barangay', 'province', 'zip_code'])
                ->orderBy('id')
                ->chunkById(100, function ($rows) {
                    foreach ($rows as $row) {
                        $completeAddress = collect([
                            $row->lot_house_numer ?? null,
                            $row->street ?? null,
                            $row->barangay ?? null,
                            $row->province ?? null,
                            $row->zip_code ?? null,
                        ])
                            ->map(fn($value) => trim((string) $value))
                            ->filter()
                            ->implode(', ');

                        DB::table('part2_residential_addresses')
                            ->where('id', $row->id)
                            ->update(['complete_address' => $completeAddress]);
                    }
                }, 'id');
        }

        foreach (['lot_house_numer', 'street', 'barangay', 'province', 'zip_code'] as $column) {
            if (Schema::hasColumn('part2_residential_addresses', $column)) {
                DB::statement("ALTER TABLE `part2_residential_addresses` DROP COLUMN `{$column}`");
            }
        }

        DB::statement('ALTER TABLE `part2_residential_addresses` MODIFY `complete_address` TEXT NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('part2_residential_addresses')) {
            return;
        }

        if (! Schema::hasColumn('part2_residential_addresses', 'lot_house_numer')) {
            DB::statement('ALTER TABLE `part2_residential_addresses` ADD `lot_house_numer` VARCHAR(255) NULL AFTER `part2_id`');
        }
        if (! Schema::hasColumn('part2_residential_addresses', 'street')) {
            DB::statement('ALTER TABLE `part2_residential_addresses` ADD `street` VARCHAR(255) NULL AFTER `lot_house_numer`');
        }
        if (! Schema::hasColumn('part2_residential_addresses', 'barangay')) {
            DB::statement('ALTER TABLE `part2_residential_addresses` ADD `barangay` VARCHAR(255) NULL AFTER `street`');
        }
        if (! Schema::hasColumn('part2_residential_addresses', 'province')) {
            DB::statement('ALTER TABLE `part2_residential_addresses` ADD `province` VARCHAR(255) NULL AFTER `barangay`');
        }
        if (! Schema::hasColumn('part2_residential_addresses', 'zip_code')) {
            DB::statement('ALTER TABLE `part2_residential_addresses` ADD `zip_code` VARCHAR(255) NULL AFTER `province`');
        }

        if (Schema::hasColumn('part2_residential_addresses', 'complete_address')) {
            DB::statement('ALTER TABLE `part2_residential_addresses` DROP COLUMN `complete_address`');
        }
    }
};
