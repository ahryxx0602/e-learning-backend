<?php

use Illuminate\Support\Facades\Route;
use Modules\Lessons\Http\Controllers\LessonController;

// ── Admin routes ──────────────────────────────────────────
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Extra routes TRƯỚC để tránh bị match bởi {id}
    Route::get('lessons/trashed',                [LessonController::class, 'trashed']);
    Route::post('lessons/reorder',               [LessonController::class, 'reorder']);

    // Nested: danh sách + tạo lesson thuộc course
    Route::get('courses/{course_id}/lessons',    [LessonController::class, 'index']);
    Route::post('courses/{course_id}/lessons',   [LessonController::class, 'store']);

    // Per-item CRUD
    Route::get('lessons/{id}',                   [LessonController::class, 'show']);
    Route::put('lessons/{id}',                   [LessonController::class, 'update']);
    Route::patch('lessons/{id}',                 [LessonController::class, 'update']);
    Route::delete('lessons/{id}',                [LessonController::class, 'destroy']);
    Route::patch('lessons/{id}/toggle-status',   [LessonController::class, 'toggleStatus']);
    Route::post('lessons/{id}/restore',          [LessonController::class, 'restore']);
    Route::delete('lessons/{id}/force-delete',   [LessonController::class, 'forceDelete']);
});

// ── Client routes (auth:api — học viên đã đăng nhập) ──────
Route::middleware(['auth:api'])->group(function () {
    Route::get('my-courses/{slug}/lessons',  [LessonController::class, 'myLessons']);
    Route::post('lessons/{id}/progress',     [LessonController::class, 'updateProgress']);
    Route::get('courses/{slug}/progress',    [LessonController::class, 'courseProgress']);
});
