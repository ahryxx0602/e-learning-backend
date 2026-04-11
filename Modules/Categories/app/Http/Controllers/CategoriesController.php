<?php

namespace Modules\Categories\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Categories\Http\Requests\BulkDeleteCategoriesRequest;
use Modules\Categories\Http\Requests\BulkForceDeleteCategoriesRequest;
use Modules\Categories\Http\Requests\BulkRestoreCategoriesRequest;
use Modules\Categories\Http\Requests\MoveCategoryRequest;
use Modules\Categories\Http\Requests\StoreCategoriesRequest;
use Modules\Categories\Http\Requests\UpdateCategoriesRequest;
use Modules\Categories\Http\Resources\CategoryResource;
use Modules\Categories\Repositories\CategoriesRepositoryInterface;

class CategoriesController extends Controller
{
    use ApiResponse;

    protected CategoriesRepositoryInterface $repository;

    public function __construct(CategoriesRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ── Admin CRUD ──

    /**
     * Danh sách Categories (có phân trang, dạng flat).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginate($perPage);
        $data->setCollection(CategoryResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Lấy toàn bộ cây Category dạng nested (Admin).
     */
    public function tree(): JsonResponse
    {
        $tree = $this->repository->getTree();

        return $this->success(CategoryResource::collection($tree), 'Lấy cây danh mục thành công.');
    }

    /**
     * Lấy danh sách flat (có depth) cho dropdown chọn parent.
     */
    public function flatTree(): JsonResponse
    {
        $categories = $this->repository->getFlatTree();

        return $this->success(CategoryResource::collection($categories), 'Lấy danh sách danh mục thành công.');
    }

    /**
     * Tạo mới Category.
     */
    public function store(StoreCategoriesRequest $request): JsonResponse
    {
        $data = $request->validated();

        $category = $this->repository->create($data);
        $category->refresh();

        return $this->success(new CategoryResource($category), 'Danh mục đã được tạo thành công.', 201);
    }

    /**
     * Chi tiết Category (kèm ancestors cho breadcrumb).
     */
    public function show(int $id): JsonResponse
    {
        $category = $this->repository->findOrFail($id);
        $category->load('ancestors', 'children');

        return $this->success(new CategoryResource($category));
    }

    /**
     * Cập nhật Category.
     */
    public function update(UpdateCategoriesRequest $request, int $id): JsonResponse
    {
        $category = $this->repository->update($id, $request->validated());

        return $this->success(new CategoryResource($category), 'Danh mục đã được cập nhật thành công.');
    }

    /**
     * Xoá Category (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->repository->delete($id);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success(null, 'Danh mục đã được xoá thành công.');
    }

    // ── Nested Set Operations ──

    /**
     * Di chuyển Category sang parent mới (hoặc lên root).
     */
    public function move(MoveCategoryRequest $request, int $id): JsonResponse
    {
        try {
            $category = $this->repository->moveToParent($id, $request->parent_id);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new CategoryResource($category), 'Đã di chuyển danh mục thành công.');
    }

    /**
     * Lấy danh sách ancestors (tổ tiên / breadcrumb).
     */
    public function ancestors(int $id): JsonResponse
    {
        $ancestors = $this->repository->getAncestors($id);

        return $this->success(CategoryResource::collection($ancestors));
    }

    /**
     * Lấy danh sách descendants (con cháu).
     */
    public function descendants(int $id): JsonResponse
    {
        $descendants = $this->repository->getDescendants($id);

        return $this->success(CategoryResource::collection($descendants));
    }

    /**
     * Toggle trạng thái active/inactive.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $category = $this->repository->toggleStatus($id);

        $statusText = $category->status === 1 ? 'kích hoạt' : 'vô hiệu hoá';

        return $this->success(new CategoryResource($category), "Danh mục đã được {$statusText}.");
    }

    // ── Soft Delete Operations ──

    /**
     * Danh sách Categories đã bị soft-delete (thùng rác).
     */
    public function trashed(Request $request): JsonResponse
    {
        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage);
        $data->setCollection(CategoryResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Khôi phục một Category đã soft-delete.
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $this->repository->restore($id);
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 400);
        }

        return $this->success(null, 'Danh mục đã được khôi phục thành công.');
    }

    /**
     * Xoá vĩnh viễn một Category.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'Danh mục đã bị xoá vĩnh viễn.');
    }

    // ── Bulk Operations ──

    /**
     * Xoá nhiều Categories (soft delete).
     */
    public function bulkDelete(BulkDeleteCategoriesRequest $request): JsonResponse
    {
        $deleted = $this->repository->deleteMany($request->ids);

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá {$deleted} danh mục thành công."
        );
    }

    /**
     * Khôi phục nhiều Categories đã soft-delete.
     */
    public function bulkRestore(BulkRestoreCategoriesRequest $request): JsonResponse
    {
        $restored = $this->repository->restoreMany($request->ids);

        return $this->success(
            ['restored_count' => $restored, 'restored_ids' => $request->ids],
            "Đã khôi phục {$restored} danh mục thành công."
        );
    }

    /**
     * Xoá vĩnh viễn nhiều Categories.
     */
    public function bulkForceDelete(BulkForceDeleteCategoriesRequest $request): JsonResponse
    {
        $deleted = $this->repository->forceDeleteMany($request->ids);

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá vĩnh viễn {$deleted} danh mục."
        );
    }

    // ── Public API ──

    /**
     * Public: Lấy danh sách flat mở rộng.
     */
    public function publicIndex(): JsonResponse
    {
        // Lấy danh sách category_id đang có course published
        $hasCourseIds = DB::table('categories_courses')
            ->join('courses', 'courses.id', '=', 'categories_courses.course_id')
            ->where('courses.status', 1)
            ->whereNull('courses.deleted_at')
            ->pluck('categories_courses.category_id')
            ->unique()
            ->toArray();

        // Lấy tất cả danh mục đang active để lọc.
        $categories = $this->repository->getFlatTree(true);

        $validIds = [];
        foreach ($categories as $cat) {
            $hasCourse = false;
            foreach ($hasCourseIds as $cId) {
                $child = $categories->firstWhere('id', $cId);
                // Nếu child có tồn tại và nằm trong (chính là, hoặc là con cháu của) node hiện tại
                if ($child && $child->_lft >= $cat->_lft && $child->_rgt <= $cat->_rgt) {
                    $hasCourse = true;
                    break;
                }
            }
            if ($hasCourse) {
                $validIds[] = $cat->id;
            }
        }

        $filteredCategories = $categories->filter(fn ($c) => in_array($c->id, $validIds))->values();

        return $this->success(CategoryResource::collection($filteredCategories));
    }

    /**
     * Public: Lấy cây danh mục (chỉ active).
     */
    public function publicTree(): JsonResponse
    {
        $tree = $this->repository->getTree(true);

        return $this->success(CategoryResource::collection($tree));
    }

    /**
     * Public: Chi tiết danh mục theo slug (chỉ active).
     */
    public function publicShow(string $slug): JsonResponse
    {
        $category = $this->repository->findBySlug($slug, true);

        if (! $category) {
            return $this->error('Danh mục không tồn tại.', 404);
        }

        $category->load('ancestors', 'children');

        return $this->success(new CategoryResource($category));
    }
}
