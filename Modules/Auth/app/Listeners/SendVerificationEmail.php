<?php

namespace Modules\Auth\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Auth\Events\StudentRegistered;

/**
 * Gửi email xác thực khi student đăng ký hoặc yêu cầu gửi lại.
 *
 * Implement ShouldQueue → Laravel đẩy job vào queue, không block HTTP response.
 * Nếu gửi mail thất bại, job sẽ tự retry theo cấu hình queue (mặc định 3 lần).
 */
class SendVerificationEmail implements ShouldQueue
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

    public function handle(StudentRegistered $event): void
    {
        $verifyUrl = config('app.url') . '/api/v1/auth/verify-email/' . $event->verifyToken;

        Mail::send(
            'auth::emails.verify-email',
            [
                'studentName' => $event->student->name,
                'verifyUrl'   => $verifyUrl,
            ],
            function ($message) use ($event) {
                $message->to($event->student->email)
                    ->subject('Xác thực email — E-Learning');
            }
        );
    }

    /**
     * Xử lý khi job thất bại sau tất cả các lần retry.
     */
    public function failed(StudentRegistered $event, \Throwable $exception): void
    {
        Log::error('Không thể gửi email xác thực sau tất cả các lần retry.', [
            'student_id' => $event->student->id,
            'email'      => $event->student->email,
            'error'      => $exception->getMessage(),
        ]);
    }
}
