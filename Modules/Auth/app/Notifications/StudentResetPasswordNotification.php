<?php

namespace Modules\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Custom ResetPassword Notification cho Student.
 *
 * Override notification mặc định của Laravel để:
 * - Dùng template email custom (auth::emails.reset-password)
 * - Tránh lỗi "Route [password.reset] not defined"
 * - Tạo URL reset trỏ về frontend
 */
class StudentResetPasswordNotification extends Notification
{
    use Queueable;

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
