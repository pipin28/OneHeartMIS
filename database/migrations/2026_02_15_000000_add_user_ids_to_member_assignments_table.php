<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('collector_user_id')->nullable()->after('collector_name');
            $table->unsignedBigInteger('agent_user_id')->nullable()->after('agent_name');
            $table->unsignedBigInteger('manager_user_id')->nullable()->after('manager_name');

            $table->index('collector_user_id');
            $table->index('agent_user_id');
            $table->index('manager_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('member_assignments', function (Blueprint $table) {
            $table->dropIndex(['collector_user_id']);
            $table->dropIndex(['agent_user_id']);
            $table->dropIndex(['manager_user_id']);
            $table->dropColumn(['collector_user_id', 'agent_user_id', 'manager_user_id']);
        });
    }
};
