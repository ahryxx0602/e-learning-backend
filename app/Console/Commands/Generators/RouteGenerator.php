<?php

namespace App\Console\Commands\Generators;

use Illuminate\Support\Str;

class RouteGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $routeName = Str::kebab(Str::plural($this->name));
        $path = "{$this->modulePath}/routes/api.php";

        $content = <<<PHP
<?php

use Illuminate\\Support\\Facades\\Route;
use Modules\\{$this->name}\\Http\\Controllers\\{$this->name}Controller;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('{$routeName}', {$this->name}Controller::class)->names('{$routeName}');
});

PHP;

        $this->putFile($path, $content);

        return ["  ✔ Routes: routes/api.php"];
    }
}
