<?php

namespace Modules\Categories\Repositories;

use App\Repositories\BaseRepository;
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
        }
        else {
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
    public function toggleStatus(int $id): Model
    {
        $category = $this->model->newQuery()->findOrFail($id);
        $category->update(['status' => $category->status === 1 ? 0 : 1]);
        $category->refresh();

        return $category;
    }
}
