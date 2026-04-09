<?php

use Illuminate\Support\Facades\Route;
use Modules\Categories\Http\Controllers\CategoriesController;

/*
|--------------------------------------------------------------------------
| Admin Routes (auth:admin middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Nested set routes (đặt TRƯỚC apiResource để tránh bị match bởi {category})
    Route::get('categories/tree',                  [CategoriesController::class, 'tree']);
    Route::get('categories/flat-tree',             [CategoriesController::class, 'flatTree']);
    Route::get('categories/trashed',               [CategoriesController::class, 'trashed']);

    // Bulk routes
    Route::post('categories/bulk-restore',         [CategoriesController::class, 'bulkRestore']);
    Route::delete('categories/bulk-delete',        [CategoriesController::class, 'bulkDelete']);
    Route::delete('categories/bulk-force-delete',  [CategoriesController::class, 'bulkForceDelete']);

    // Standard CRUD
    Route::apiResource('categories', CategoriesController::class)->names('admin.categories');

    // Per-item actions (đặt SAU apiResource)
    Route::post('categories/{id}/move',            [CategoriesController::class, 'move']);
    Route::get('categories/{id}/ancestors',        [CategoriesController::class, 'ancestors']);
    Route::get('categories/{id}/descendants',      [CategoriesController::class, 'descendants']);
    Route::patch('categories/{id}/toggle-status',  [CategoriesController::class, 'toggleStatus']);
    Route::post('categories/{id}/restore',         [CategoriesController::class, 'restore']);
    Route::delete('categories/{id}/force-delete',  [CategoriesController::class, 'forceDelete']);
});

/*
|--------------------------------------------------------------------------
| Public Routes (không cần auth)
|--------------------------------------------------------------------------
*/
Route::group([], function () {
    Route::get('categories',            [CategoriesController::class, 'publicIndex']);
    Route::get('categories/tree',       [CategoriesController::class, 'publicTree']);
    Route::get('categories/{slug}',     [CategoriesController::class, 'publicShow']);
});
