<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Admin\AuthController as AdminAuthController;
use Modules\Auth\Http\Controllers\Student\AuthController as StudentAuthController;

/*
|--------------------------------------------------------------------------
| Auth Module — API Routes
|--------------------------------------------------------------------------
| Prefix: /api/v1  (được thêm bởi RouteServiceProvider)
|
| ── ADMIN AUTH ──────────────────────────────────────────────
| POST   /admin/auth/login    → Đăng nhập admin (public)
| POST   /admin/auth/logout   → Đăng xuất admin  [auth:admin]
| GET    /admin/auth/me       → Thông tin admin   [auth:admin]
|
| ── STUDENT AUTH ────────────────────────────────────────────
| POST   /auth/register                → Đăng ký học viên
| POST   /auth/login                   → Đăng nhập
| POST   /auth/logout                  → Đăng xuất [auth:api]
| GET    /auth/me                      → Thông tin học viên [auth:api]
| GET    /auth/verify-email/{token}    → Xác thực email
| POST   /auth/forgot-password         → Gửi link reset mật khẩu
| POST   /auth/reset-password          → Đặt lại mật khẩu
*/

// ─── ADMIN AUTH (guard: admin) ───────────────────────────────
Route::prefix('admin/auth')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('me', [AdminAuthController::class, 'me']);
    });
});

// ─── STUDENT AUTH (guard: api) ───────────────────────────────
Route::prefix('auth')->group(function () {
    // Public
    Route::post('register', [StudentAuthController::class, 'register'])
        ->middleware('throttle:10,1');

    Route::post('login', [StudentAuthController::class, 'login'])
        ->middleware('throttle:5,1');

    Route::get('verify-email/{token}', [StudentAuthController::class, 'verifyEmail'])
        ->where('token', '[a-f0-9]{64}');

    Route::post('forgot-password', [StudentAuthController::class, 'forgotPassword'])
        ->middleware('throttle:3,1');

    Route::post('resend-verification', [StudentAuthController::class, 'resendVerification'])
        ->middleware('throttle:3,1');

    Route::post('reset-password', [StudentAuthController::class, 'resetPassword']);

    // Protected
    Route::middleware('auth:api')->group(function () {
        Route::post('logout', [StudentAuthController::class, 'logout']);
        Route::get('me', [StudentAuthController::class, 'me']);
    });
});