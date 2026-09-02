<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnualSalary extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'payday_amount',
    ];

    protected function casts(): array
    {
        return [
            'year'          => 'integer',
            'payday_amount' => 'float',
        ];
    }

    public function monthlyAmount(): float
    {
        return round(((float) $this->payday_amount) * 2, 2);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
