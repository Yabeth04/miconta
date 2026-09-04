<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LibraryExercise extends Model
{
    protected $table = 'exercise_library';

    protected $fillable = [
        'user_id',
        'name',
        'muscle_group',
        'sets',
        'reps',
        'load_type',
        'load_value',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sets'       => 'integer',
            'reps'       => 'integer',
            'load_value' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workoutExercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class, 'library_exercise_id');
    }
}
