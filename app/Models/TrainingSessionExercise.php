<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingSessionExercise extends Model
{
    protected $fillable = [
        'training_session_id',
        'training_exercise_id',
        'position',
        'load_type',
        'sets',
        'reps',
        'load_value',
        'duration_seconds',
        'cue',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'sets' => 'integer',
            'reps' => 'integer',
            'load_value' => 'decimal:2',
            'duration_seconds' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class, 'training_session_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(TrainingExercise::class, 'training_exercise_id');
    }

    public function setDetails(): HasMany
    {
        return $this->hasMany(TrainingSet::class)->orderBy('position');
    }
}
