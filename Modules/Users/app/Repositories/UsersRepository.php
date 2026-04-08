<?php

namespace Modules\Users\Repositories;

use App\Repositories\BaseRepository;
use Modules\Users\Models\User;

/**
 * Class UsersRepository
 *
 * Eloquent implementation cho UsersRepositoryInterface.
 * Extends BaseRepository (đã có sẵn 9 methods chuẩn + clamp perPage, soft-delete support).
 * Thêm các method riêng cho Users tại đây.
 */
class UsersRepository extends BaseRepository implements UsersRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function assignRoleMany(array $ids, string $role): int
    {
        $users = $this->model->whereIn('id', $ids)->get();

        foreach ($users as $user) {
            $user->syncRoles([$role]);
        }

        return $users->count();
    }
}
