<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Admin\AuthController;

/*
|--------------------------------------------------------------------------
| Auth Module — API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api/v1  (được thêm bởi RouteServiceProvider)
|
| ── ADMIN AUTH ──────────────────────────────────────────────
| POST   /admin/auth/login    → Đăng nhập admin (public)
| POST   /admin/auth/logout   → Đăng xuất admin  [auth:admin]
| GET    /admin/auth/me       → Thông tin admin   [auth:admin]
|
| ── CLIENT AUTH (sẽ thêm ở Task 1.4) ──────────────────────
| POST   /auth/register       → Đăng ký student
| POST   /auth/login          → Đăng nhập student
| ...
*/

// ─── ADMIN AUTH (guard: admin) ───────────────────────────────
Route::prefix('admin/auth')->group(function () {
    // Public — không cần đăng nhập
    // Rate limit: 5 lần/phút để chống brute-force
    Route::post('login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');

    // Protected — cần token admin
    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
    });
});
