<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Class BaseRepository
 *
 * Implementation mặc định của RepositoryInterface sử dụng Eloquent.
 * Các module Repository chỉ cần extend class này và truyền Model vào constructor.
 *
 * Ví dụ trong module:
 *   class CourseRepository extends BaseRepository {
 *       public function __construct(Course $model) {
 *           parent::__construct($model);
 *       }
 *   }
 */
class BaseRepository implements RepositoryInterface
{
    /**
     * Giới hạn số bản ghi tối đa mỗi trang.
     * Tránh client truyền per_page=999999 gây quá tải.
     */
    protected const MAX_PER_PAGE = 100;

    /**
     * Eloquent Model instance.
     *
     * @var Model
     */
    protected Model $model;

    /**
     * BaseRepository constructor.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->newQuery()
            ->with($relations)
            ->get($columns);
    }

    /**
     * {@inheritDoc}
     */
    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->newQuery()
            ->select($columns)
            ->with($relations)
            ->find($id);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ModelNotFoundException
     */
    public function findOrFail(int $id, array $columns = ['*'], array $relations = []): Model
    {
        return $this->model->newQuery()
            ->select($columns)
            ->with($relations)
            ->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Model
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ModelNotFoundException
     */
    public function update(int $id, array $data): Model
    {
        $record = $this->model->newQuery()->findOrFail($id);
        $record->update($data);
        $record->refresh();

        return $record;
    }

    /**
     * {@inheritDoc}
     *
     * Hỗ trợ cả SoftDeletes: nếu Model dùng SoftDeletes thì soft-delete,
     * nếu không thì hard-delete. Luôn trả bool nhất quán.
     *
     * @throws ModelNotFoundException
     */
    public function delete(int $id): bool
    {
        $record = $this->model->newQuery()->findOrFail($id);

        // delete() trả về bool|null — ép kiểu để nhất quán return type
        return (bool) $record->delete();
    }

    /**
     * {@inheritDoc}
     *
     * Xoá nhiều bản ghi. Nếu Model dùng SoftDeletes thì soft-delete hàng loạt.
     * Dùng Eloquent query (không phải mass delete) để trigger Model events.
     */
    public function deleteMany(array $ids): int
    {
        $count = 0;

        // Dùng chunk qua từng record để trigger model events (deleting/deleted)
        $records = $this->model->newQuery()->whereIn('id', $ids)->get();

        foreach ($records as $record) {
            if ($record->delete()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * {@inheritDoc}
     *
     * Force delete một bản ghi (xoá vĩnh viễn khỏi DB).
     * Bao gồm cả bản ghi đã soft-delete và chưa soft-delete.
     *
     * @throws ModelNotFoundException
     */
    public function forceDeleteById(int $id): bool
    {
        $record = $this->model->newQuery()->withTrashed()->findOrFail($id);

        return (bool) $record->forceDelete();
    }

    /**
     * {@inheritDoc}
     *
     * Force delete nhiều bản ghi (xoá vĩnh viễn khỏi DB).
     * Bao gồm cả bản ghi đã soft-delete và chưa soft-delete.
     */
    public function forceDeleteMany(array $ids): int
    {
        $count = 0;

        $records = $this->model->newQuery()->withTrashed()->whereIn('id', $ids)->get();

        foreach ($records as $record) {
            if ($record->forceDelete()) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * {@inheritDoc}
     *
     * Lấy danh sách bản ghi đã soft-delete, phân trang.
     */
    public function paginateTrashed(int $perPage = 15, array $columns = ['*'], array $relations = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        return $this->model->newQuery()
            ->onlyTrashed()
            ->with($relations)
            ->paginate($perPage, $columns);
    }

    /**
     * {@inheritDoc}
     *
     * Khôi phục một bản ghi đã soft-delete.
     *
     * @throws ModelNotFoundException
     */
    public function restore(int $id): bool
    {
        $record = $this->model->newQuery()->onlyTrashed()->findOrFail($id);

        return (bool) $record->restore();
    }

    /**
     * {@inheritDoc}
     *
     * Khôi phục nhiều bản ghi đã soft-delete.
     */
    public function restoreMany(array $ids): int
    {
        return $this->model->newQuery()
            ->onlyTrashed()
            ->whereIn('id', $ids)
            ->restore();
    }

    /**
     * {@inheritDoc}
     *
     * Mapping action → field update. Module con có thể override để thêm action.
     * Các action mặc định: activate, deactivate, publish, unpublish, archive.
     *
     * Không cho phép unknown action để tránh mass-update tùy tiện.
     * Module con muốn thêm action → override method này và gọi parent::actionMany() làm fallback.
     *
     * @throws \InvalidArgumentException Khi action không được hỗ trợ
     */
    public function actionMany(array $ids, string $action): int
    {
        $updateData = match ($action) {
            'activate'   => ['status' => 1],
            'deactivate' => ['status' => 0],
            'publish'    => ['status' => 1],
            'unpublish'  => ['status' => 0],
            'archive'    => ['status' => -1],
            default      => throw new \InvalidArgumentException("Unsupported action: {$action}"),
        };

        return $this->model->newQuery()
            ->whereIn('id', $ids)
            ->update($updateData);
    }

    /**
     * {@inheritDoc}
     *
     * $perPage được clamp trong khoảng [1, MAX_PER_PAGE] để tránh abuse.
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        // Clamp: đảm bảo 1 <= perPage <= MAX_PER_PAGE
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        return $this->model->newQuery()
            ->with($relations)
            ->paginate($perPage, $columns);
    }
}
