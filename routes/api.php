<?php

use App\Http\Controllers\AccountingController;
use Illuminate\Support\Facades\Route;

Route::post('accounting/import', [AccountingController::class, 'import']);
Route::get('accounting/settings', [AccountingController::class, 'showSettings']);
Route::put('accounting/settings', [AccountingController::class, 'updateSettings']);
Route::apiResource('accounting', AccountingController::class);
