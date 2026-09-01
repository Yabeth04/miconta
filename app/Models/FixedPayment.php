<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedPayment extends Model
{
    public const GROUPS = ['primero', 'segundo'];

    protected $fillable = [
        'user_id',
        'description',
        'amount',
        'payment_group',
        'due_label',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'float',
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
