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

        DB::table('payments')->where('part2_id', 0)->update(['part2_id' => null]);

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('part2_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('payments')->whereNull('part2_id')->update(['part2_id' => 0]);

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('part2_id')->nullable(false)->change();
        });
    }
};
