<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RegisterController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Роуты админ-панели
|--------------------------------------------------------------------------
|
| Все роуты имеют префикс /admin и именной префикс admin.
|
*/

Route::prefix('admin')->name('admin.')->group(function () {

    // Гостевые роуты (только для неавторизованных)
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [AuthController::class, 'login']);

        Route::middleware('registration.enabled')->group(function () {
            Route::get('/register', [RegisterController::class, 'showRegisterForm'])->name('register');
            Route::post('/register', [RegisterController::class, 'register']);
        });
    });

    // Роуты верификации (ожидание ввода кода)
    Route::middleware('verification.pending')->group(function () {
        Route::get('/verify', [AuthController::class, 'showVerifyForm'])->name('verify');
        Route::post('/verify', [AuthController::class, 'verify']);
        Route::post('/verify/resend', [AuthController::class, 'resendCode'])->name('verify.resend');
    });

    // Авторизованные роуты
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    });
});
