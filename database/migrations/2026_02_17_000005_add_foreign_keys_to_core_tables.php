<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        $this->cleanupOrphanData();

        Schema::table('part1s', function (Blueprint $table) {
            $table->foreign('member_assignment_id')
                ->references('id')
                ->on('member_assignments')
                ->nullOnDelete();
            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        Schema::table('part2s', function (Blueprint $table) {
            $table->foreign('part1_id')
                ->references('id')
                ->on('part1s')
                ->cascadeOnDelete();
        });

        Schema::table('part2_residential_addresses', function (Blueprint $table) {
            $table->foreign('part1_id')
                ->references('id')
                ->on('part1s')
                ->cascadeOnDelete();
            $table->foreign('part2_id')
                ->references('id')
                ->on('part2s')
                ->cascadeOnDelete();
        });

        Schema::table('part2_beneficiaries', function (Blueprint $table) {
            $table->foreign('part1_id')
                ->references('id')
                ->on('part1s')
                ->cascadeOnDelete();
            $table->foreign('part2_id')
                ->references('id')
                ->on('part2s')
                ->cascadeOnDelete();
            $table->foreign('par2_residential_address_id')
                ->references('id')
                ->on('part2_residential_addresses')
                ->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('part1_id')
                ->references('id')
                ->on('part1s')
                ->cascadeOnDelete();
        });

        Schema::table('member_assignments', function (Blueprint $table) {
            $table->foreign('collector_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('agent_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('manager_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('member_assignments', function (Blueprint $table) {
            $table->dropForeign(['collector_user_id']);
            $table->dropForeign(['agent_user_id']);
            $table->dropForeign(['manager_user_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['part1_id']);
        });

        Schema::table('part2_beneficiaries', function (Blueprint $table) {
            $table->dropForeign(['part1_id']);
            $table->dropForeign(['part2_id']);
            $table->dropForeign(['par2_residential_address_id']);
        });

        Schema::table('part2_residential_addresses', function (Blueprint $table) {
            $table->dropForeign(['part1_id']);
            $table->dropForeign(['part2_id']);
        });

        Schema::table('part2s', function (Blueprint $table) {
            $table->dropForeign(['part1_id']);
        });

        Schema::table('part1s', function (Blueprint $table) {
            $table->dropForeign(['member_assignment_id']);
            $table->dropForeign(['created_by_user_id']);
        });
    }

    private function cleanupOrphanData(): void
    {
        DB::table('part1s')
            ->whereNotNull('member_assignment_id')
            ->whereNotIn('member_assignment_id', DB::table('member_assignments')->select('id'))
            ->update(['member_assignment_id' => null]);

        DB::table('part1s')
            ->whereNotNull('created_by_user_id')
            ->whereNotIn('created_by_user_id', DB::table('users')->select('id'))
            ->update(['created_by_user_id' => null]);

        DB::table('part2s')
            ->whereNotIn('part1_id', DB::table('part1s')->select('id'))
            ->delete();

        DB::table('part2_residential_addresses')
            ->whereNotIn('part1_id', DB::table('part1s')->select('id'))
            ->orWhereNotIn('part2_id', DB::table('part2s')->select('id'))
            ->delete();

        DB::table('part2_beneficiaries')
            ->whereNotIn('part1_id', DB::table('part1s')->select('id'))
            ->orWhereNotIn('part2_id', DB::table('part2s')->select('id'))
            ->delete();

        DB::table('part2_beneficiaries')
            ->whereNotNull('par2_residential_address_id')
            ->whereNotIn('par2_residential_address_id', DB::table('part2_residential_addresses')->select('id'))
            ->update(['par2_residential_address_id' => null]);

        DB::table('payments')
            ->whereNotIn('part1_id', DB::table('part1s')->select('id'))
            ->delete();

        DB::table('member_assignments')
            ->whereNotNull('collector_user_id')
            ->whereNotIn('collector_user_id', DB::table('users')->select('id'))
            ->update(['collector_user_id' => null]);

        DB::table('member_assignments')
            ->whereNotNull('agent_user_id')
            ->whereNotIn('agent_user_id', DB::table('users')->select('id'))
            ->update(['agent_user_id' => null]);

        DB::table('member_assignments')
            ->whereNotNull('manager_user_id')
            ->whereNotIn('manager_user_id', DB::table('users')->select('id'))
            ->update(['manager_user_id' => null]);
    }
};
