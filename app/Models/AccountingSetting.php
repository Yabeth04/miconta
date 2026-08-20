<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingSetting extends Model
{
    protected $table = 'accounting_settings';

    protected $fillable = [
        'user_id',
        'opening_balance_main',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance_main' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
