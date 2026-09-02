<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('month_closes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('closing_balance', 14, 2);
            $table->decimal('total_haber', 14, 2)->default(0);
            $table->decimal('total_debe', 14, 2)->default(0);
            $table->unsignedInteger('movements_count')->default(0);
            $table->decimal('opening_balance_at_close', 14, 2)->default(0);
            $table->timestamp('closed_at');
            $table->timestamps();

            $table->unique(['user_id', 'year', 'month']);
            $table->index(['user_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('month_closes');
    }
};
