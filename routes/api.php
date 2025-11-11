<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DistributeurController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - OmPay
|--------------------------------------------------------------------------
|
| Routes pour l'API OmPay avec authentification automatique par Observer
|
*/

// OTP routes (pour vérification des codes)
Route::post('/otp/verify', [OtpController::class, 'verifyOtp']);
Route::post('/otp/resend', [OtpController::class, 'resendOtp']);

// Auth routes (OTP envoyé automatiquement par Observer)
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes (nécessitent un token Bearer)
Route::middleware('auth:api')->group(function () {
    Route::post('/transactions/pay', [TransactionController::class, 'pay']);
    Route::post('/transactions/transfert', [TransactionController::class, 'transfer']);
    Route::post('/transactions/depot', [DistributeurController::class, 'depot']);
    Route::post('/transactions/confirm', [TransactionController::class, 'confirmTransaction']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/{uuid}', [TransactionController::class, 'show']);
    Route::get('/distributeurs', [DistributeurController::class, 'index']);
});