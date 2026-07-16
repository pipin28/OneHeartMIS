<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            if (Schema::hasColumn('part1s', 'lpaf_no')) {
                $table->dropColumn('lpaf_no');
            }

            if (Schema::hasColumn('part1s', 'sales_counselor_code')) {
                $table->dropColumn('sales_counselor_code');
            }

            if (! Schema::hasColumn('part1s', 'approved_date')) {
                $table->date('approved_date')->nullable()->after('application_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            if (! Schema::hasColumn('part1s', 'lpaf_no')) {
                $table->integer('lpaf_no')->nullable()->after('user_id');
            }

            if (Schema::hasColumn('part1s', 'approved_date')) {
                $table->dropColumn('approved_date');
            }

            if (! Schema::hasColumn('part1s', 'sales_counselor_code')) {
                $table->string('sales_counselor_code')->nullable()->after('application_date');
            }
        });
    }
};
