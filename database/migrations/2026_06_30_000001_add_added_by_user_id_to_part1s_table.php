<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('part1s', 'added_by_user_id')) {
            Schema::table('part1s', function (Blueprint $table) {
                $after = Schema::hasColumn('part1s', 'created_by_user_id')
                    ? 'created_by_user_id'
                    : 'user_id';

                $table->unsignedBigInteger('added_by_user_id')->nullable()->after($after);
                $table->index('added_by_user_id');
            });
        }

        if (Schema::hasColumn('part1s', 'created_by_user_id')) {
            DB::table('part1s')
                ->whereNull('added_by_user_id')
                ->update(['added_by_user_id' => DB::raw('created_by_user_id')]);
        }

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('part1s', function (Blueprint $table) {
                $table->foreign('added_by_user_id', 'part1s_added_by_user_id_foreign')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('part1s', 'added_by_user_id')) {
            return;
        }

        Schema::table('part1s', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign('part1s_added_by_user_id_foreign');
            }

            $table->dropIndex(['added_by_user_id']);
            $table->dropColumn('added_by_user_id');
        });
    }
};
