<?php

use App\Http\Controllers\AccountingConceptController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StudyPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

    Route::post('accounting/import', [AccountingController::class, 'import']);
    Route::get('accounting/settings', [AccountingController::class, 'showSettings']);
    Route::put('accounting/settings', [AccountingController::class, 'updateSettings']);

    Route::get('accounting/concepts', [AccountingConceptController::class, 'index']);
    Route::post('accounting/concepts', [AccountingConceptController::class, 'store']);
    Route::put('accounting/concepts/{concept}', [AccountingConceptController::class, 'update']);
    Route::delete('accounting/concepts/{concept}', [AccountingConceptController::class, 'destroy']);

    Route::apiResource('accounting', AccountingController::class);

    Route::middleware('sysadmin')->group(function () {
        Route::get('study-plan', [StudyPlanController::class, 'index']);
        Route::post('study-plan/subjects', [StudyPlanController::class, 'storeSubject']);
        Route::put('study-plan/subjects/{subject}', [StudyPlanController::class, 'updateSubject']);
        Route::delete('study-plan/subjects/{subject}', [StudyPlanController::class, 'destroySubject']);
    });
});
