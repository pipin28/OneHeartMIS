<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part2_residential_addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part1_id');
            $table->unsignedBigInteger('part2_id');
            $table->string('lot_house_numer');
            $table->string('street');
            $table->string('barangay');
            $table->string('province');
            $table->string('zip_code');
            $table->string('contact_no');
            $table->string('sss_gsis_no');
            $table->string('tin_no');
            $table->string('source_of_funds_if_not_imployed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part2_residential_addresses');
    }
};
