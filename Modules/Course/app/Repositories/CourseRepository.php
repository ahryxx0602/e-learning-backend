<?php

namespace Modules\Course\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Course\Models\Course;

/**
 * Class CourseRepository
 *
 * Eloquent implementation cho CourseRepositoryInterface.
 * Extends BaseRepository (đã có sẵn base methods + clamp perPage, soft-delete support).
 */
class CourseRepository extends BaseRepository implements CourseRepositoryInterface
{
    public function __construct(Course $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->with(['teacher', 'categories'])
            ->latest();

        // Filter theo tên
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Filter theo status
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        // Filter theo teacher_id
        if (!empty($filters['teacher_id'])) {
            $query->where('teacher_id', (int) $filters['teacher_id']);
        }

        // Filter theo category_id (qua pivot)
        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', (int) $filters['category_id']);
            });
        }

        // Filter theo level
        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getPublished(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->published()
            ->with(['teacher', 'categories'])
            ->latest();

        // Tìm kiếm theo tên
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Filter theo category_id (qua pivot)
        if (!empty($filters['category_id'])) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->where('categories.id', (int) $filters['category_id']);
            });
        }

        // Filter theo level
        if (!empty($filters['level'])) {
            $query->where('level', $filters['level']);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlug(string $slug, bool $publishedOnly = false): ?Model
    {
        $query = $this->model->newQuery()
            ->where('slug', $slug)
            ->with(['teacher', 'categories']);

        if ($publishedOnly) {
            $query->published();
        }

        return $query->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getByTeacher(int $teacherId, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        return $this->model->newQuery()
            ->where('teacher_id', $teacherId)
            ->with(['categories'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function toggleStatus(int $id): Model
    {
        $course = $this->model->newQuery()->findOrFail($id);
        $course->update(['status' => $course->status === 1 ? 0 : 1]);
        $course->refresh();
        $course->load(['teacher', 'categories']);

        return $course;
    }

    /**
     * {@inheritDoc}
     */
    public function incrementStudentCount(int $courseId): void
    {
        $this->model->newQuery()->where('id', $courseId)->increment('total_students');
    }

    /**
     * {@inheritDoc}
     */
    public function decrementStudentCount(int $courseId): void
    {
        $this->model->newQuery()->where('id', $courseId)->where('total_students', '>', 0)->decrement('total_students');
    }

    /**
     * {@inheritDoc}
     */
    public function syncCategories(int $courseId, array $categoryIds): void
    {
        $course = $this->model->newQuery()->findOrFail($courseId);
        $course->categories()->sync($categoryIds);
    }

    /**
     * {@inheritDoc}
     */
    public function getByStudent(int $studentId, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        return $this->model->newQuery()
            ->whereHas('students', fn($q) => $q->where('student_id', $studentId))
            ->with(['teacher', 'categories'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findTrashed(int $id): \Illuminate\Database\Eloquent\Model
    {
        return $this->model->newQuery()->withTrashed()->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function findManyTrashed(array $ids): \Illuminate\Database\Eloquent\Collection
    {
        return $this->model->newQuery()->withTrashed()->whereIn('id', $ids)->get();
    }
}
