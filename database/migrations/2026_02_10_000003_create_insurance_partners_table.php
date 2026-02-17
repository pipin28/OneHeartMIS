<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('percent', 5, 2)->default(0);
            $table->unsignedTinyInteger('sort_order')->default(1);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insurance_partners');
    }
};
