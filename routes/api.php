<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('accounting/import', [AccountingController::class, 'import']);
    Route::get('accounting/settings', [AccountingController::class, 'showSettings']);
    Route::put('accounting/settings', [AccountingController::class, 'updateSettings']);
    Route::apiResource('accounting', AccountingController::class);
});
