<?php

namespace Modules\Payment\Repositories;

use App\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Payment\Models\Order;

/**
 * Class OrderRepository
 *
 * Eloquent implementation cho OrderRepositoryInterface.
 * Extends BaseRepository (đã có sẵn base methods + clamp perPage, soft-delete support).
 */
class OrderRepository extends BaseRepository implements OrderRepositoryInterface
{
    public function __construct(Order $model)
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
            ->with(['student', 'items.course'])
            ->latest();

        // Tìm kiếm theo order_code hoặc email sinh viên
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_code', 'like', "%{$search}%")
                  ->orWhereHas('student', fn($sq) => $sq->where('email', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%"));
            });
        }

        // Filter theo status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter theo khoảng thời gian
        if (!empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        // Filter theo payment_method
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function getByStudent(int $studentId, int $perPage = 15): LengthAwarePaginator
    {
        $perPage = max(1, min($perPage, static::MAX_PER_PAGE));

        return $this->model->newQuery()
            ->where('student_id', $studentId)
            ->with(['items.course'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * {@inheritDoc}
     */
    public function findByOrderCode(string $orderCode): ?Order
    {
        return $this->model->newQuery()
            ->where('order_code', $orderCode)
            ->with(['items.course', 'transactions'])
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function createWithItems(array $orderData, array $items): Order
    {
        $order = $this->model->newQuery()->create($orderData);

        foreach ($items as $item) {
            $order->items()->create($item);
        }

        $order->load(['items.course']);

        return $order;
    }

    /**
     * {@inheritDoc}
     */
    public function markAsPaid(int $orderId): Order
    {
        $order = $this->model->newQuery()->findOrFail($orderId);
        $order->update([
            'status'  => 'paid',
            'paid_at' => now(),
        ]);
        $order->refresh();

        return $order;
    }

    /**
     * {@inheritDoc}
     */
    public function getRevenueStats(string $period = 'monthly', ?string $from = null, ?string $to = null): array
    {
        $query = $this->model->newQuery()->paid();

        if ($from) {
            $query->whereDate('paid_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('paid_at', '<=', $to);
        }

        if ($period === 'daily') {
            $results = $query
                ->select(
                    DB::raw('DATE(paid_at) as date'),
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(total_amount) as total_revenue')
                )
                ->groupBy(DB::raw('DATE(paid_at)'))
                ->orderBy('date', 'asc')
                ->get();
        } else {
            // monthly
            $results = $query
                ->select(
                    DB::raw('YEAR(paid_at) as year'),
                    DB::raw('MONTH(paid_at) as month'),
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(total_amount) as total_revenue')
                )
                ->groupBy(DB::raw('YEAR(paid_at)'), DB::raw('MONTH(paid_at)'))
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();
        }

        // Tổng doanh thu
        $totalRevenue = $this->model->newQuery()->paid()
            ->when($from, fn($q) => $q->whereDate('paid_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('paid_at', '<=', $to))
            ->sum('total_amount');

        $totalOrders = $this->model->newQuery()->paid()
            ->when($from, fn($q) => $q->whereDate('paid_at', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('paid_at', '<=', $to))
            ->count();

        return [
            'period'        => $period,
            'data'          => $results->toArray(),
            'total_revenue' => (float) $totalRevenue,
            'total_orders'  => $totalOrders,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function checkDuplicateEnrollment(int $studentId, array $courseIds): array
    {
        return DB::table('students_course')
            ->where('student_id', $studentId)
            ->whereIn('course_id', $courseIds)
            ->pluck('course_id')
            ->toArray();
    }
}
