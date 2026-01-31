<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part1s', function () {
            DB::statement('ALTER TABLE part1s MODIFY gross_contact_price BIGINT');
        });
    }

    public function down(): void
    {
        Schema::table('part1s', function () {
            DB::statement('ALTER TABLE part1s MODIFY gross_contact_price INT');
        });
    }
};
