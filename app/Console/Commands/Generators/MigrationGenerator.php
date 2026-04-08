<?php

namespace App\Console\Commands\Generators;

use Illuminate\Support\Str;

class MigrationGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $tableName = Str::snake(Str::plural($this->name));
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_create_{$tableName}_table.php";
        $path = "{$this->modulePath}/database/migrations/{$filename}";

        $content = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{$tableName}', function (Blueprint \$table) {
            \$table->id();

            // TODO: Thêm các cột cần thiết

            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{$tableName}');
    }
};

PHP;

        $this->putFile($path, $content);

        return ["  ✔ Migration: database/migrations/{$filename}"];
    }
}
