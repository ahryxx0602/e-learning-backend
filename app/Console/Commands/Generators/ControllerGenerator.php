<?php

namespace App\Console\Commands\Generators;

use Illuminate\Support\Str;

class ControllerGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $routeName = Str::kebab(Str::plural($this->name));
        $path = "{$this->modulePath}/app/Http/Controllers/{$this->name}Controller.php";

        $content = <<<PHP
<?php

namespace Modules\\{$this->name}\\Http\\Controllers;

use App\\Http\\Controllers\\Controller;
use App\\Traits\\ApiResponse;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;
use Modules\\{$this->name}\\Http\\Requests\\Store{$this->name}Request;
use Modules\\{$this->name}\\Http\\Requests\\Update{$this->name}Request;
use Modules\\{$this->name}\\Repositories\\{$this->name}RepositoryInterface;

class {$this->name}Controller extends Controller
{
    use ApiResponse;

    protected {$this->name}RepositoryInterface \$repository;

    public function __construct({$this->name}RepositoryInterface \$repository)
    {
        \$this->repository = \$repository;
    }

    /**
     * Danh sách {$this->name} (có phân trang).
     */
    public function index(Request \$request): JsonResponse
    {
        \$perPage = (int) \$request->query('per_page', 15);
        \$data = \$this->repository->paginate(\$perPage);

        return \$this->paginated(\$data);
    }

    /**
     * Tạo mới {$this->name}.
     */
    public function store(Store{$this->name}Request \$request): JsonResponse
    {
        \$data = \$this->repository->create(\$request->validated());

        return \$this->success(\$data, '{$this->name} đã được tạo thành công.', 201);
    }

    /**
     * Chi tiết {$this->name}.
     */
    public function show(int \$id): JsonResponse
    {
        \$data = \$this->repository->findOrFail(\$id);

        return \$this->success(\$data);
    }

    /**
     * Cập nhật {$this->name}.
     */
    public function update(Update{$this->name}Request \$request, int \$id): JsonResponse
    {
        \$data = \$this->repository->update(\$id, \$request->validated());

        return \$this->success(\$data, '{$this->name} đã được cập nhật thành công.');
    }

    /**
     * Xoá {$this->name}.
     */
    public function destroy(int \$id): JsonResponse
    {
        \$this->repository->delete(\$id);

        return \$this->success(null, '{$this->name} đã được xoá thành công.');
    }
}

PHP;

        $this->putFile($path, $content);

        return ["  ✔ Controller: Http/Controllers/{$this->name}Controller.php"];
    }
}
