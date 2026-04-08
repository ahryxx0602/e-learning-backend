<?php

namespace App\Console\Commands\Generators;

use Illuminate\Support\Str;

class ModelGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $tableName = Str::snake(Str::plural($this->name));
        $path = "{$this->modulePath}/app/Models/{$this->name}.php";

        $content = <<<PHP
<?php

namespace Modules\\{$this->name}\\Models;

use Illuminate\\Database\\Eloquent\\Model;
use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;
use Illuminate\\Database\\Eloquent\\SoftDeletes;

class {$this->name} extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Bảng tương ứng trong database.
     */
    protected \$table = '{$tableName}';

    /**
     * Các cột được phép mass-assign.
     * TODO: Thêm các cột cần thiết.
     */
    protected \$fillable = [
        //
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected \$casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // ── Relationships ──

}

PHP;

        $this->putFile($path, $content);

        return ["  ✔ Model: Models/{$this->name}.php"];
    }
}
