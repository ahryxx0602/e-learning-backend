<?php

namespace Modules\Lessons\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Interface LessonRepositoryInterface
 *
 * Contract cho Lesson Repository.
 * Extends RepositoryInterface (base methods chuẩn).
 * Thêm các method riêng cho Lesson.
 */
interface LessonRepositoryInterface extends RepositoryInterface
{
    /**
     * Danh sách lessons của 1 course (Admin — có filter).
     *
     * @param  int    $courseId
     * @param  array  $filters   Filter theo status, type
     * @param  int    $perPage
     * @return LengthAwarePaginator
     */
    public function getByCourse(int $courseId, array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Danh sách lessons published của 1 course (Public/Client).
     * Trả Collection (không phân trang).
     *
     * @param  int  $courseId
     * @return Collection
     */
    public function getPublishedByCourse(int $courseId): Collection;

    /**
     * Tìm lesson theo slug.
     *
     * @param  string  $slug
     * @return Model|null
     */
    public function findBySlug(string $slug): ?Model;

    /**
     * Toggle trạng thái draft/published (0 ↔ 1).
     *
     * @param  int  $id
     * @return Model
     */
    public function toggleStatus(int $id): Model;

    /**
     * Cập nhật order hàng loạt.
     *
     * @param  array  $orders  Array of ['id' => int, 'order' => int]
     * @return void
     */
    public function reorder(array $orders): void;
}
