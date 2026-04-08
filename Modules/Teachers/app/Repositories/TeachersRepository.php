<?php

namespace Modules\Teachers\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Teachers\Models\Teachers;

/**
 * Class TeachersRepository
 *
 * Eloquent implementation cho TeachersRepositoryInterface.
 * Extends BaseRepository (đã có sẵn base methods + clamp perPage, soft-delete support).
 * Thêm các method riêng cho Teachers.
 */
class TeachersRepository extends BaseRepository implements TeachersRepositoryInterface
{
    public function __construct(Teachers $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function findBySlug(string $slug, bool $activeOnly = false): ?Model
    {
        $query = $this->model->newQuery()->where('slug', $slug);

        if ($activeOnly) {
            $query->active();
        }

        return $query->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()->latest();

        // Filter theo tên
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Filter theo status
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        $query = $this->model->newQuery()
            ->active()
            ->latest();

        // Tìm kiếm theo tên
        if (!empty($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function toggleStatus(int $id): Model
    {
        $teacher = $this->model->newQuery()->findOrFail($id);
        $teacher->update(['status' => $teacher->status === 1 ? 0 : 1]);
        $teacher->refresh();

        return $teacher;
    }
}
