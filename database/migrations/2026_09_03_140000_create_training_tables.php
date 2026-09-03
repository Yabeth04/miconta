<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->string('focus')->nullable();
            $table->boolean('is_rest')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'weekday']);
        });

        Schema::create('workout_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_day_id')->constrained('workout_days')->cascadeOnDelete();
            $table->string('name');
            $table->string('muscle_group')->nullable();
            $table->unsignedTinyInteger('sets')->default(4);
            $table->unsignedTinyInteger('reps')->default(11);
            $table->string('load_type', 16)->default('level');
            $table->decimal('load_value', 8, 2)->nullable();
            $table->string('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_day_id')->nullable()->constrained('workout_days')->nullOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedSmallInteger('calories')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'date']);
        });

        Schema::create('workout_session_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->constrained('workout_sessions')->cascadeOnDelete();
            $table->string('name');
            $table->string('muscle_group')->nullable();
            $table->unsignedTinyInteger('sets')->default(0);
            $table->unsignedTinyInteger('reps')->default(0);
            $table->string('load_type', 16)->default('level');
            $table->decimal('load_value', 8, 2)->nullable();
            $table->string('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_session_exercises');
        Schema::dropIfExists('workout_sessions');
        Schema::dropIfExists('workout_exercises');
        Schema::dropIfExists('workout_days');
    }
};
