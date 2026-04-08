<?php

namespace App\Console\Commands\Generators;

class RepositoryGenerator extends BaseGenerator
{
    public function generate(): array
    {
        $messages = [];

        // Interface
        $interfacePath = "{$this->modulePath}/app/Repositories/{$this->name}RepositoryInterface.php";
        $this->putFile($interfacePath, $this->getInterfaceStub());
        $messages[] = "  ✔ RepositoryInterface: Repositories/{$this->name}RepositoryInterface.php";

        // Implementation
        $repoPath = "{$this->modulePath}/app/Repositories/{$this->name}Repository.php";
        $this->putFile($repoPath, $this->getRepositoryStub());
        $messages[] = "  ✔ Repository: Repositories/{$this->name}Repository.php";

        return $messages;
    }

    protected function getInterfaceStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->name}\\Repositories;

use App\\Repositories\\RepositoryInterface;

/**
 * Interface {$this->name}RepositoryInterface
 *
 * Contract cho {$this->name} Repository.
 * Extends RepositoryInterface (9 methods chuẩn: getAll, find, findOrFail, create, update, delete, deleteMany, actionMany, paginate).
 * Thêm các method riêng cho {$this->name} tại đây.
 */
interface {$this->name}RepositoryInterface extends RepositoryInterface
{
    //
}

PHP;
    }

    protected function getRepositoryStub(): string
    {
        return <<<PHP
<?php

namespace Modules\\{$this->name}\\Repositories;

use App\\Repositories\\BaseRepository;
use Modules\\{$this->name}\\Models\\{$this->name};

/**
 * Class {$this->name}Repository
 *
 * Eloquent implementation cho {$this->name}RepositoryInterface.
 * Extends BaseRepository (đã có sẵn 9 methods chuẩn + clamp perPage, soft-delete support).
 * Thêm các method riêng cho {$this->name} tại đây.
 */
class {$this->name}Repository extends BaseRepository implements {$this->name}RepositoryInterface
{
    public function __construct({$this->name} \$model)
    {
        parent::__construct(\$model);
    }
}

PHP;
    }
}
