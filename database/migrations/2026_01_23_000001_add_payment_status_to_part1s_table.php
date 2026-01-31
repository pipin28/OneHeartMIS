<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            $table->string('payment_status')->default('pending')->after('amount');
        });

        DB::table('part1s')
            ->whereNull('payment_status')
            ->update(['payment_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            $table->dropColumn('payment_status');
        });
    }
};
