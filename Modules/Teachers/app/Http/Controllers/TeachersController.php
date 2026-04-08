<?php

namespace Modules\Teachers\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Teachers\Http\Requests\BulkDeleteTeachersRequest;
use Modules\Teachers\Http\Requests\BulkForceDeleteTeachersRequest;
use Modules\Teachers\Http\Requests\BulkRestoreTeachersRequest;
use Modules\Teachers\Http\Requests\StoreTeachersRequest;
use Modules\Teachers\Http\Requests\UpdateTeachersRequest;
use Modules\Teachers\Http\Resources\TeacherResource;
use Modules\Teachers\Repositories\TeachersRepositoryInterface;

class TeachersController extends Controller
{
    use ApiResponse;

    protected TeachersRepositoryInterface $repository;

    public function __construct(TeachersRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    // ── Admin CRUD ──

    /**
     * Danh sách Teachers (có phân trang + filter).
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'   => 'nullable|string|max:100',
            'status'   => 'nullable|integer|in:0,1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'status']);

        $data = $this->repository->getFiltered($filters, $perPage);
        $data->setCollection(TeacherResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Tạo mới Teacher.
     */
    public function store(StoreTeachersRequest $request): JsonResponse
    {
        $teacher = $this->repository->create($request->validated());
        $teacher->refresh();

        return $this->success(new TeacherResource($teacher), 'Giảng viên đã được tạo thành công.', 201);
    }

    /**
     * Chi tiết Teacher.
     */
    public function show(int $id): JsonResponse
    {
        $teacher = $this->repository->findOrFail($id);

        return $this->success(new TeacherResource($teacher));
    }

    /**
     * Cập nhật Teacher.
     */
    public function update(UpdateTeachersRequest $request, int $id): JsonResponse
    {
        $teacher = $this->repository->update($id, $request->validated());

        return $this->success(new TeacherResource($teacher), 'Giảng viên đã được cập nhật thành công.');
    }

    /**
     * Xoá Teacher (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Giảng viên đã được xoá thành công.');
    }

    /**
     * Toggle trạng thái active/inactive.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $teacher = $this->repository->toggleStatus($id);

        $statusText = $teacher->status === 1 ? 'kích hoạt' : 'vô hiệu hoá';

        return $this->success(new TeacherResource($teacher), "Giảng viên đã được {$statusText}.");
    }

    // ── Soft Delete Operations ──

    /**
     * Danh sách Teachers đã bị soft-delete (thùng rác).
     */
    public function trashed(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage);
        $data->setCollection(TeacherResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Khôi phục một Teacher đã soft-delete.
     */
    public function restore(int $id): JsonResponse
    {
        $this->repository->restore($id);

        return $this->success(null, 'Giảng viên đã được khôi phục thành công.');
    }

    /**
     * Xoá vĩnh viễn một Teacher.
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'Giảng viên đã bị xoá vĩnh viễn.');
    }

    // ── Bulk Operations ──

    /**
     * Xoá nhiều Teachers (soft delete).
     * Bọc trong DB::transaction() để đảm bảo tính toàn vẹn dữ liệu.
     */
    public function bulkDelete(BulkDeleteTeachersRequest $request): JsonResponse
    {
        $deleted = DB::transaction(function () use ($request) {
            return $this->repository->deleteMany($request->ids);
        });

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá {$deleted} giảng viên thành công."
        );
    }

    /**
     * Khôi phục nhiều Teachers đã soft-delete.
     * Bọc trong DB::transaction() để đảm bảo tính toàn vẹn dữ liệu.
     */
    public function bulkRestore(BulkRestoreTeachersRequest $request): JsonResponse
    {
        $restored = DB::transaction(function () use ($request) {
            return $this->repository->restoreMany($request->ids);
        });

        return $this->success(
            ['restored_count' => $restored, 'restored_ids' => $request->ids],
            "Đã khôi phục {$restored} giảng viên thành công."
        );
    }

    /**
     * Xoá vĩnh viễn nhiều Teachers.
     * Bọc trong DB::transaction() để đảm bảo tính toàn vẹn dữ liệu.
     */
    public function bulkForceDelete(BulkForceDeleteTeachersRequest $request): JsonResponse
    {
        $deleted = DB::transaction(function () use ($request) {
            return $this->repository->forceDeleteMany($request->ids);
        });

        return $this->success(
            ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá vĩnh viễn {$deleted} giảng viên."
        );
    }

    // ── Public API ──

    /**
     * Public: Danh sách giảng viên (chỉ active, phân trang).
     */
    public function publicIndex(Request $request): JsonResponse
    {
        $request->validate([
            'search'   => 'nullable|string|max:100',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search']);

        $data = $this->repository->getPublicList($filters, $perPage);
        $data->setCollection(TeacherResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Public: Chi tiết giảng viên theo slug (kèm danh sách khóa học).
     */
    public function publicShow(string $slug): JsonResponse
    {
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return $this->error('Giảng viên không tồn tại.', 404);
        }

        $teacher = $this->repository->findBySlug($slug, true);

        if (!$teacher) {
            return $this->error('Giảng viên không tồn tại.', 404);
        }

        $teacher->load('courses');

        return $this->success(new TeacherResource($teacher));
    }
}
