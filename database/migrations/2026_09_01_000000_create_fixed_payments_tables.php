<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_payment_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('monthly_salary', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('fixed_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('description');
            $table->decimal('amount', 14, 2)->default(0);
            $table->string('payment_group', 20);
            $table->string('due_label', 50)->default('Varía');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'payment_group', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_payments');
        Schema::dropIfExists('fixed_payment_settings');
    }
};
