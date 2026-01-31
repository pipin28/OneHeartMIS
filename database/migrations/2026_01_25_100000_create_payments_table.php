<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('part1_id');
            $table->unsignedBigInteger('part2_id');
            $table->date('due_date');
            $table->decimal('amount', 12, 2);
            $table->string('status', 50)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['part1_id', 'due_date']);
            $table->index(['part2_id', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
