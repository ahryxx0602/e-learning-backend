<?php

namespace App\Console\Commands\Generators;

class ComposerAutoloadGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $composerPath = base_path('composer.json');
        $composer = json_decode($this->files->get($composerPath), true);

        if (!is_array($composer)) {
            return ["  ⚠ composer.json không phải JSON hợp lệ. Hãy kiểm tra lại file."];
        }

        $mappings = [
            "Modules\\{$this->name}\\"                    => "Modules/{$this->name}/app/",
            "Modules\\{$this->name}\\Database\\Factories\\" => "Modules/{$this->name}/database/factories/",
            "Modules\\{$this->name}\\Database\\Seeders\\"   => "Modules/{$this->name}/database/seeders/",
        ];

        $changed = false;

        foreach ($mappings as $namespace => $path) {
            if (!isset($composer['autoload']['psr-4'][$namespace])) {
                $composer['autoload']['psr-4'][$namespace] = $path;
                $changed = true;
            }
        }

        if ($changed) {
            $this->files->put(
                $composerPath,
                json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
            );
            return ["  ✔ composer.json: Đã thêm PSR-4 autoload cho module [{$this->name}]"];
        }

        return ["  ⏭ composer.json: Autoload cho [{$this->name}] đã tồn tại, bỏ qua."];
    }
}
