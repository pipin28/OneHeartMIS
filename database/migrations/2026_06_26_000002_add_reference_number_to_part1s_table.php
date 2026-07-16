<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            if (! Schema::hasColumn('part1s', 'reference_number')) {
                $table->string('reference_number')->nullable()->after('user_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            if (Schema::hasColumn('part1s', 'reference_number')) {
                $table->dropColumn('reference_number');
            }
        });
    }
};
