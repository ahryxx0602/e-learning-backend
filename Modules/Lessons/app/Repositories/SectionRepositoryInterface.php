<?php

namespace Modules\Lessons\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface SectionRepositoryInterface extends RepositoryInterface
{
    /**
     * Danh sách sections của 1 course (có filter, phân trang).
     */
    public function getByCourse(int $courseId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Toggle trạng thái draft/published (0 ↔ 1).
     */
    public function toggleStatus(int $id): Model;

    /**
     * Cập nhật order hàng loạt.
     *
     * @param  array  $orders  Array of ['id' => int, 'order' => int]
     */
    public function reorder(array $orders): void;
}
