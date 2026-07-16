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
        Schema::table('part2s', function (Blueprint $table) {
            foreach ([
                'civil_status',
                'email_address',
                'nationality',
                'institution_name',
                'institution_no',
                'occupation',
                'name_of_employer',
                'office_address',
                'office_no',
            ] as $column) {
                if (Schema::hasColumn('part2s', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('part2s', function (Blueprint $table) {
            if (! Schema::hasColumn('part2s', 'civil_status')) {
                $table->string('civil_status')->nullable()->after('sex_at_birth');
            }
            if (! Schema::hasColumn('part2s', 'email_address')) {
                $table->string('email_address')->nullable()->after('cellular_no');
            }
            if (! Schema::hasColumn('part2s', 'nationality')) {
                $table->string('nationality')->nullable()->after('email_address');
            }
            if (! Schema::hasColumn('part2s', 'institution_name')) {
                $table->string('institution_name')->nullable()->after('nationality');
            }
            if (! Schema::hasColumn('part2s', 'institution_no')) {
                $table->integer('institution_no')->nullable()->after('institution_name');
            }
            if (! Schema::hasColumn('part2s', 'occupation')) {
                $table->string('occupation')->nullable()->after('institution_no');
            }
            if (! Schema::hasColumn('part2s', 'name_of_employer')) {
                $table->string('name_of_employer')->nullable()->after('occupation');
            }
            if (! Schema::hasColumn('part2s', 'office_address')) {
                $table->string('office_address')->nullable()->after('name_of_employer');
            }
            if (! Schema::hasColumn('part2s', 'office_no')) {
                $table->integer('office_no')->nullable()->after('office_address');
            }
        });
    }
};
