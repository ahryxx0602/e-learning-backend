<?php

namespace Modules\Teachers\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface TeachersRepositoryInterface
 *
 * Contract cho Teachers Repository.
 * Extends RepositoryInterface (base methods chuẩn).
 * Thêm các method riêng cho Teachers.
 */
interface TeachersRepositoryInterface extends RepositoryInterface
{
    /**
     * Tìm teacher theo slug.
     * Chỉ lấy active nếu $activeOnly = true.
     */
    public function findBySlug(string $slug, bool $activeOnly = false): ?Model;

    /**
     * Danh sách teachers (phân trang) có filter theo tên, status (Admin).
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Danh sách teachers active (public, phân trang).
     */
    public function getPublicList(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Toggle trạng thái active/inactive.
     */
    public function toggleStatus(int $id): Model;
}
