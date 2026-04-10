<?php

namespace Modules\Auth\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Students\Models\Student;

/**
 * Fired sau khi student đăng ký thành công và token xác thực đã được lưu vào DB.
 * Listener sẽ đọc token từ bảng student_email_verifications để gửi email.
 */
class StudentRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Student $student,
        public readonly string $verifyToken,
    ) {}
}
