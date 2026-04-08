<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Users\Http\Requests\BulkActionUsersRequest;
use Modules\Users\Http\Requests\BulkAssignRoleRequest;
use Modules\Users\Http\Requests\BulkDeleteUsersRequest;
use Modules\Users\Http\Requests\BulkForceDeleteUsersRequest;
use Modules\Users\Http\Requests\BulkRestoreUsersRequest;
use Modules\Users\Http\Requests\StoreUsersRequest;
use Modules\Users\Http\Requests\UpdateUsersRequest;
use Modules\Users\Repositories\UsersRepositoryInterface;

class UsersController extends Controller
{
    use ApiResponse;

    protected UsersRepositoryInterface $repository;

    public function __construct(UsersRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Danh sách Users (có phân trang).
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int)$request->query('per_page', 15);
        $data = $this->repository->paginate($perPage);

        return $this->paginated($data);
    }

    /**
     * Tạo mới User + gán role.
     */
    public function store(StoreUsersRequest $request): JsonResponse
    {
        $user = $this->repository->create($request->validated());

        if ($request->filled('role')) {
            $user->assignRole($request->role);
        }

        $user->load('roles');

        return $this->success($user, 'User đã được tạo thành công.', 201);
    }

    /**
     * Chi tiết User.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->repository->findOrFail($id);
        $user->load('roles', 'permissions');

        return $this->success($user);
    }

    /**
     * Cập nhật User + đổi role.
     */
    public function update(UpdateUsersRequest $request, int $id): JsonResponse
    {
        $user = $this->repository->update($id, $request->validated());

        if ($request->filled('role')) {
            $user->syncRoles([$request->role]);
        }

        $user->load('roles');

        return $this->success($user, 'User đã được cập nhật thành công.');
    }

    /**
     * Xoá User (soft delete).
     */
    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'User đã được xoá thành công.');
    }

    /**
     * Gán role cho User.
     */
    public function assignRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = $this->repository->findOrFail($id);
        $user->assignRole($request->role);
        $user->load('roles');

        return $this->success($user, 'Gán role thành công.');
    }

    /**
     * Thu hồi role của User.
     */
    public function revokeRole(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = $this->repository->findOrFail($id);
        $user->removeRole($request->role);
        $user->load('roles');

        return $this->success($user, 'Thu hồi role thành công.');
    }

    /**
     * Xoá nhiều Users cùng lúc.
     */
    public function bulkDelete(BulkDeleteUsersRequest $request): JsonResponse
    {
        $deleted = $this->repository->deleteMany($request->ids);

        return $this->success(
        ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá {$deleted} user thành công."
        );
    }

    /**
     * Thực hiện action hàng loạt (activate / deactivate).
     */
    public function bulkAction(BulkActionUsersRequest $request): JsonResponse
    {
        $affected = $this->repository->actionMany($request->ids, $request->action);

        return $this->success(
        ['affected_count' => $affected, 'affected_ids' => $request->ids],
            "Đã thực hiện '{$request->action}' cho {$affected} user thành công."
        );
    }

    /**
     * Danh sách Users đã bị soft-delete (thùng rác).
     */
    public function trashed(Request $request): JsonResponse
    {
        $perPage = (int)$request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage, ['*'], ['roles']);

        return $this->paginated($data);
    }

    /**
     * Khôi phục một User đã soft-delete.
     */
    public function restore(int $id): JsonResponse
    {
        $this->repository->restore($id);

        return $this->success(null, 'User đã được khôi phục thành công.');
    }

    /**
     * Khôi phục nhiều Users đã soft-delete.
     */
    public function bulkRestore(BulkRestoreUsersRequest $request): JsonResponse
    {
        $restored = $this->repository->restoreMany($request->ids);

        return $this->success(
        ['restored_count' => $restored, 'restored_ids' => $request->ids],
            "Đã khôi phục {$restored} user thành công."
        );
    }

    /**
     * Xoá vĩnh viễn một User (bao gồm cả đã soft-delete).
     */
    public function forceDelete(int $id): JsonResponse
    {
        $this->repository->forceDeleteById($id);

        return $this->success(null, 'User đã bị xoá vĩnh viễn.');
    }

    /**
     * Xoá vĩnh viễn nhiều Users cùng lúc (bao gồm cả đã soft-delete).
     */
    public function bulkForceDelete(BulkForceDeleteUsersRequest $request): JsonResponse
    {
        $deleted = $this->repository->forceDeleteMany($request->ids);

        return $this->success(
        ['deleted_count' => $deleted, 'deleted_ids' => $request->ids],
            "Đã xoá vĩnh viễn {$deleted} user."
        );
    }

    /**
     * Gán role cho nhiều Users cùng lúc.
     */
    public function bulkAssignRole(BulkAssignRoleRequest $request): JsonResponse
    {
        $affected = $this->repository->assignRoleMany($request->ids, $request->role);

        return $this->success(
        ['affected_count' => $affected],
            "Đã gán role '{$request->role}' cho {$affected} user thành công."
        );
    }
}
