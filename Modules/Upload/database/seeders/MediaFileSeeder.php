<?php

namespace Modules\Upload\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Upload\Models\MediaFile;

class MediaFileSeeder extends Seeder
{
    public function run(): void
    {
        // ── Videos ──
        $videos = [
            [
                'disk'            => 'public',
                'type'            => 'video',
                'original_name'   => 'video-1.mp4',
                'path'            => 'seed/videos/video-1.mp4',
                'url'             => '/storage/seed/videos/video-1.mp4',
                'mime_type'       => 'video/mp4',
                'size'            => $this->fileSize('seed/videos/video-1.mp4'),
                'status'          => 1,
                'reference_count' => 0,
                'duration'        => 720,  // 12 phút
                'codec'           => 'h264',
                'uploaded_by'     => 1,
            ],
            [
                'disk'            => 'public',
                'type'            => 'video',
                'original_name'   => 'video-2.mp4',
                'path'            => 'seed/videos/video-2.mp4',
                'url'             => '/storage/seed/videos/video-2.mp4',
                'mime_type'       => 'video/mp4',
                'size'            => $this->fileSize('seed/videos/video-2.mp4'),
                'status'          => 1,
                'reference_count' => 0,
                'duration'        => 540,  // 9 phút
                'codec'           => 'h264',
                'uploaded_by'     => 1,
            ],
            [
                'disk'            => 'public',
                'type'            => 'video',
                'original_name'   => 'video-3.mp4',
                'path'            => 'seed/videos/video-3.mp4',
                'url'             => '/storage/seed/videos/video-3.mp4',
                'mime_type'       => 'video/mp4',
                'size'            => $this->fileSize('seed/videos/video-3.mp4'),
                'status'          => 1,
                'reference_count' => 0,
                'duration'        => 900,  // 15 phút
                'codec'           => 'h264',
                'uploaded_by'     => 1,
            ],
        ];

        foreach ($videos as $video) {
            MediaFile::create($video);
        }

        // ── Documents ──
        $documents = [
            ['original_name' => 'document-1.pdf', 'path' => 'seed/documents/document-1.pdf'],
            ['original_name' => 'document-2.pdf', 'path' => 'seed/documents/document-2.pdf'],
            ['original_name' => 'document-3.pdf', 'path' => 'seed/documents/document-3.pdf'],
            ['original_name' => 'document-4.pdf', 'path' => 'seed/documents/document-4.pdf'],
            ['original_name' => 'document-5.pdf', 'path' => 'seed/documents/document-5.pdf'],
            ['original_name' => 'document-6.pdf', 'path' => 'seed/documents/document-6.pdf'],
            ['original_name' => 'document-7.pdf', 'path' => 'seed/documents/document-7.pdf'],
        ];

        foreach ($documents as $doc) {
            MediaFile::create([
                'disk'            => 'public',
                'type'            => 'document',
                'original_name'   => $doc['original_name'],
                'path'            => $doc['path'],
                'url'             => '/storage/' . $doc['path'],
                'mime_type'       => 'application/pdf',
                'size'            => $this->fileSize($doc['path']),
                'status'          => 1,
                'reference_count' => 0,
                'uploaded_by'     => 1,
            ]);
        }
    }

    private function fileSize(string $relativePath): int
    {
        $fullPath = storage_path('app/public/' . $relativePath);
        return file_exists($fullPath) ? filesize($fullPath) : 0;
    }
}
