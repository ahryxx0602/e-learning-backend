<?php

namespace Modules\Upload\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Services\UploadService;
use Modules\Upload\Models\MediaFile;
use Modules\Upload\Resources\MediaFileResource;
use Modules\Upload\Http\Requests\UploadVideoRequest;
use Modules\Upload\Http\Requests\UploadDocumentRequest;
use Modules\Upload\Http\Requests\PresignedUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class UploadController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected UploadService $uploadService
    ) {}

    // ── Local flow ──

    public function uploadVideo(UploadVideoRequest $request): JsonResponse
    {
        $mediaFile = $this->uploadService->uploadVideo(
            $request->file('file'),
            auth('admin')->id()
        );

        return $this->success(
            new MediaFileResource($mediaFile),
            'Upload video thành công.',
            201
        );
    }

    public function uploadDocument(UploadDocumentRequest $request): JsonResponse
    {
        $mediaFile = $this->uploadService->uploadDocument(
            $request->file('file'),
            auth('admin')->id()
        );

        return $this->success(
            new MediaFileResource($mediaFile),
            'Upload tài liệu thành công.',
            201
        );
    }

    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'file'   => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'folder' => 'nullable|string|in:images,thumbnails,avatars,banners',
        ]);

        $folder = $request->input('folder', 'images');

        $mediaFile = $this->uploadService->uploadImage(
            $request->file('file'),
            $folder,
            auth('admin')->id()
        );

        return $this->success(
            new MediaFileResource($mediaFile),
            'Upload hình ảnh thành công.',
            201
        );
    }

    // ── S3 flow ──

    public function presigned(PresignedUploadRequest $request): JsonResponse
    {
        $videoMeta = [];
        if ($request->type === 'video') {
            $videoMeta = array_filter([
                'duration' => $request->duration,
                'width'    => $request->width,
                'height'   => $request->height,
            ]);
        }

        $result = $this->uploadService->generatePresigned(
            $request->type,
            $request->filename,
            $request->mime_type,
            $request->size,
            $videoMeta,
            auth('admin')->id()
        );

        return $this->success($result, 'Presigned URL đã được tạo.');
    }

    public function confirm(int $id): JsonResponse
    {
        $mediaFile = MediaFile::where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        try {
            $mediaFile = $this->uploadService->confirmUpload($mediaFile);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(
            new MediaFileResource($mediaFile),
            'Xác nhận upload thành công.'
        );
    }

    // ── Delete ──

    public function destroy(int $id): JsonResponse
    {
        $mediaFile = MediaFile::findOrFail($id);

        if ($mediaFile->reference_count > 0) {
            return $this->error(
                "Không thể xóa: file đang được dùng bởi {$mediaFile->reference_count} bài giảng.",
                422
            );
        }

        Log::info('Admin xóa media file', [
            'media_file_id' => $mediaFile->id,
            'original_name' => $mediaFile->original_name,
            'deleted_by'    => auth('admin')->id(),
            'uploaded_by'   => $mediaFile->uploaded_by,
        ]);

        $this->uploadService->delete($mediaFile);

        return $this->success(null, 'Đã xóa file thành công.');
    }
}
