<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthClose extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'month',
        'closing_balance',
        'total_haber',
        'total_debe',
        'movements_count',
        'opening_balance_at_close',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'year'                     => 'integer',
            'month'                    => 'integer',
            'closing_balance'          => 'decimal:2',
            'total_haber'              => 'decimal:2',
            'total_debe'               => 'decimal:2',
            'movements_count'          => 'integer',
            'opening_balance_at_close' => 'decimal:2',
            'closed_at'                => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function periodKey(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}
