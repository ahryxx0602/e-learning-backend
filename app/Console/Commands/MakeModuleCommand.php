<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use App\Console\Commands\Generators\ModelGenerator;
use App\Console\Commands\Generators\MigrationGenerator;
use App\Console\Commands\Generators\ControllerGenerator;
use App\Console\Commands\Generators\RequestGenerator;
use App\Console\Commands\Generators\RepositoryGenerator;
use App\Console\Commands\Generators\HelperGenerator;
use App\Console\Commands\Generators\RouteGenerator;
use App\Console\Commands\Generators\ServiceProviderGenerator;
use App\Console\Commands\Generators\ComposerAutoloadGenerator;

/**
 * Custom Artisan Command: make:module
 *
 * Tạo đầy đủ cấu trúc HMVC cho một module mới.
 * Mỗi thành phần được xử lý bởi một Generator class riêng biệt
 * trong namespace App\Console\Commands\Generators.
 *
 * Thành phần được tạo:
 *   - Model (SoftDeletes, HasFactory)
 *   - Migration (create table)
 *   - Controller (API-only, inject Repository, use ApiResponse)
 *   - StoreRequest + UpdateRequest (Form Request validation)
 *   - RepositoryInterface + Repository (extends BaseRepository)
 *   - Helper class (auto-registered)
 *   - Routes (api.php với apiResource)
 *   - ServiceProvider (tự động inject binding + helper)
 *   - composer.json (tự động thêm PSR-4 autoload)
 *
 * Ví dụ:
 *   php artisan make:module Course
 */
class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : Tên module (VD: Course, Category, Teacher)}';

    protected $description = 'Tạo đầy đủ module HMVC: Model, Migration, Controller, Requests, Repository, Helper, routes';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $tableName = Str::snake(Str::plural($name));

        // ── Validate input ──
        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name)) {
            $this->error('Tên module chỉ được chứa chữ cái và số, bắt đầu bằng chữ cái.');
            $this->line('  Ví dụ: php artisan make:module Course');
            return self::FAILURE;
        }

        $modulePath = base_path("Modules/{$name}");

        // ── Kiểm tra module đã tồn tại ──
        if ($this->files->isDirectory($modulePath)) {
            $this->warn("Module [{$name}] đã tồn tại tại: Modules/{$name}");
            if (!$this->confirm('Bạn có muốn tạo lại các file bên trong? (các file đã có sẽ bị ghi đè)', false)) {
                $this->info('Đã huỷ.');
                return self::SUCCESS;
            }
        }
        else {
            // Tạo module base bằng nwidart/laravel-modules
            $this->info("🚀 Đang tạo module [{$name}]...");
            $this->call('module:make', ['name' => [$name]]);
        }

        // ── Chạy tất cả Generators ──
        $this->info('');
        $this->info("📦 Đang generate các thành phần cho module [{$name}]...");

        $generators = [
            new ModelGenerator($this->files, $name, $modulePath),
            new MigrationGenerator($this->files, $name, $modulePath),
            new RepositoryGenerator($this->files, $name, $modulePath),
            new RequestGenerator($this->files, $name, $modulePath),
            new ControllerGenerator($this->files, $name, $modulePath),
            new HelperGenerator($this->files, $name, $modulePath),
            new RouteGenerator($this->files, $name, $modulePath),
            new ServiceProviderGenerator($this->files, $name, $modulePath),
            new ComposerAutoloadGenerator($this->files, $name, $modulePath),
        ];

        foreach ($generators as $generator) {
            $messages = $generator->generate();
            foreach ($messages as $msg) {
                $this->info($msg);
            }
        }

        // ── Tổng kết ──
        $this->newLine();
        $this->info("═══════════════════════════════════════════════════");
        $this->info("      Module [{$name}] đã được tạo đầy đủ!");
        $this->info("═══════════════════════════════════════════════════");

        $this->newLine();
        $this->warn("📌 Việc cần làm tiếp:");
        $this->line("  1. Chỉnh sửa migration: thêm các cột cần thiết cho bảng [{$tableName}]");
        $this->line("  2. Chỉnh sửa Model: thêm \$fillable, \$casts, relationships");
        $this->line("  3. Chỉnh sửa StoreRequest / UpdateRequest: thêm validation rules");
        $this->line("  4. Chạy: composer dump-autoload && php artisan migrate");

        return self::SUCCESS;
    }
}
