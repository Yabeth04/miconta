<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::table('accounting_movements', function (Blueprint $table) {
            $table->foreignId('accounting_concept_id')
                ->nullable()
                ->after('concept')
                ->constrained('accounting_concepts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounting_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('accounting_concept_id');
        });

        Schema::dropIfExists('accounting_concepts');
    }
};
