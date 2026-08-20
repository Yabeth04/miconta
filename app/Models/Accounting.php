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
        'concept',
        'detail',
        'accounting_concept_id',
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

    public function fixedConcept(): BelongsTo
    {
        return $this->belongsTo(AccountingConcept::class, 'accounting_concept_id');
    }
}
