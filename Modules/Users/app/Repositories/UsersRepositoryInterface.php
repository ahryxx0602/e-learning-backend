<?php

namespace Modules\Users\Repositories;

use App\Repositories\RepositoryInterface;

/**
 * Interface UsersRepositoryInterface
 *
 * Contract cho Users Repository.
 * Extends RepositoryInterface (9 methods chuẩn: getAll, find, findOrFail, create, update, delete, deleteMany, actionMany, paginate).
 * Thêm các method riêng cho Users tại đây.
 */
interface UsersRepositoryInterface extends RepositoryInterface
{
    /**
     * Gán role cho nhiều users cùng lúc.
     *
     * @param  array<int>  $ids
     * @param  string      $role
     * @return int  Số lượng user được cập nhật
     */
    public function assignRoleMany(array $ids, string $role): int;
}
