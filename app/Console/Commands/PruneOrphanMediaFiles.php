<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Upload\Models\MediaFile;
use App\Services\UploadService;

class PruneOrphanMediaFiles extends Command
{
    protected $signature   = 'media:prune-orphans {--dry-run}';
    protected $description = 'Xóa media file không được tham chiếu sau 24 giờ';

    public function handle(UploadService $uploadService): void
    {
        $dryRun = $this->option('dry-run');

        // 1. Dọn file pending quá 1 giờ (presigned URL hết hạn, FE không confirm)
        $stalePending = MediaFile::query()
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subHour())
            ->get();

        if ($stalePending->isNotEmpty()) {
            $this->info('── File pending quá hạn (>1 giờ) ──');
            foreach ($stalePending as $file) {
                if (!$dryRun) {
                    $uploadService->delete($file);
                }
                $this->line("  Xóa: [{$file->id}] {$file->original_name} (pending)");
            }
        }

        // 2. Dọn file ready không ai tham chiếu sau 24 giờ
        $orphanReady = MediaFile::query()
            ->where('status', 'ready')
            ->where('reference_count', 0)
            ->where('created_at', '<', now()->subHours(24))
            ->get();

        if ($orphanReady->isNotEmpty()) {
            $this->info('── File ready không tham chiếu (>24 giờ) ──');
            foreach ($orphanReady as $file) {
                if (!$dryRun) {
                    $uploadService->delete($file);
                }
                $this->line("  Xóa: [{$file->id}] {$file->original_name} (orphan)");
            }
        }

        $total = $stalePending->count() + $orphanReady->count();
        $this->info("Tổng: {$total} file." . ($dryRun ? ' (dry-run, chưa xóa thật)' : ''));
    }
}
