<?php

use Illuminate\Support\Facades\Route;
use Modules\Lessons\Http\Controllers\LessonController;
use Modules\Lessons\Http\Controllers\SectionController;

// ── Admin routes ──────────────────────────────────────────
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {

    // ── Sections ──
    Route::get('sections/trashed',                    [SectionController::class, 'trashed']);
    Route::post('sections/reorder',                   [SectionController::class, 'reorder']);

    Route::post('sections/bulk-action',               [SectionController::class, 'bulkAction']);
    Route::delete('sections/bulk-delete',             [SectionController::class, 'bulkDelete']);
    Route::post('sections/bulk-restore',              [SectionController::class, 'bulkRestore']);
    Route::delete('sections/bulk-force-delete',       [SectionController::class, 'bulkForceDelete']);

    Route::get('courses/{course_id}/sections',        [SectionController::class, 'index']);
    Route::post('courses/{course_id}/sections',       [SectionController::class, 'store']);

    Route::get('sections/{id}',                       [SectionController::class, 'show']);
    Route::put('sections/{id}',                       [SectionController::class, 'update']);
    Route::patch('sections/{id}',                     [SectionController::class, 'update']);
    Route::delete('sections/{id}',                    [SectionController::class, 'destroy']);
    Route::patch('sections/{id}/toggle-status',       [SectionController::class, 'toggleStatus']);
    Route::post('sections/{id}/restore',              [SectionController::class, 'restore']);
    Route::delete('sections/{id}/force-delete',       [SectionController::class, 'forceDelete']);

    // ── Lessons ──
    Route::get('lessons/trashed',                     [LessonController::class, 'trashed']);
    Route::post('lessons/reorder',                    [LessonController::class, 'reorder']);

    Route::post('lessons/bulk-action',                [LessonController::class, 'bulkAction']);
    Route::delete('lessons/bulk-delete',              [LessonController::class, 'bulkDelete']);
    Route::post('lessons/bulk-restore',               [LessonController::class, 'bulkRestore']);
    Route::delete('lessons/bulk-force-delete',        [LessonController::class, 'bulkForceDelete']);

    // Nested: lessons thuộc course (section_id optional trong body)
    Route::get('courses/{course_id}/lessons',         [LessonController::class, 'index']);
    Route::post('courses/{course_id}/lessons',        [LessonController::class, 'store']);

    Route::get('lessons/{id}',                        [LessonController::class, 'show']);
    Route::put('lessons/{id}',                        [LessonController::class, 'update']);
    Route::patch('lessons/{id}',                      [LessonController::class, 'update']);
    Route::delete('lessons/{id}',                     [LessonController::class, 'destroy']);
    Route::patch('lessons/{id}/toggle-status',        [LessonController::class, 'toggleStatus']);
    Route::post('lessons/{id}/restore',               [LessonController::class, 'restore']);
    Route::delete('lessons/{id}/force-delete',        [LessonController::class, 'forceDelete']);
});

// ── Client routes (auth:api + email.verified — học viên đã đăng nhập và đã kích hoạt) ──
Route::middleware(['auth:api', 'email.verified'])->group(function () {
    Route::get('my-courses/{slug}/lessons',           [LessonController::class, 'myLessons']);
    Route::get('my-courses/{slug}/lessons/{lesson_slug}', [LessonController::class, 'myLessonDetail']);
    Route::post('lessons/{id}/progress',              [LessonController::class, 'updateProgress']);
    Route::get('courses/{slug}/progress',             [LessonController::class, 'courseProgress']);
});

// ── Public routes (không cần auth) ────────────────────────
Route::get('courses/{slug}/curriculum',               [SectionController::class, 'curriculum']);
