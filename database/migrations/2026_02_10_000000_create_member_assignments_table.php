<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('collector_name');
            $table->string('agent_name');
            $table->string('manager_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_assignments');
    }
};
