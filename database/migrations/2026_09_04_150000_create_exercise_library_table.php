<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_library', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('muscle_group')->nullable();
            $table->unsignedTinyInteger('sets')->default(4);
            $table->unsignedTinyInteger('reps')->default(11);
            $table->string('load_type', 16)->default('level');
            $table->decimal('load_value', 8, 2)->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->foreignId('library_exercise_id')
                ->nullable()
                ->after('workout_day_id')
                ->constrained('exercise_library')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workout_exercises', function (Blueprint $table) {
            $table->dropConstrainedForeignId('library_exercise_id');
        });

        Schema::dropIfExists('exercise_library');
    }
};
