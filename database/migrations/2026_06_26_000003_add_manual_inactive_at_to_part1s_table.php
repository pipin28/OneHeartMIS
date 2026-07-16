<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            if (! Schema::hasColumn('part1s', 'manual_inactive_at')) {
                $table->timestamp('manual_inactive_at')->nullable()->after('payment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            if (Schema::hasColumn('part1s', 'manual_inactive_at')) {
                $table->dropColumn('manual_inactive_at');
            }
        });
    }
};
