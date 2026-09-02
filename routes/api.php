<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AccountingConceptController;
use App\Http\Controllers\AccountingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FixedPaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudyPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);

    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::put('/user/password', [ProfileController::class, 'updatePassword']);
    Route::post('/user/avatar', [ProfileController::class, 'updateAvatar']);
    Route::delete('/user/avatar', [ProfileController::class, 'destroyAvatar']);

    Route::get('accounting/stats', [AccountingController::class, 'stats']);

    Route::post('accounting/import', [AccountingController::class, 'import']);
    Route::post('accounting/bulk-update', [AccountingController::class, 'bulkUpdate']);
    Route::post('accounting/bulk-destroy', [AccountingController::class, 'bulkDestroy']);
    Route::get('accounting/settings', [AccountingController::class, 'showSettings']);
    Route::put('accounting/settings', [AccountingController::class, 'updateSettings']);

    Route::get('accounting/concepts', [AccountingConceptController::class, 'index']);
    Route::post('accounting/concepts', [AccountingConceptController::class, 'store']);
    Route::put('accounting/concepts/{concept}', [AccountingConceptController::class, 'update']);
    Route::delete('accounting/concepts/{concept}', [AccountingConceptController::class, 'destroy']);

    Route::apiResource('accounting', AccountingController::class);

    Route::get('fixed-payments', [FixedPaymentController::class, 'index']);
    Route::put('fixed-payments/settings', [FixedPaymentController::class, 'updateSettings']);
    Route::post('fixed-payments', [FixedPaymentController::class, 'store']);
    Route::put('fixed-payments/{fixedPayment}', [FixedPaymentController::class, 'update']);
    Route::delete('fixed-payments/{fixedPayment}', [FixedPaymentController::class, 'destroy']);

    Route::middleware('sysadmin')->group(function () {
        Route::get('users', [AdminUserController::class, 'index']);
        Route::post('users', [AdminUserController::class, 'store']);
        Route::put('users/{user}', [AdminUserController::class, 'update']);
        Route::delete('users/{user}', [AdminUserController::class, 'destroy']);

        Route::get('study-plan', [StudyPlanController::class, 'index']);
        Route::post('study-plan/subjects', [StudyPlanController::class, 'storeSubject']);
        Route::put('study-plan/subjects/{subject}', [StudyPlanController::class, 'updateSubject']);
        Route::delete('study-plan/subjects/{subject}', [StudyPlanController::class, 'destroySubject']);
    });
});
