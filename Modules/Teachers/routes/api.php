<?php

use Illuminate\Support\Facades\Route;
use Modules\Teachers\Http\Controllers\TeachersController;

/*
|--------------------------------------------------------------------------
| Admin Routes (auth:admin middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Extra routes (đặt TRƯỚC apiResource để tránh bị match bởi {teacher})
    Route::get('teachers/trashed',               [TeachersController::class, 'trashed']);

    // Bulk routes
    Route::post('teachers/bulk-restore',         [TeachersController::class, 'bulkRestore']);
    Route::delete('teachers/bulk-delete',        [TeachersController::class, 'bulkDelete']);
    Route::delete('teachers/bulk-force-delete',  [TeachersController::class, 'bulkForceDelete']);

    // Standard CRUD
    Route::apiResource('teachers', TeachersController::class)->names('admin.teachers');

    // Per-item actions (đặt SAU apiResource)
    Route::patch('teachers/{id}/toggle-status',  [TeachersController::class, 'toggleStatus']);
    Route::post('teachers/{id}/restore',         [TeachersController::class, 'restore']);
    Route::delete('teachers/{id}/force-delete',  [TeachersController::class, 'forceDelete']);
});

/*
|--------------------------------------------------------------------------
| Public Routes (không cần auth)
|--------------------------------------------------------------------------
*/
Route::group([], function () {
    Route::get('teachers',           [TeachersController::class, 'publicIndex']);
    Route::get('teachers/{slug}',    [TeachersController::class, 'publicShow']);
});
