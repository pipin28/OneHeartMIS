<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('plan_settings', 'legacy_monthly_amount')) {
                $table->bigInteger('legacy_monthly_amount')->nullable()->after('contract_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('plan_settings', function (Blueprint $table) {
            if (Schema::hasColumn('plan_settings', 'legacy_monthly_amount')) {
                $table->dropColumn('legacy_monthly_amount');
            }
        });
    }
};
