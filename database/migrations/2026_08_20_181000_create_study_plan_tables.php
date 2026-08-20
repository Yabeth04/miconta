<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('term_number');
            $table->string('name');
            $table->boolean('is_elective_slot')->default(false);
            $table->unsignedTinyInteger('elective_group')->nullable();
            $table->string('status', 32)->nullable();
            $table->string('note', 255)->nullable();
            $table->string('selected_elective_key', 80)->nullable();
            $table->timestamps();

            $table->index('term_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_subjects');
    }
};
