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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;
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

    // ── Stream Auth Helper ──

    /**
     * Xác thực request stream: chấp nhận token từ Bearer header hoặc query param ?token=
     * Cần thiết vì <video src> của browser không gửi Authorization header.
     */
    private function authorizeStreamRequest(Request $request): void
    {
        // Ưu tiên guard đã được resolve bởi middleware (trường hợp gọi qua axios)
        if (Auth::guard('admin')->check() || Auth::guard('api')->check()) {
            return;
        }

        // Fallback: đọc token từ query param ?token= (dành cho <video src="...?token=...">)
        $rawToken = $request->query('token');
        if (!$rawToken) {
            abort(401, 'Unauthenticated.');
        }

        $accessToken = PersonalAccessToken::findToken($rawToken);
        if (!$accessToken || ($accessToken->expires_at && $accessToken->expires_at->isPast())) {
            abort(401, 'Token không hợp lệ hoặc đã hết hạn.');
        }
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

    // ── Stream (hỗ trợ HTTP 206 Range, Cache, S3 presigned) ──
    public function stream(int $id, Request $request)
    {
        // <video src="..."> không gửi được Authorization header nên hỗ trợ token qua query param.
        // Sanctum đã resolve token từ header trước khi vào controller nên cần validate thủ công.
        $this->authorizeStreamRequest($request);

        $mediaFile = MediaFile::findOrFail($id);

        // S3: trả về presigned URL tạm thời thay vì redirect thẳng public URL
        if ($mediaFile->disk !== 'local' && $mediaFile->disk !== 'public') {
            $presignedUrl = \Illuminate\Support\Facades\Storage::disk($mediaFile->disk)
                ->temporaryUrl($mediaFile->path, now()->addMinutes(60));
            return redirect()->away($presignedUrl);
        }

        $disk     = \Illuminate\Support\Facades\Storage::disk($mediaFile->disk);
        $fullPath = $disk->path($mediaFile->path);

        if (!file_exists($fullPath)) {
            abort(404, 'File gốc không tồn tại trên hệ thống.');
        }

        $mimeType    = $mediaFile->mime_type ?? 'video/mp4';
        $fileSize    = filesize($fullPath);
        $lastModified = filemtime($fullPath);
        $etag        = md5($mediaFile->id . '-' . $lastModified . '-' . $fileSize);

        // Cache validation: 304 Not Modified
        if (
            $request->header('If-None-Match') === '"' . $etag . '"' ||
            $request->header('If-Modified-Since') === gmdate('D, d M Y H:i:s', $lastModified) . ' GMT'
        ) {
            return response('', 304, [
                'ETag'          => '"' . $etag . '"',
                'Last-Modified' => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            ]);
        }

        // HTTP Range request (206 Partial Content) — hỗ trợ seek video
        $rangeHeader = $request->header('Range');
        if ($rangeHeader && preg_match('/bytes=(\d*)-(\d*)/i', $rangeHeader, $matches)) {
            $start = $matches[1] !== '' ? (int) $matches[1] : 0;
            $end   = $matches[2] !== '' ? (int) $matches[2] : $fileSize - 1;

            $end   = min($end, $fileSize - 1);
            $start = max(0, $start);

            if ($start > $end) {
                return response('', 416, ['Content-Range' => 'bytes */' . $fileSize]);
            }

            $length = $end - $start + 1;

            $stream = fopen($fullPath, 'rb');
            fseek($stream, $start);

            return response()->stream(
                function () use ($stream, $length) {
                    $remaining = $length;
                    $chunkSize = 1024 * 256; // 256 KB
                    while (!feof($stream) && $remaining > 0) {
                        $read = min($chunkSize, $remaining);
                        echo fread($stream, $read);
                        $remaining -= $read;
                        flush();
                    }
                    fclose($stream);
                },
                206,
                [
                    'Content-Type'   => $mimeType,
                    'Content-Range'  => "bytes {$start}-{$end}/{$fileSize}",
                    'Content-Length' => $length,
                    'Accept-Ranges'  => 'bytes',
                    'Cache-Control'  => 'public, max-age=3600',
                    'ETag'           => '"' . $etag . '"',
                    'Last-Modified'  => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
                ]
            );
        }

        // Full file response (200)
        $stream = fopen($fullPath, 'rb');
        return response()->stream(
            function () use ($stream) {
                $chunkSize = 1024 * 256; // 256 KB
                while (!feof($stream)) {
                    echo fread($stream, $chunkSize);
                    flush();
                }
                fclose($stream);
            },
            200,
            [
                'Content-Type'   => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges'  => 'bytes',
                'Cache-Control'  => 'public, max-age=3600',
                'ETag'           => '"' . $etag . '"',
                'Last-Modified'  => gmdate('D, d M Y H:i:s', $lastModified) . ' GMT',
            ]
        );
    }
}
