<?php

namespace Modules\Lessons\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Lessons\Models\Lesson;

/**
 * Class LessonRepository
 *
 * Eloquent implementation cho LessonRepositoryInterface.
 * Extends BaseRepository (đã có sẵn base methods + clamp perPage, soft-delete support).
 */
class LessonRepository extends BaseRepository implements LessonRepositoryInterface
{
    public function __construct(Lesson $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getByCourse(int $courseId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->where('course_id', $courseId)
            ->ordered();

        // Filter theo status
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        // Filter theo type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getPublishedByCourse(int $courseId): Collection
    {
        return $this->model->newQuery()
            ->where('course_id', $courseId)
            ->published()
            ->ordered()
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlug(string $slug): ?Model
    {
        return $this->model->newQuery()
            ->where('slug', $slug)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function toggleStatus(int $id): Model
    {
        $lesson = $this->model->newQuery()->findOrFail($id);
        $lesson->update(['status' => $lesson->status === 1 ? 0 : 1]);
        $lesson->refresh();

        return $lesson;
    }

    /**
     * {@inheritDoc}
     */
    public function reorder(array $orders): void
    {
        DB::transaction(function () use ($orders) {
            foreach ($orders as $item) {
                Lesson::where('id', $item['id'])->update(['order' => $item['order']]);
            }
        });
    }
}
