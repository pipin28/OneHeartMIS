<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('part2s', 'cellular_no')) {
            DB::statement('ALTER TABLE `part2s` MODIFY `cellular_no` VARCHAR(255) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('part2s', 'cellular_no')) {
            DB::table('part2s')->whereNull('cellular_no')->update(['cellular_no' => '']);
            DB::statement('ALTER TABLE `part2s` MODIFY `cellular_no` VARCHAR(255) NOT NULL');
        }
    }
};
