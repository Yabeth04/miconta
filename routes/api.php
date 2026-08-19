<?php

use App\Http\Controllers\AccountingController;
use Illuminate\Support\Facades\Route;

Route::post('accounting/import', [AccountingController::class, 'import']);
Route::apiResource('accounting', AccountingController::class);
