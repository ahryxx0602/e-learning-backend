<?php

namespace Modules\Payment\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Modules\Payment\Events\OrderPlaced;
use Modules\Payment\Models\Order;
use Modules\Payment\Models\Transaction;

/**
 * Class VnpayService
 *
 * Xử lý logic tích hợp VNPAY:
 * - Tạo payment URL (createPaymentUrl)
 * - Verify checksum (verifyChecksum)
 * - Xử lý IPN callback (handleIpn)
 * - Xử lý return URL (handleReturn)
 *
 * QUAN TRỌNG:
 * - Timezone: luôn dùng 'Asia/Ho_Chi_Minh' cho vnp_CreateDate / vnp_ExpireDate
 * - Checksum: params phải ksort() trước khi hash HMAC-SHA512
 * - Logging: toàn bộ IPN payload được ghi vào channel 'vnpay'
 */
class VnpayService
{
    /**
     * Tạo payment URL để redirect user sang VNPAY.
     *
     * @param  Order   $order      Order cần thanh toán
     * @param  string  $ipAddress  IP address của user
     * @return string  URL VNPAY để redirect
     */
    public function createPaymentUrl(Order $order, string $ipAddress): string
    {
        $vnpUrl    = config('vnpay.url');
        $secretKey = config('vnpay.hash_secret');

        // Thời gian tạo — BẮT BUỘC dùng GMT+7 (Asia/Ho_Chi_Minh)
        $createDate = Carbon::now('Asia/Ho_Chi_Minh')->format('YmdHis');
        $expireDate = Carbon::now('Asia/Ho_Chi_Minh')->addMinutes(15)->format('YmdHis');

        // VNPAY yêu cầu amount = VND × 100 (không có thập phân)
        $amount = (int) ($order->total_amount * 100);

        $inputData = [
            'vnp_Version'    => config('vnpay.version'),
            'vnp_TmnCode'    => config('vnpay.tmn_code'),
            'vnp_Amount'     => $amount,
            'vnp_Command'    => config('vnpay.command'),
            'vnp_CreateDate' => $createDate,
            'vnp_CurrCode'   => config('vnpay.curr_code'),
            'vnp_IpAddr'     => $ipAddress,
            'vnp_Locale'     => config('vnpay.locale'),
            'vnp_OrderInfo'  => 'Thanh toan don hang ' . $order->order_code,
            'vnp_OrderType'  => 'other',
            'vnp_ReturnUrl'  => config('vnpay.return_url'),
            'vnp_TxnRef'     => $order->order_code,
            'vnp_ExpireDate' => $expireDate,
        ];

        // QUAN TRỌNG: sort params theo alphabet trước khi hash
        ksort($inputData);

        $hashData = '';
        $query    = '';
        $i        = 0;

        foreach ($inputData as $key => $value) {
            if ($i === 1) {
                $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
            } else {
                $hashData .= urlencode($key) . '=' . urlencode($value);
                $i = 1;
            }
            $query .= urlencode($key) . '=' . urlencode($value) . '&';
        }

        // Tạo secure hash HMAC-SHA512
        $vnpSecureHash = hash_hmac('sha512', $hashData, $secretKey);
        $paymentUrl = $vnpUrl . '?' . $query . 'vnp_SecureHash=' . $vnpSecureHash;

        return $paymentUrl;
    }

    /**
     * Verify checksum từ VNPAY response.
     *
     * @param  array  $vnpData  Query params từ VNPAY
     * @return bool
     */
    public function verifyChecksum(array $vnpData): bool
    {
        $secretKey  = config('vnpay.hash_secret');
        $secureHash = $vnpData['vnp_SecureHash'] ?? '';

        // Loại bỏ các tham số hash/type trước khi tính checksum
        unset($vnpData['vnp_SecureHash']);
        unset($vnpData['vnp_SecureHashType']);

        // Sort theo alphabet
        ksort($vnpData);

        $hashData = '';
        $i = 0;

        foreach ($vnpData as $key => $value) {
            if (str_starts_with($key, 'vnp_')) {
                if ($i === 1) {
                    $hashData .= '&' . urlencode($key) . '=' . urlencode($value);
                } else {
                    $hashData .= urlencode($key) . '=' . urlencode($value);
                    $i = 1;
                }
            }
        }

        $checkSum = hash_hmac('sha512', $hashData, $secretKey);

        return hash_equals($checkSum, $secureHash);
    }

    /**
     * Xử lý IPN callback từ VNPAY (server-to-server).
     *
     * Logic:
     * 1. Verify checksum
     * 2. Tìm order theo vnp_TxnRef
     * 3. Kiểm tra amount khớp
     * 4. Kiểm tra idempotent (đã xử lý chưa)
     * 5. Cập nhật transaction + order + enroll
     *
     * @param  array  $vnpData  Query params từ VNPAY IPN
     * @return array  ['RspCode' => string, 'Message' => string]
     */
    public function handleIpn(array $vnpData): array
    {
        // Log toàn bộ payload — "phao cứu sinh" khi debug
        Log::channel('vnpay')->info('IPN received', $vnpData);

        // 1. Verify checksum
        if (!$this->verifyChecksum($vnpData)) {
            Log::channel('vnpay')->warning('IPN checksum invalid', $vnpData);
            return ['RspCode' => '97', 'Message' => 'Invalid Checksum'];
        }

        $orderCode    = $vnpData['vnp_TxnRef'] ?? '';
        $vnpAmount    = (int) ($vnpData['vnp_Amount'] ?? 0);
        $responseCode = $vnpData['vnp_ResponseCode'] ?? '';
        $transCode    = $vnpData['vnp_TransactionNo'] ?? '';

        // 2. Tìm order
        $order = Order::where('order_code', $orderCode)->first();

        if (!$order) {
            Log::channel('vnpay')->warning('IPN order not found', ['order_code' => $orderCode]);
            return ['RspCode' => '01', 'Message' => 'Order not Found'];
        }

        // 3. Kiểm tra amount (VNPAY gửi amount × 100)
        $expectedAmount = (int) ($order->total_amount * 100);

        if ($vnpAmount !== $expectedAmount) {
            Log::channel('vnpay')->warning('IPN amount mismatch', [
                'order_code' => $orderCode,
                'expected'   => $expectedAmount,
                'received'   => $vnpAmount,
            ]);
            return ['RspCode' => '04', 'Message' => 'Invalid Amount'];
        }

        // 4. Kiểm tra idempotent — order đã xử lý chưa
        if ($order->status !== 'pending') {
            Log::channel('vnpay')->info('IPN order already processed', [
                'order_code' => $orderCode,
                'status'     => $order->status,
            ]);
            return ['RspCode' => '02', 'Message' => 'Order already confirmed'];
        }

        // 5. Xử lý kết quả thanh toán
        // Dùng lockForUpdate() tránh race condition khi nhận duplicate IPN
        $order = Order::where('order_code', $orderCode)->lockForUpdate()->first();

        // Double check sau lock
        if ($order->status !== 'pending') {
            return ['RspCode' => '02', 'Message' => 'Order already confirmed'];
        }

        // Tìm transaction pending mới nhất
        $transaction = Transaction::where('order_id', $order->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($responseCode === '00') {
            // Thanh toán thành công
            if ($transaction) {
                $transaction->update([
                    'status'           => 'success',
                    'transaction_code' => $transCode,
                    'bank_code'        => $vnpData['vnp_BankCode'] ?? null,
                    'card_type'        => $vnpData['vnp_CardType'] ?? null,
                    'response_code'    => $responseCode,
                    'gateway_response' => $vnpData,
                    'paid_at'          => now(),
                ]);
            }

            $order->update([
                'status'  => 'paid',
                'paid_at' => now(),
            ]);

            // Enroll student — tạo records trong students_course
            $this->enrollStudent($order);

            // Dispatch event — Listener SendOrderConfirmationEmail chạy async qua queue
            // Không dùng Mail::send trực tiếp vì IPN callback cần phản hồi VNPAY trong vài giây
            OrderPlaced::dispatch($order);

            Log::channel('vnpay')->info('IPN payment SUCCESS', ['order_code' => $orderCode]);
        } else {
            // Thanh toán thất bại
            if ($transaction) {
                $transaction->update([
                    'status'           => 'failed',
                    'transaction_code' => $transCode,
                    'bank_code'        => $vnpData['vnp_BankCode'] ?? null,
                    'card_type'        => $vnpData['vnp_CardType'] ?? null,
                    'response_code'    => $responseCode,
                    'gateway_response' => $vnpData,
                ]);
            }

            $order->update(['status' => 'failed']);

            Log::channel('vnpay')->info('IPN payment FAILED', [
                'order_code'    => $orderCode,
                'response_code' => $responseCode,
            ]);
        }

        return ['RspCode' => '00', 'Message' => 'Confirm Success'];
    }

    /**
     * Xử lý return URL (user redirect về từ VNPAY).
     * CHỈ verify + redirect FE, KHÔNG cập nhật order (để IPN xử lý).
     *
     * @param  array  $vnpData  Query params từ VNPAY return
     * @return array  ['order_code' => string, 'status' => 'success'|'failed']
     */
    public function handleReturn(array $vnpData): array
    {
        Log::channel('vnpay')->info('Return URL received', $vnpData);

        $orderCode    = $vnpData['vnp_TxnRef'] ?? '';
        $responseCode = $vnpData['vnp_ResponseCode'] ?? '';

        if (!$this->verifyChecksum($vnpData)) {
            return [
                'order_code' => $orderCode,
                'status'     => 'failed',
                'message'    => 'Checksum không hợp lệ',
            ];
        }

        return [
            'order_code' => $orderCode,
            'status'     => $responseCode === '00' ? 'success' : 'failed',
            'message'    => $responseCode === '00'
                ? 'Thanh toán thành công'
                : 'Thanh toán thất bại (mã lỗi: ' . $responseCode . ')',
        ];
    }

    /**
     * Enroll student vào tất cả courses trong order.
     * Tạo records trong students_course + increment total_students.
     */
    private function enrollStudent(Order $order): void
    {
        $order->load('items');

        foreach ($order->items as $item) {
            // Kiểm tra đã enroll chưa (tránh duplicate)
            $exists = \Illuminate\Support\Facades\DB::table('students_course')
                ->where('student_id', $order->student_id)
                ->where('course_id', $item->course_id)
                ->exists();

            if (!$exists) {
                \Illuminate\Support\Facades\DB::table('students_course')->insert([
                    'student_id'  => $order->student_id,
                    'course_id'   => $item->course_id,
                    'enrolled_at' => now(),
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                // Increment total_students trên bảng courses
                \Modules\Course\Models\Course::where('id', $item->course_id)
                    ->increment('total_students');
            }
        }
    }
}
