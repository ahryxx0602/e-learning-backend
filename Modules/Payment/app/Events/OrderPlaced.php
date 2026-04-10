<?php

namespace Modules\Payment\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Payment\Models\Order;

/**
 * Fired sau khi VNPAY IPN xác nhận thanh toán thành công và student đã được enroll.
 * Listener sẽ gửi email xác nhận đơn hàng bất đồng bộ.
 */
class OrderPlaced
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Order $order,
    ) {}
}
