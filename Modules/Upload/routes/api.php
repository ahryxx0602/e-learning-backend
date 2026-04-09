<?php

use Illuminate\Support\Facades\Route;
use Modules\Upload\Http\Controllers\UploadController;

/*
|--------------------------------------------------------------------------
| Admin Routes (auth:admin middleware)
|--------------------------------------------------------------------------
| Local flow: uploadVideo, uploadDocument, uploadImage, destroy
| S3 flow:    presigned, confirm, destroy
*/
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Local flow
    Route::post('upload/video',          [UploadController::class, 'uploadVideo']);
    Route::post('upload/document',       [UploadController::class, 'uploadDocument']);
    Route::post('upload/image',          [UploadController::class, 'uploadImage']);

    // S3 flow
    Route::post('upload/presigned',      [UploadController::class, 'presigned']);
    Route::post('upload/{id}/confirm',   [UploadController::class, 'confirm']);

    // Delete (dùng chung cho cả 2 flow)
    Route::delete('upload/{id}',         [UploadController::class, 'destroy']);
});

// Stream nội dung media — auth được xử lý trong controller (hỗ trợ token qua query param)
Route::get('media/{id}/stream', [UploadController::class, 'stream']);
