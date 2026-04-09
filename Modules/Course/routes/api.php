<?php

use Illuminate\Support\Facades\Route;
use Modules\Course\Http\Controllers\CourseController;

/*
|--------------------------------------------------------------------------
| Admin Routes (auth:admin middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Extra routes (đặt TRƯỚC apiResource để tránh bị match bởi {course})
    Route::get('courses/trashed',               [CourseController::class, 'trashed']);

    // Bulk routes
    Route::post('courses/bulk-restore',         [CourseController::class, 'bulkRestore']);
    Route::delete('courses/bulk-delete',        [CourseController::class, 'bulkDelete']);
    Route::delete('courses/bulk-force-delete',  [CourseController::class, 'bulkForceDelete']);

    // Standard CRUD
    Route::apiResource('courses', CourseController::class)->names('admin.courses');

    // Per-item actions (đặt SAU apiResource)
    Route::patch('courses/{id}/toggle-status',  [CourseController::class, 'toggleStatus']);
    Route::post('courses/{id}/restore',         [CourseController::class, 'restore']);
    Route::delete('courses/{id}/force-delete',  [CourseController::class, 'forceDelete']);
});

/*
|--------------------------------------------------------------------------
| Public Routes (không cần auth)
|--------------------------------------------------------------------------
*/
Route::group([], function () {
    Route::get('courses',                  [CourseController::class, 'publicIndex']);
    Route::get('courses/{slug}',           [CourseController::class, 'publicShow']);
    Route::get('courses/{slug}/lessons',   [CourseController::class, 'publicLessons']);
    Route::get('courses/{slug}/preview-lesson/{lesson_slug}', [CourseController::class, 'publicPreviewLesson']);
});

/*
|--------------------------------------------------------------------------
| Client Routes (auth:api + email.verified — học viên đã đăng nhập và đã kích hoạt)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'email.verified'])->group(function () {
    Route::get('my-courses', [CourseController::class, 'myCourses']);
    Route::post('courses/{slug}/enroll-free', [CourseController::class, 'enrollFree']);
});
