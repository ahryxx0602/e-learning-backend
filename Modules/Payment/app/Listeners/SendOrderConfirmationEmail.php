<?php

namespace Modules\Payment\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Payment\Events\OrderPlaced;
use Modules\Payment\Mail\OrderConfirmationMail;

/**
 * Gửi email xác nhận thanh toán thành công.
 *
 * Implement ShouldQueue → chạy bất đồng bộ, không block IPN response về VNPAY.
 * IPN callback cần trả về ['RspCode' => '00'] trong vài giây — queue giải quyết vấn đề này.
 */
class SendOrderConfirmationEmail implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Số lần retry nếu job thất bại.
     */
    public int $tries = 3;

    /**
     * Thời gian chờ giữa các lần retry (giây).
     */
    public int $backoff = 60;

    public function handle(OrderPlaced $event): void
    {
        $order = $event->order;

        // Load relations cần thiết cho template email
        $order->loadMissing(['student', 'items.course']);

        Mail::to($order->student->email)
            ->send(new OrderConfirmationMail($order));
    }

    /**
     * Xử lý khi job thất bại sau tất cả các lần retry.
     */
    public function failed(OrderPlaced $event, \Throwable $exception): void
    {
        Log::error('Không thể gửi email xác nhận đơn hàng sau tất cả các lần retry.', [
            'order_code' => $event->order->order_code,
            'student_id' => $event->order->student_id,
            'error'      => $exception->getMessage(),
        ]);
    }
}
