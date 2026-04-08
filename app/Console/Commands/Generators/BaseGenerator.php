<?php

namespace App\Console\Commands\Generators;

use Illuminate\Filesystem\Filesystem;

/**
 * Abstract BaseGenerator
 *
 * Lớp cha chung cho tất cả các Generator.
 * Cung cấp các thuộc tính và method dùng chung:
 *   - $files: Filesystem instance để tạo file/thư mục
 *   - $name: Tên module (VD: Course)
 *   - $modulePath: Đường dẫn tuyệt đối đến module (VD: /path/Modules/Course)
 *   - ensureDirectory(): Tạo thư mục nếu chưa có
 *   - putFile(): Ghi nội dung ra file
 *   - generate(): Abstract method — mỗi generator phải implement
 */
abstract class BaseGenerator
{
    protected Filesystem $files;
    protected string $name;
    protected string $modulePath;

    public function __construct(Filesystem $files, string $name, string $modulePath)
    {
        $this->files = $files;
        $this->name = $name;
        $this->modulePath = $modulePath;
    }

    /**
     * Thực thi generate — mỗi class con phải implement.
     *
     * @return array Mảng các message log (info/warn)
     */
    abstract public function generate(): array;

    /**
     * Đảm bảo thư mục tồn tại, tạo nếu chưa có.
     */
    protected function ensureDirectory(string $path): void
    {
        if (!$this->files->isDirectory($path)) {
            $this->files->makeDirectory($path, 0755, true);
        }
    }

    /**
     * Ghi nội dung ra file.
     */
    protected function putFile(string $path, string $content): void
    {
        $this->ensureDirectory(dirname($path));
        $this->files->put($path, $content);
    }
}
