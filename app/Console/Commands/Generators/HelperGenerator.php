<?php

namespace App\Console\Commands\Generators;

class HelperGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $path = "{$this->modulePath}/app/Helpers/{$this->name}Helper.php";

        $content = <<<PHP
<?php

namespace Modules\\{$this->name}\\Helpers;

/**
 * Class {$this->name}Helper
 *
 * Chứa các hàm tiện ích dùng riêng cho module {$this->name}.
 * Được auto-load thông qua ServiceProvider.
 */
class {$this->name}Helper
{
    /**
     * Ví dụ: format dữ liệu {$this->name}.
     * TODO: Thêm các helper methods cần thiết.
     */
    public static function format{$this->name}Data(array \$data): array
    {
        return \$data;
    }
}

PHP;

        $this->putFile($path, $content);

        return ["  ✔ Helper: Helpers/{$this->name}Helper.php"];
    }
}
