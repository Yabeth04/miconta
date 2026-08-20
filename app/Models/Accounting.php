<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accounting extends Model
{
    protected $table = 'accounting_movements';

    protected $fillable = [
        'user_id',
        'date',
        'movement_type',
        'description',
        'payment_type',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'date'   => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
