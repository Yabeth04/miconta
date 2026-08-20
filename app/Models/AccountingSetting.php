<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingSetting extends Model
{
    protected $table = 'accounting_settings';

    protected $fillable = [
        'opening_balance_main',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance_main' => 'decimal:2',
        ];
    }
}
