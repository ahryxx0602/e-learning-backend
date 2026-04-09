<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Http\Resources\OrderResource;
use Modules\Payment\Repositories\OrderRepositoryInterface;

class AdminOrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private OrderRepositoryInterface $repository,
    ) {}

    /**
     * Danh sách tất cả đơn hàng (phân trang + filter).
     * Filter: search (order_code/student), status, from, to, payment_method.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'search'         => 'nullable|string|max:100',
            'status'         => 'nullable|string|in:pending,paid,failed,cancelled,refunded',
            'from'           => 'nullable|date',
            'to'             => 'nullable|date',
            'payment_method' => 'nullable|string|in:vnpay,momo,free',
            'per_page'       => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $filters = $request->only(['search', 'status', 'from', 'to', 'payment_method']);

        $data = $this->repository->getFiltered($filters, $perPage);
        $data->setCollection(OrderResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Chi tiết đơn hàng (kèm items + transactions).
     */
    public function show(int $id): JsonResponse
    {
        $order = $this->repository->findOrFail($id, ['*'], ['student', 'items.course', 'transactions']);

        return $this->success(new OrderResource($order));
    }

    /**
     * Admin cập nhật trạng thái đơn hàng (VD: refund thủ công).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:pending,paid,failed,cancelled,refunded',
            'note'   => 'nullable|string|max:500',
        ]);

        $order = $this->repository->findOrFail($id);

        $oldStatus = $order->status;
        $newStatus = $request->input('status');

        $updateData = ['status' => $newStatus];

        if ($request->filled('note')) {
            $updateData['note'] = $request->input('note');
        }

        if ($newStatus === 'paid' && $oldStatus !== 'paid') {
            $updateData['paid_at'] = now();
        }

        $order->update($updateData);
        $order->refresh();
        $order->load(['student', 'items.course', 'transactions']);

        return $this->success(
            new OrderResource($order),
            "Trạng thái đơn hàng đã cập nhật: {$oldStatus} → {$newStatus}."
        );
    }

    /**
     * Danh sách đơn hàng đã soft-delete (thùng rác).
     */
    public function trashed(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $perPage = (int) $request->query('per_page', 15);
        $data = $this->repository->paginateTrashed($perPage, ['*'], ['student', 'items.course']);
        $data->setCollection(OrderResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Soft delete đơn hàng.
     */
    public function destroy(int $id): JsonResponse
    {
        $this->repository->delete($id);

        return $this->success(null, 'Đơn hàng đã được xoá thành công.');
    }

    /**
     * Khôi phục đơn hàng đã soft-delete.
     */
    public function restore(int $id): JsonResponse
    {
        $this->repository->restore($id);

        return $this->success(null, 'Đơn hàng đã được khôi phục thành công.');
    }

    /**
     * Bulk soft delete nhiều đơn hàng.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:orders,id',
        ]);

        $deleted = DB::transaction(function () use ($request) {
            return $this->repository->deleteMany($request->ids);
        });

        return $this->success(
            ['deleted_count' => $deleted],
            "Đã xoá {$deleted} đơn hàng thành công."
        );
    }

    /**
     * Thống kê doanh thu (cho dashboard).
     * Query params: period (daily/monthly), from, to.
     */
    public function revenueStats(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:daily,monthly',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date',
        ]);

        $period = $request->query('period', 'monthly');
        $from   = $request->query('from');
        $to     = $request->query('to');

        $stats = $this->repository->getRevenueStats($period, $from, $to);

        return $this->success($stats, 'Lấy thống kê doanh thu thành công.');
    }
}
