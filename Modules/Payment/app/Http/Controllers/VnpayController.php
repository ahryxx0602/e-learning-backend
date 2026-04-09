<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payment\Services\VnpayService;

class VnpayController extends Controller
{
    use ApiResponse;

    public function __construct(
        private VnpayService $vnpayService,
    ) {}

    /**
     * VNPAY Return URL — user được redirect về đây sau khi thanh toán.
     *
     * Trong môi trường production, IPN sẽ xử lý cập nhật order.
     * Tuy nhiên, ở localhost VNPAY không gọi được IPN,
     * nên return URL cũng xử lý luôn (fallback) để đảm bảo order được cập nhật.
     */
    public function return(Request $request): mixed
    {
        $vnpData = $request->query();

        // Xử lý IPN logic ngay tại return (fallback cho localhost)
        // handleIpn đã có idempotent check, nên gọi lại ở đây an toàn
        $this->vnpayService->handleIpn($vnpData);

        $result = $this->vnpayService->handleReturn($vnpData);

        // Redirect user về frontend payment result page
        $frontendUrl = config('vnpay.frontend_result_url');
        $queryParams = http_build_query([
            'order_code' => $result['order_code'],
            'status'     => $result['status'],
            'message'    => $result['message'],
        ]);

        return redirect()->away("{$frontendUrl}?{$queryParams}");
    }

    /**
     * VNPAY IPN — Webhook server-to-server.
     *
     * VNPAY gọi endpoint này để thông báo kết quả thanh toán.
     * Response phải trả JSON theo chuẩn VNPAY: { RspCode, Message }
     */
    public function ipn(Request $request): JsonResponse
    {
        $vnpData = $request->query();

        $result = $this->vnpayService->handleIpn($vnpData);

        return response()->json($result);
    }
}
