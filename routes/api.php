<?php

use App\Http\Controllers\AccountingController;
use Illuminate\Support\Facades\Route;

Route::post('accounting/import', [AccountingController::class, 'import']);
Route::get('accounting/import/{importId}', [AccountingController::class, 'importStatus']);
Route::apiResource('accounting', AccountingController::class);
