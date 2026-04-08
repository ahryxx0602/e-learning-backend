<?php

use Illuminate\Support\Facades\Route;
use Modules\Users\Http\Controllers\UsersController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Bulk routes phải đặt TRƯỚC apiResource để tránh bị match bởi {user}
    // Bulk + static routes phải đặt TRƯỚC apiResource để tránh bị match bởi {user}
    Route::get('users/trashed',               [UsersController::class, 'trashed']);
    Route::post('users/bulk-restore',         [UsersController::class, 'bulkRestore']);
    Route::delete('users/bulk-delete',        [UsersController::class, 'bulkDelete']);
    Route::delete('users/bulk-force-delete',  [UsersController::class, 'bulkForceDelete']);
    Route::post('users/bulk-action',          [UsersController::class, 'bulkAction']);
    Route::post('users/bulk-assign-role',     [UsersController::class, 'bulkAssignRole']);

    Route::apiResource('users', UsersController::class)->names('admin.users');

    Route::post('users/{id}/assign-role',    [UsersController::class, 'assignRole']);
    Route::post('users/{id}/revoke-role',    [UsersController::class, 'revokeRole']);
    Route::post('users/{id}/restore',        [UsersController::class, 'restore']);
    Route::delete('users/{id}/force-delete', [UsersController::class, 'forceDelete']);
});
