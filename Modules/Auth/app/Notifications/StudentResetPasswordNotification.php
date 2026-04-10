<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * Custom ResetPassword Notification cho Student.
 *
 * Override notification mặc định của Laravel để:
 * - Dùng template email custom (auth::emails.reset-password)
 * - Tránh lỗi "Route [password.reset] not defined"
 * - Tạo URL reset trỏ về frontend
 * - Chạy async qua queue (ShouldQueue) — không block HTTP response
 */
class StudentResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Số lần retry nếu job thất bại.
     */
    public int $tries = 3;

    /**
     * Thời gian chờ giữa các lần retry (giây).
     */
    public int $backoff = 60;

    public string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $resetUrl = $frontendUrl . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->email);

        return (new MailMessage)
            ->subject('Đặt lại mật khẩu — E-Learning')
            ->view('auth::emails.reset-password', [
                'studentName' => $notifiable->name,
                'resetUrl'    => $resetUrl,
                'token'       => $this->token,
            ]);
    }
}
