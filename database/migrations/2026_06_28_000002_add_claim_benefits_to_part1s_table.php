<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            if (! Schema::hasColumn('part1s', 'claim_contribution_months')) {
                $table->unsignedInteger('claim_contribution_months')->default(0)->after('claimed_by_user_id');
            }

            if (! Schema::hasColumn('part1s', 'claim_cash_assistance')) {
                $table->decimal('claim_cash_assistance', 12, 2)->default(0)->after('claim_contribution_months');
            }

            if (! Schema::hasColumn('part1s', 'claim_burial_assistance')) {
                $table->decimal('claim_burial_assistance', 12, 2)->default(0)->after('claim_cash_assistance');
            }

            if (! Schema::hasColumn('part1s', 'claim_total_amount')) {
                $table->decimal('claim_total_amount', 12, 2)->default(0)->after('claim_burial_assistance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            foreach ([
                'claim_total_amount',
                'claim_burial_assistance',
                'claim_cash_assistance',
                'claim_contribution_months',
            ] as $column) {
                if (Schema::hasColumn('part1s', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
