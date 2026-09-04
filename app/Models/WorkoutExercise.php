<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutExercise extends Model
{
    public const LOAD_TYPES = ['level', 'kg', 'bodyweight', 'km'];

    protected $fillable = [
        'workout_day_id',
        'library_exercise_id',
        'name',
        'muscle_group',
        'sets',
        'reps',
        'load_type',
        'load_value',
        'notes',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sets'       => 'integer',
            'reps'       => 'integer',
            'load_value' => 'float',
            'sort_order' => 'integer',
        ];
    }

    public function day(): BelongsTo
    {
        return $this->belongsTo(WorkoutDay::class, 'workout_day_id');
    }

    public function libraryExercise(): BelongsTo
    {
        return $this->belongsTo(LibraryExercise::class, 'library_exercise_id');
    }
}
