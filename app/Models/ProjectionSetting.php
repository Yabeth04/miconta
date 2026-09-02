<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectionSetting extends Model
{
    protected $fillable = [
        'user_id',
        'university_fee',
        'monthly_remaining',
    ];

    protected function casts(): array
    {
        return [
            'university_fee'    => 'decimal:2',
            'monthly_remaining' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
