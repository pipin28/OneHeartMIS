<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('percentage_settings', function (Blueprint $table) {
            $table->id();
            $table->string('mode', 50)->default('Monthly');
            $table->string('role', 50);
            $table->string('tier', 50);
            $table->decimal('percent', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['mode', 'role', 'tier']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('percentage_settings');
    }
};
