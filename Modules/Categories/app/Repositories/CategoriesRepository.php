<?php

namespace Modules\Categories\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Modules\Categories\Models\Category;

/**
 * Class CategoriesRepository
 *
 * Eloquent implementation cho CategoriesRepositoryInterface.
 * Extends BaseRepository (đã có sẵn 9 methods chuẩn + clamp perPage, soft-delete support).
 * Thêm các method riêng cho Categories (nested set operations).
 */
class CategoriesRepository extends BaseRepository implements CategoriesRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getTree(bool $activeOnly = false): Collection
    {
        $query = $this->model->newQuery()
            ->defaultOrder();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get()->toTree();
    }

    /**
     * {@inheritDoc}
     */
    public function getFlatTree(bool $activeOnly = false): Collection
    {
        $query = $this->model->newQuery()
            ->defaultOrder()
            ->withDepth();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getAncestors(int $id): Collection
    {
        $category = $this->model->newQuery()->findOrFail($id);

        return $category->ancestors()->defaultOrder()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getDescendants(int $id): Collection
    {
        $category = $this->model->newQuery()->findOrFail($id);

        return $category->descendants()->defaultOrder()->withDepth()->get();
    }

    /**
     * {@inheritDoc}
     */
    public function moveToParent(int $id, ?int $parentId): Model
    {
        $category = $this->model->newQuery()->findOrFail($id);

        if ($parentId === null) {
            // Đưa lên root
            $category->saveAsRoot();
        } else {
            // Di chuyển vào chính nó
            if ($parentId === $id) {
                throw new \InvalidArgumentException('Không thể di chuyển danh mục vào chính nó.');
            }

            $parent = $this->model->newQuery()->findOrFail($parentId);
            // Di chuyển vào con của nó
            if ($parent->isDescendantOf($category)) {
                throw new \InvalidArgumentException('Không thể di chuyển danh mục vào con của nó.');
            }

            $category->appendToNode($parent)->save();
        }

        $category->refresh();

        return $category;
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
    /**
     * {@inheritDoc}
     */
    public function toggleStatus(int $id): Model
    {
        $category = $this->model->newQuery()->findOrFail($id);
        $category->update(['status' => $category->status === 1 ? 0 : 1]);
        $category->refresh();

        return $category;
    }

    /**
     * {@inheritDoc}
     * Hỗ trợ tìm kiếm theo name hoặc slug.
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));
        $search = request()->query('search');

        // Nếu có search, ưu tiên phân trang phẳng để tìm đúng kết quả
        if ($search) {
            return $this->model->newQuery()
                ->with($relations)
                ->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                })
                ->paginate($perPage, $columns);
        }

        // Nếu không search, phân trang theo Danh mục gốc (Roots)
        // Điều này đảm bảo cây danh mục không bị gãy giữa các trang
        $paginator = $this->model->newQuery()
            ->whereIsRoot()
            ->defaultOrder()
            ->paginate($perPage, ['*']); // Cần lấy _lft/_rgt để truy vấn con cháu

        $roots = $paginator->getCollection();

        if ($roots->isEmpty()) {
            return $paginator;
        }

        // Lấy toàn bộ cây (gốc + con cháu) của các gốc đã phân trang
        // Dùng lft/rgt range: con cháu của root có _lft >= root._lft và _rgt <= root._rgt
        $ranges = $roots->map(fn ($r) => ['lft' => $r->_lft, 'rgt' => $r->_rgt]);

        $items = $this->model->newQuery()
            ->with($relations)
            ->withDepth()
            ->defaultOrder()
            ->where(function ($q) use ($ranges) {
                foreach ($ranges as $range) {
                    $q->orWhere(function ($sub) use ($range) {
                        $sub->where('_lft', '>=', $range['lft'])
                            ->where('_rgt', '<=', $range['rgt']);
                    });
                }
            })
            ->get();

        return $paginator->setCollection($items);
    }

    /**
     * {@inheritDoc}
     * Không cho phép xóa danh mục nếu đang có danh mục con.
     */
    public function delete(int $id): bool
    {
        $category = $this->model->newQuery()->withCount('children')->findOrFail($id);

        if ($category->children_count > 0) {
            throw new \RuntimeException('Không thể xóa danh mục đang có danh mục con.');
        }

        return (bool) $category->delete();
    }

    /**
     * {@inheritDoc}
     * Kiểm tra danh mục cha trước khi khôi phục.
     */
    public function restore(int $id): bool
    {
        $category = $this->model->newQuery()->onlyTrashed()->findOrFail($id);

        if ($category->parent_id !== null) {
            // Kiểm tra cha có đang active không (không bị xóa tạm/vĩnh viễn)
            $parentExists = $this->model->newQuery()->withoutTrashed()->find($category->parent_id);
            if (! $parentExists) {
                throw new \RuntimeException('Vui lòng khôi phục danh mục cha trước.');
            }
        }

        return (bool) $category->restore();
    }

    /**
     * {@inheritDoc}
     * Khôi phục nhiều bản ghi, áp dụng validation từng item.
     */
    public function restoreMany(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            try {
                if ($this->restore($id)) {
                    $count++;
                }
            } catch (\RuntimeException) {
                // Skip items that fail validation (e.g. parent still deleted)
            }
        }

        return $count;
    }
}
