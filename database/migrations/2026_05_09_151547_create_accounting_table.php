<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounting_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('movement_type', ['haber', 'debe']);
            $table->string('concept')->nullable();
            $table->string('detail')->nullable();
            $table->enum('payment_type', ['sinpe', 'efectivo', 'transferencia', 'tarjeta', 'otros']);
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_movements');
    }
};
