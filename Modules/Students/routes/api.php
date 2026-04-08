<?php

use Illuminate\Support\Facades\Route;
use Modules\Students\Http\Controllers\StudentsController;

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Bulk + static routes phải đặt TRƯỚC apiResource để tránh bị match bởi {student}
    Route::get('students/trashed',              [StudentsController::class, 'trashed']);
    Route::post('students/bulk-restore',        [StudentsController::class, 'bulkRestore']);
    Route::delete('students/bulk-delete',       [StudentsController::class, 'bulkDelete']);
    Route::delete('students/bulk-force-delete', [StudentsController::class, 'bulkForceDelete']);

    Route::apiResource('students', StudentsController::class)->names('admin.students');

    Route::post('students/{id}/restore',        [StudentsController::class, 'restore']);
    Route::delete('students/{id}/force-delete', [StudentsController::class, 'forceDelete']);
});
