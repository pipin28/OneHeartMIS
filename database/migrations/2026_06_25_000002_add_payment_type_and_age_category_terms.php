<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'payment_type')) {
                $table->string('payment_type', 50)->default('regular')->after('amount');
                $table->index(['part1_id', 'payment_type', 'due_date']);
            }
        });

        DB::table('payments')
            ->whereNull('payment_type')
            ->orWhere('payment_type', '')
            ->update(['payment_type' => 'regular']);

        DB::table('plan_settings')
            ->whereIn('name', ['Age 60 to 65', 'Age 66 to 70', 'Age 71 to 80', 'Age 81 above'])
            ->update([
                'default_terms' => '60 months',
                'default_months' => 60,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'payment_type')) {
                $table->dropIndex(['part1_id', 'payment_type', 'due_date']);
                $table->dropColumn('payment_type');
            }
        });

        DB::table('plan_settings')
            ->whereIn('name', ['Age 60 to 65', 'Age 66 to 70', 'Age 71 to 80', 'Age 81 above'])
            ->update([
                'default_terms' => 'Monthly',
                'default_months' => 1,
                'updated_at' => now(),
            ]);
    }
};
