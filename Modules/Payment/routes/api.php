<?php

use Illuminate\Support\Facades\Route;
use Modules\Payment\Http\Controllers\OrderController;
use Modules\Payment\Http\Controllers\AdminOrderController;
use Modules\Payment\Http\Controllers\VnpayController;

/*
|--------------------------------------------------------------------------
| Admin Routes (auth:admin middleware)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    // Extra routes trước để tránh conflict với {id}
    Route::get('orders/trashed',                [AdminOrderController::class, 'trashed']);
    Route::delete('orders/bulk-delete',         [AdminOrderController::class, 'bulkDelete']);
    Route::get('orders/stats/revenue',          [AdminOrderController::class, 'revenueStats']);

    // Danh sách + chi tiết
    Route::get('orders',                        [AdminOrderController::class, 'index']);
    Route::get('orders/{id}',                   [AdminOrderController::class, 'show']);
    Route::patch('orders/{id}/status',          [AdminOrderController::class, 'updateStatus']);
    Route::delete('orders/{id}',                [AdminOrderController::class, 'destroy']);
    Route::patch('orders/{id}/restore',         [AdminOrderController::class, 'restore']);
});

/*
|--------------------------------------------------------------------------
| Student Routes (auth:api + email.verified)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:api', 'email.verified'])->group(function () {
    // Tạo đơn hàng → nhận URL VNPAY
    Route::post('orders',                       [OrderController::class, 'store']);

    // Lịch sử đơn hàng của sinh viên
    Route::get('my-orders',                     [OrderController::class, 'myOrders']);
    Route::get('my-orders/{orderCode}',         [OrderController::class, 'show']);

    // Thanh toán lại đơn pending
    Route::post('orders/{orderCode}/retry-payment', [OrderController::class, 'retryPayment']);
});

/*
|--------------------------------------------------------------------------
| VNPAY Callback (public — VNPAY redirect user về đây)
|--------------------------------------------------------------------------
*/
Route::get('payment/vnpay/return',              [VnpayController::class, 'return']);

/*
|--------------------------------------------------------------------------
| VNPAY IPN — Webhook (server-to-server, public, không cần auth)
|--------------------------------------------------------------------------
*/
Route::get('payment/vnpay/ipn',                 [VnpayController::class, 'ipn']);
