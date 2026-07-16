<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('part1s', 'member_status')) {
            Schema::table('part1s', function (Blueprint $table) {
                $table->string('member_status', 30)->default('active')->after('payment_status');
                $table->index('member_status');
            });
        }

        DB::table('part1s')
            ->whereNotNull('claimed_at')
            ->update(['member_status' => 'claimed']);

        DB::table('part1s')
            ->whereNull('claimed_at')
            ->where('payment_status', 'inactive')
            ->update(['member_status' => 'inactive']);

        DB::table('part1s')
            ->whereNull('claimed_at')
            ->where(function ($query) {
                $query->whereNull('payment_status')
                    ->orWhere('payment_status', '!=', 'inactive');
            })
            ->update(['member_status' => 'active']);
    }

    public function down(): void
    {
        if (! Schema::hasColumn('part1s', 'member_status')) {
            return;
        }

        Schema::table('part1s', function (Blueprint $table) {
            $table->dropIndex(['member_status']);
            $table->dropColumn('member_status');
        });
    }
};
