<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('percentage_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('percentage_settings', 'mode')) {
                $table->string('mode', 50)->default('Monthly')->after('id');
            }
        });

        Schema::table('percentage_settings', function (Blueprint $table) {
            $table->dropUnique(['role', 'tier']);
            $table->unique(['mode', 'role', 'tier']);
        });
    }

    public function down(): void
    {
        Schema::table('percentage_settings', function (Blueprint $table) {
            $table->dropUnique(['mode', 'role', 'tier']);
            $table->unique(['role', 'tier']);
            $table->dropColumn('mode');
        });
    }
};
