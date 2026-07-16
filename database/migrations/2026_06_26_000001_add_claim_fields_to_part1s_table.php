<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            if (! Schema::hasColumn('part1s', 'claimed_at')) {
                $table->timestamp('claimed_at')->nullable()->after('payment_status');
            }

            if (! Schema::hasColumn('part1s', 'claimed_by_user_id')) {
                $table->unsignedBigInteger('claimed_by_user_id')->nullable()->after('claimed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            if (Schema::hasColumn('part1s', 'claimed_by_user_id')) {
                $table->dropColumn('claimed_by_user_id');
            }

            if (Schema::hasColumn('part1s', 'claimed_at')) {
                $table->dropColumn('claimed_at');
            }
        });
    }
};
