<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('insurance_total', 12, 2)->nullable()->after('amount');
            $table->decimal('net_amount', 12, 2)->nullable()->after('insurance_total');
            $table->text('insurance_breakdown')->nullable()->after('net_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['insurance_total', 'net_amount', 'insurance_breakdown']);
        });
    }
};
