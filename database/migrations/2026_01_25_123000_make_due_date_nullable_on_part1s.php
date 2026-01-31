<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL to avoid doctrine/dbal dependency.
        DB::statement('ALTER TABLE `part1s` MODIFY `due_date` DATE NULL');
    }

    public function down(): void
    {
        // Revert to NOT NULL with a default fallback to application_date if needed.
        DB::statement('ALTER TABLE `part1s` MODIFY `due_date` DATE NOT NULL');
    }
};
