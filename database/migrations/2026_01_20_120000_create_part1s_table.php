<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part1s', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->integer('lpaf_no');
            $table->date('application_date');
            $table->string('sales_counselor_code');
            $table->string('plan_type');
            $table->integer('gross_contact_price');
            $table->string('mode_of_payment');
            $table->string('terms_of_payment');
            $table->date('due_date');
            $table->integer('amount');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part1s');
    }
};
