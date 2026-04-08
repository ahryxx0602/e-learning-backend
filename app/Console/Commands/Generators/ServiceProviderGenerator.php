<?php

namespace App\Console\Commands\Generators;

use Illuminate\Support\Str;

class ServiceProviderGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $providerPath = "{$this->modulePath}/app/Providers/{$this->name}ServiceProvider.php";

        // Nếu ServiceProvider chưa tồn tại → tạo mới hoàn chỉnh
        if (!$this->files->exists($providerPath)) {
            $this->putFile($providerPath, $this->getFullStub());
            return ["  ✔ ServiceProvider: Providers/{$this->name}ServiceProvider.php (created)"];
        }

        // ServiceProvider đã tồn tại → inject binding vào register()
        $content = $this->files->get($providerPath);

        // Kiểm tra đã có binding chưa
        if (Str::contains($content, "{$this->name}RepositoryInterface::class")) {
            return ["  ⏭ ServiceProvider: Repository binding đã tồn tại, bỏ qua."];
        }

        $bindingCode = $this->getBindingCode();

        // Tìm method register() và inject binding
        $content = preg_replace(
            '/(public function register\(\): void\s*\{)/s',
            "$1\n{$bindingCode}",
            $content,
            1,
            $count
        );

        if ($count > 0) {
            $this->files->put($providerPath, $content);
            return ["  ✔ ServiceProvider: Đã thêm Repository binding + Helper vào register()"];
        }

        return ["  ⚠ Không tìm thấy method register() trong ServiceProvider. Hãy thêm binding thủ công."];
    }

    /**
     * Code binding để inject vào register().
     */
    protected function getBindingCode(): string
    {
        return <<<CODE
        // ── Repository Binding ──
        \$this->app->bind(
            \\Modules\\{$this->name}\\Repositories\\{$this->name}RepositoryInterface::class,
            \\Modules\\{$this->name}\\Repositories\\{$this->name}Repository::class
        );

        // ── Helper Binding ──
        \$this->app->singleton('{$this->name}Helper', function () {
            return new \\Modules\\{$this->name}\\Helpers\\{$this->name}Helper();
        });

CODE;
    }

    /**
     * Tạo ServiceProvider mới hoàn chỉnh (khi chưa tồn tại).
     */
    protected function getFullStub(): string
    {
        $nameLower = Str::lower($this->name);

        return <<<PHP
<?php

namespace Modules\\{$this->name}\\Providers;

use Illuminate\\Support\\ServiceProvider;

class {$this->name}ServiceProvider extends ServiceProvider
{
    protected string \$name = '{$this->name}';
    protected string \$nameLower = '{$nameLower}';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        \$this->loadMigrationsFrom(module_path(\$this->name, 'database/migrations'));
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        // ── Repository Binding ──
        \$this->app->bind(
            \\Modules\\{$this->name}\\Repositories\\{$this->name}RepositoryInterface::class,
            \\Modules\\{$this->name}\\Repositories\\{$this->name}Repository::class
        );

        // ── Helper Binding ──
        \$this->app->singleton('{$this->name}Helper', function () {
            return new \\Modules\\{$this->name}\\Helpers\\{$this->name}Helper();
        });

        \$this->app->register(\\Modules\\{$this->name}\\Providers\\RouteServiceProvider::class);
    }
}

PHP;
    }
}
