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
        Schema::table('part2_residential_addresses', function (Blueprint $table) {
            foreach (['sss_gsis_no', 'tin_no', 'source_of_funds_if_not_imployed'] as $column) {
                if (Schema::hasColumn('part2_residential_addresses', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (! Schema::hasColumn('part2_residential_addresses', 'religion')) {
                $table->string('religion')->nullable()->after('contact_no');
            }
            if (! Schema::hasColumn('part2_residential_addresses', 'occupation_livelihood')) {
                $table->string('occupation_livelihood')->nullable()->after('religion');
            }
            if (! Schema::hasColumn('part2_residential_addresses', 'valid_id')) {
                $table->string('valid_id')->nullable()->after('occupation_livelihood');
            }
            if (! Schema::hasColumn('part2_residential_addresses', 'valid_id_no')) {
                $table->string('valid_id_no')->nullable()->after('valid_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('part2_residential_addresses', function (Blueprint $table) {
            foreach (['valid_id_no', 'valid_id', 'occupation_livelihood', 'religion'] as $column) {
                if (Schema::hasColumn('part2_residential_addresses', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (! Schema::hasColumn('part2_residential_addresses', 'sss_gsis_no')) {
                $table->string('sss_gsis_no')->nullable()->after('contact_no');
            }
            if (! Schema::hasColumn('part2_residential_addresses', 'tin_no')) {
                $table->string('tin_no')->nullable()->after('sss_gsis_no');
            }
            if (! Schema::hasColumn('part2_residential_addresses', 'source_of_funds_if_not_imployed')) {
                $table->string('source_of_funds_if_not_imployed')->nullable()->after('tin_no');
            }
        });
    }
};
