<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            if (! Schema::hasColumn('part1s', 'contestability_at')) {
                $table->timestamp('contestability_at')->nullable()->after('manual_inactive_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            if (Schema::hasColumn('part1s', 'contestability_at')) {
                $table->dropColumn('contestability_at');
            }
        });
    }
};
