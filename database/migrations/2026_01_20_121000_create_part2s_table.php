<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part2s', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part1_id');
            $table->string('surname');
            $table->string('first_name');
            $table->string('midle_name')->nullable();
            $table->string('place_of_birth');
            $table->date('date_of_birth');
            $table->integer('age');
            $table->string('sex_at_birth');
            $table->string('civil_status');
            $table->string('cellular_no');
            $table->string('email_address');
            $table->string('nationality');
            $table->string('institution_name');
            $table->integer('institution_no');
            $table->string('occupation');
            $table->string('name_of_employer');
            $table->string('office_address');
            $table->integer('office_no');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part2s');
    }
};
