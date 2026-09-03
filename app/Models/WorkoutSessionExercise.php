<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSessionExercise extends Model
{
    protected $fillable = [
        'workout_session_id',
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

    public function session(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class, 'workout_session_id');
    }
}
