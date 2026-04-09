<?php

return [
    /*
    |--------------------------------------------------------------------------
    | VNPAY Merchant Configuration
    |--------------------------------------------------------------------------
    |
    | Thông tin tích hợp cổng thanh toán VNPAY.
    | Lấy từ trang quản trị merchant VNPAY sandbox / production.
    |
    */

    'tmn_code'    => env('VNPAY_TMN_CODE', ''),
    'hash_secret' => env('VNPAY_HASH_SECRET', ''),

    // URL thanh toán (sandbox hoặc production)
    'url'         => env('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'),

    // URL VNPAY redirect user về sau khi thanh toán
    'return_url'  => env('VNPAY_RETURN_URL', 'http://localhost:8000/api/v1/payment/vnpay/return'),

    // API URL (cho query transaction — dùng sau nếu cần)
    'api_url'     => env('VNPAY_API_URL', 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction'),

    // VNPAY API version
    'version'     => '2.1.0',

    // Command: 'pay' cho thanh toán
    'command'     => 'pay',

    // Mã tiền tệ (VND)
    'curr_code'   => 'VND',

    // Ngôn ngữ: 'vn' = tiếng Việt
    'locale'      => 'vn',

    // Frontend URL để redirect user sau khi xử lý return
    'frontend_result_url' => env('VNPAY_FRONTEND_RESULT_URL', 'http://localhost:5173/payment/result'),
];
