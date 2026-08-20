<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudySubject extends Model
{
    public const STATUSES = [
        'matriculado',
        'en_curso',
        'aprobado',
        'reprobado',
    ];

    protected $fillable = [
        'term_number',
        'name',
        'is_elective_slot',
        'elective_group',
        'status',
        'note',
        'selected_elective_key',
        'elective_preferences',
    ];

    protected function casts(): array
    {
        return [
            'term_number'          => 'integer',
            'is_elective_slot'     => 'boolean',
            'elective_group'       => 'integer',
            'elective_preferences' => 'array',
        ];
    }
}
