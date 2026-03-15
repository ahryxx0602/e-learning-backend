<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Custom Artisan Command: make:module-repository
 *
 * Tự động tạo 2 file Repository cho một module nwidart/laravel-modules:
 *   - {Name}RepositoryInterface.php (extends App\Repositories\RepositoryInterface)
 *   - {Name}Repository.php (extends App\Repositories\BaseRepository, implements interface trên)
 *
 * Ví dụ:
 *   php artisan make:module-repository Course Courses
 *
 * Kết quả:
 *   ✔ Modules/Courses/app/Repositories/CourseRepositoryInterface.php
 *   ✔ Modules/Courses/app/Repositories/CourseRepository.php
 */
class MakeModuleRepository extends Command
{
    /**
     * Tên và arguments của command.
     *
     * @var string
     */
    protected $signature = 'make:module-repository
                            {name : Tên Repository (VD: Course, User, Category)}
                            {module : Tên Module (VD: Courses, Users, Categories)}';

    /**
     * Mô tả command.
     *
     * @var string
     */
    protected $description = 'Tạo Repository + Interface cho một module (nwidart/laravel-modules)';

    /**
     * Filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Thực thi command.
     */
    public function handle(): int
    {
        $name   = ucfirst($this->argument('name'));    // Course
        $module = ucfirst($this->argument('module'));   // Courses

        // ── Validate input: chỉ cho phép chữ cái + số, ngăn path traversal ──
        if (! preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name) || ! preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $module)) {
            $this->error('Name và Module chỉ được chứa chữ cái và số, bắt đầu bằng chữ cái.');
            $this->line('  Ví dụ: php artisan make:module-repository Course Courses');
            return self::FAILURE;
        }

        // Xác định đường dẫn thư mục Repositories trong module
        $basePath = base_path("Modules/{$module}/app/Repositories");

        // Kiểm tra module có tồn tại không
        $modulePath = base_path("Modules/{$module}");
        if (! $this->files->isDirectory($modulePath)) {
            $this->error("Module [{$module}] không tồn tại! Hãy tạo module trước:");
            $this->line("  php artisan module:make {$module}");
            return self::FAILURE;
        }

        // Tạo thư mục Repositories nếu chưa có
        if (! $this->files->isDirectory($basePath)) {
            $this->files->makeDirectory($basePath, 0755, true);
        }

        $interfacePath   = "{$basePath}/{$name}RepositoryInterface.php";
        $repositoryPath  = "{$basePath}/{$name}Repository.php";

        // ── Kiểm tra file tồn tại: gộp xác nhận 1 lần duy nhất ──
        $existingFiles = [];
        if ($this->files->exists($interfacePath)) {
            $existingFiles[] = "{$name}RepositoryInterface.php";
        }
        if ($this->files->exists($repositoryPath)) {
            $existingFiles[] = "{$name}Repository.php";
        }

        if (! empty($existingFiles)) {
            $this->warn('Các file sau đã tồn tại:');
            foreach ($existingFiles as $file) {
                $this->line("  - Modules/{$module}/app/Repositories/{$file}");
            }
            if (! $this->confirm('Bạn có muốn ghi đè TẤT CẢ?', false)) {
                $this->info('Đã huỷ. Không có file nào bị thay đổi.');
                return self::SUCCESS;
            }
        }

        // Tạo Interface
        $this->files->put($interfacePath, $this->getInterfaceStub($name, $module));
        $this->info("✔ Created: Modules/{$module}/app/Repositories/{$name}RepositoryInterface.php");

        // Tạo Repository
        $this->files->put($repositoryPath, $this->getRepositoryStub($name, $module));
        $this->info("✔ Created: Modules/{$module}/app/Repositories/{$name}Repository.php");

        $this->newLine();
        $this->info("📌 Nhớ đăng ký binding trong ServiceProvider của module {$module}:");
        $this->line("   \$this->app->bind(");
        $this->line("       \\Modules\\{$module}\\Repositories\\{$name}RepositoryInterface::class,");
        $this->line("       \\Modules\\{$module}\\Repositories\\{$name}Repository::class");
        $this->line("   );");

        return self::SUCCESS;
    }

    /**
     * Tạo nội dung file Interface.
     */
    protected function getInterfaceStub(string $name, string $module): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Repositories;

use App\\Repositories\\RepositoryInterface;

/**
 * Interface {$name}RepositoryInterface
 *
 * Contract cho {$name} Repository trong module {$module}.
 * Extends RepositoryInterface (9 methods chuẩn: getAll, find, findOrFail, create, update, delete, deleteMany, actionMany, paginate).
 * Thêm các method riêng cho {$name} tại đây.
 */
interface {$name}RepositoryInterface extends RepositoryInterface
{
    //
}

PHP;
    }

    /**
     * Tạo nội dung file Repository.
     */
    protected function getRepositoryStub(string $name, string $module): string
    {
        return <<<PHP
<?php

namespace Modules\\{$module}\\Repositories;

use App\\Repositories\\BaseRepository;
use Modules\\{$module}\\Models\\{$name};

/**
 * Class {$name}Repository
 *
 * Eloquent implementation cho {$name}RepositoryInterface.
 * Extends BaseRepository (đã có sẵn 9 methods chuẩn + clamp perPage, soft-delete support).
 * Thêm các method riêng cho {$name} tại đây.
 */
class {$name}Repository extends BaseRepository implements {$name}RepositoryInterface
{
    /**
     * Trả về class của Model mà Repository này quản lý.
     *
     * @return string
     */
    public function getModel(): string
    {
        return {$name}::class;
    }

    /**
     * Constructor — inject Model instance.
     */
    public function __construct({$name} \$model)
    {
        parent::__construct(\$model);
    }
}

PHP;
    }
}
