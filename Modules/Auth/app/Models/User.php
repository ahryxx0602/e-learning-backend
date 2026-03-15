<?php

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Model User — Dùng cho Admin/Instructor.
 *
 * Guard: 'admin' (Sanctum token-based).
 * Bảng: users (migration mặc định của Laravel).
 *
 * Lưu ý: Model này tạm đặt trong module Auth.
 * Khi làm task 1.2 (Module Users), sẽ chuyển sang Modules\Users\Models\User
 * và cập nhật config/auth.php.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Guard name cho Spatie Permission.
     * Đảm bảo roles/permissions sẽ dùng guard 'admin'.
     */
    protected $guard_name = 'admin';

    /**
     * Bảng tương ứng trong DB.
     */
    protected $table = 'users';

    /**
     * Các cột cho phép mass assignment.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Các cột ẩn khi serialize (JSON response).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
