<?php

namespace Modules\Payment\Repositories;

use App\Repositories\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Payment\Models\Order;

/**
 * Interface OrderRepositoryInterface
 *
 * Contract cho Order Repository.
 * Extends RepositoryInterface (base methods chuẩn).
 * Thêm các method riêng cho Order/Payment.
 */
interface OrderRepositoryInterface extends RepositoryInterface
{
    /**
     * Danh sách orders (phân trang) có filter (Admin).
     * Filter: status, search (order_code/student email), date range.
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Danh sách orders của một student (phân trang).
     */
    public function getByStudent(int $studentId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Tìm order theo order_code.
     */
    public function findByOrderCode(string $orderCode): ?Order;

    /**
     * Tạo order kèm order_items trong 1 transaction.
     *
     * @param  array  $orderData  Dữ liệu order (student_id, subtotal, total_amount, ...)
     * @param  array  $items      Mảng items [['course_id' => ..., 'price' => ..., 'final_price' => ...], ...]
     * @return Order
     */
    public function createWithItems(array $orderData, array $items): Order;

    /**
     * Đánh dấu order là đã thanh toán.
     */
    public function markAsPaid(int $orderId): Order;

    /**
     * Thống kê doanh thu theo khoảng thời gian.
     *
     * @param  string       $period  'daily' | 'monthly'
     * @param  string|null  $from    Ngày bắt đầu (Y-m-d)
     * @param  string|null  $to      Ngày kết thúc (Y-m-d)
     * @return array
     */
    public function getRevenueStats(string $period = 'monthly', ?string $from = null, ?string $to = null): array;

    /**
     * Kiểm tra student đã enroll các course nào rồi.
     * Trả về mảng course_ids đã enroll.
     */
    public function checkDuplicateEnrollment(int $studentId, array $courseIds): array;
}
