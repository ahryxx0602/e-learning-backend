<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Payment\Http\Requests\CreateOrderRequest;
use Modules\Payment\Http\Resources\OrderResource;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\Transaction;
use Modules\Payment\Repositories\OrderRepositoryInterface;
use Modules\Payment\Services\VnpayService;

class OrderController extends Controller
{
    use ApiResponse;

    public function __construct(
        private OrderRepositoryInterface $repository,
        private VnpayService $vnpayService,
    ) {}

    /**
     * Tạo đơn hàng mới → nhận payment_url VNPAY.
     *
     * Flow:
     * 1. Validate course_ids (kèm check duplicate enrollment)
     * 2. Snapshot giá từng course tại thời điểm mua
     * 3. Tạo order + order_items + transaction pending
     * 4. Nếu tổng = 0 → auto mark paid + enroll (free sau coupon)
     * 5. Nếu tổng > 0 → gọi VnpayService tạo payment URL
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $courseIds = $request->validated()['course_ids'];
        $studentId = auth('api')->id();

        // Lấy thông tin courses kèm giá
        $courses = \Modules\Course\Models\Course::whereIn('id', $courseIds)
            ->published()
            ->get();

        if ($courses->isEmpty()) {
            return $this->error('Không tìm thấy khóa học nào.', 404);
        }

        // Tính toán giá
        $items = [];
        $subtotal = 0;

        foreach ($courses as $course) {
            $price      = (float) $course->price;
            $salePrice  = $course->sale_price ? (float) $course->sale_price : null;
            $finalPrice = $salePrice ?? $price;

            $items[] = [
                'course_id'   => $course->id,
                'price'       => $price,
                'sale_price'  => $salePrice,
                'final_price' => $finalPrice,
            ];

            $subtotal += $finalPrice;
        }

        $discountAmount = 0; // Phase sau: tính coupon
        $totalAmount    = max(0, $subtotal - $discountAmount);

        // Tạo order_code: ORD-YYYYMMDD-XXXXX
        $orderCode = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

        $order = DB::transaction(function () use ($studentId, $orderCode, $subtotal, $discountAmount, $totalAmount, $items) {
            $order = $this->repository->createWithItems([
                'order_code'      => $orderCode,
                'student_id'      => $studentId,
                'subtotal'        => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount'    => $totalAmount,
                'status'          => 'pending',
                'payment_method'  => $totalAmount > 0 ? 'vnpay' : 'free',
            ], $items);

            // Tạo transaction pending
            if ($totalAmount > 0) {
                Transaction::create([
                    'order_id' => $order->id,
                    'gateway'  => 'vnpay',
                    'amount'   => $totalAmount,
                    'status'   => 'pending',
                ]);
            }

            return $order;
        });

        // Xử lý đơn hàng giá = 0 (free sau coupon)
        if ($totalAmount <= 0) {
            $order->update([
                'status'         => 'paid',
                'payment_method' => 'free',
                'paid_at'        => now(),
            ]);

            // Enroll ngay
            $this->vnpayService->handleIpn([
                // Simulate success cho free order — enrollStudent được gọi nội bộ
            ]);

            // Enroll trực tiếp
            foreach ($items as $item) {
                $exists = DB::table('students_course')
                    ->where('student_id', $studentId)
                    ->where('course_id', $item['course_id'])
                    ->exists();

                if (!$exists) {
                    DB::table('students_course')->insert([
                        'student_id'  => $studentId,
                        'course_id'   => $item['course_id'],
                        'enrolled_at' => now(),
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);

                    \Modules\Course\Models\Course::where('id', $item['course_id'])
                        ->increment('total_students');
                }
            }

            $order->refresh();
            $order->load(['items.course']);

            return $this->success([
                'order'       => new OrderResource($order),
                'payment_url' => null,
            ], 'Đơn hàng miễn phí đã được xử lý. Bạn có thể vào học ngay!', 201);
        }

        // Tạo payment URL VNPAY
        $paymentUrl = $this->vnpayService->createPaymentUrl($order, $request->ip());

        $order->load(['items.course']);

        return $this->success([
            'order'       => new OrderResource($order),
            'payment_url' => $paymentUrl,
        ], 'Đơn hàng đã được tạo. Vui lòng thanh toán.', 201);
    }

    /**
     * Lịch sử đơn hàng của sinh viên đang login (phân trang).
     */
    public function myOrders(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'status'   => 'nullable|string|in:pending,paid,failed,cancelled,refunded',
        ]);

        $perPage   = (int) $request->query('per_page', 15);
        $studentId = auth('api')->id();

        $data = $this->repository->getByStudent($studentId, $perPage);

        $data->setCollection(OrderResource::collection($data->getCollection())->collection);

        return $this->paginated($data);
    }

    /**
     * Chi tiết đơn hàng theo order_code (chỉ chủ đơn).
     */
    public function show(string $orderCode): JsonResponse
    {
        $order = $this->repository->findByOrderCode($orderCode);

        if (!$order) {
            return $this->error('Đơn hàng không tồn tại.', 404);
        }

        // Chỉ chủ đơn mới xem được
        if ($order->student_id !== auth('api')->id()) {
            return $this->error('Bạn không có quyền xem đơn hàng này.', 403);
        }

        return $this->success(new OrderResource($order));
    }

    /**
     * Thanh toán lại đơn pending → tạo transaction mới → trả payment_url mới.
     */
    public function retryPayment(string $orderCode, Request $request): JsonResponse
    {
        $order = $this->repository->findByOrderCode($orderCode);

        if (!$order) {
            return $this->error('Đơn hàng không tồn tại.', 404);
        }

        if ($order->student_id !== auth('api')->id()) {
            return $this->error('Bạn không có quyền thực hiện thao tác này.', 403);
        }

        if (!$order->isPending() && !$order->isFailed()) {
            return $this->error('Chỉ đơn hàng đang chờ hoặc thất bại mới có thể thanh toán lại.', 422);
        }

        // Reset status về pending nếu đang failed
        if ($order->isFailed()) {
            $order->update(['status' => 'pending']);
        }

        // Tạo transaction mới
        Transaction::create([
            'order_id' => $order->id,
            'gateway'  => 'vnpay',
            'amount'   => $order->total_amount,
            'status'   => 'pending',
        ]);

        $paymentUrl = $this->vnpayService->createPaymentUrl($order, $request->ip());

        return $this->success([
            'order_code'  => $order->order_code,
            'payment_url' => $paymentUrl,
        ], 'Đã tạo liên kết thanh toán mới.');
    }
}
