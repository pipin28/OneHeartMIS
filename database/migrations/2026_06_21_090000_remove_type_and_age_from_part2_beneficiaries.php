<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part2_beneficiaries', function (Blueprint $table) {
            if (Schema::hasColumn('part2_beneficiaries', 'type')) {
                $table->dropColumn('type');
            }

            if (Schema::hasColumn('part2_beneficiaries', 'age')) {
                $table->dropColumn('age');
            }
        });
    }

    public function down(): void
    {
        Schema::table('part2_beneficiaries', function (Blueprint $table) {
            if (! Schema::hasColumn('part2_beneficiaries', 'type')) {
                $table->string('type')->nullable()->after('par2_residential_address_id');
            }

            if (! Schema::hasColumn('part2_beneficiaries', 'age')) {
                $table->integer('age')->nullable()->after('name');
            }
        });
    }
};
