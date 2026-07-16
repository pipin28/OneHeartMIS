<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('plan_settings')->delete();

        DB::table('plan_settings')->insert($this->ageCategoryRows());

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->timestamps();
        });

        DB::table('system_settings')->insert([
            'key' => 'registration_fee',
            'value' => '300',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');

        DB::table('plan_settings')->delete();

        DB::table('plan_settings')->insert([
            [
                'name' => 'Serenity Care',
                'contract_amount' => 30000,
                'legacy_monthly_amount' => null,
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
                'legacy_monthly_amount' => null,
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
                'legacy_monthly_amount' => 0,
                'premium_amount' => 0,
                'default_mode' => 'One-time',
                'default_terms' => 'Infinite',
                'default_months' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    private function ageCategoryRows(): array
    {
        return [
            $this->ageCategoryRow('Age 60 to 65', 100),
            $this->ageCategoryRow('Age 66 to 70', 120),
            $this->ageCategoryRow('Age 71 to 80', 150),
            $this->ageCategoryRow('Age 81 above', 200),
        ];
    }

    private function ageCategoryRow(string $name, int $amount): array
    {
        return [
            'name' => $name,
            'contract_amount' => $amount,
            'legacy_monthly_amount' => null,
            'premium_amount' => $amount,
            'default_mode' => 'Monthly',
            'default_terms' => 'Monthly',
            'default_months' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
};
