<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_user_id')->nullable()->after('user_id');
            $table->index('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('part1s', function (Blueprint $table) {
            $table->dropIndex(['created_by_user_id']);
            $table->dropColumn('created_by_user_id');
        });
    }
};
