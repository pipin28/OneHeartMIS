<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->bigInteger('contract_amount');
            $table->integer('premium_amount');
            $table->string('default_mode');
            $table->string('default_terms');
            $table->integer('default_months');
            $table->timestamps();
        });

        DB::table('plan_settings')->insert([
            [
                'name' => 'Serenity Care',
                'contract_amount' => 30000,
                'premium_amount' => 500,
                'default_mode' => 'Monthly',
                'default_terms' => '60 months (5 years)',
                'default_months' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Everlasting Care',
                'contract_amount' => 20000,
                'premium_amount' => 350,
                'default_mode' => 'Monthly',
                'default_terms' => '60 months (5 years)',
                'default_months' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Legacy Care',
                'contract_amount' => 30000,
                'premium_amount' => 0,
                'default_mode' => 'One-time',
                'default_terms' => 'Infinite',
                'default_months' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_settings');
    }
};
