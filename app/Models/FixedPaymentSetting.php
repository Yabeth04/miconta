<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedPaymentSetting extends Model
{
    protected $fillable = [
        'user_id',
        'monthly_salary',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
