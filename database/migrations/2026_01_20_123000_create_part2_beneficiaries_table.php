<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part2_beneficiaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part1_id');
            $table->unsignedBigInteger('part2_id');
            $table->unsignedBigInteger('par2_residential_address_id')->nullable();
            $table->string('name');
            $table->string('address');
            $table->string('relationship_to_planholder');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part2_beneficiaries');
    }
};
