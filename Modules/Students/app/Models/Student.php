<?php

namespace Modules\Students\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\Auth\Notifications\StudentResetPasswordNotification;

class Student extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens;

    protected $table = 'students';

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'date_of_birth',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth'     => 'date',
        'password'          => 'hashed',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        'deleted_at'        => 'datetime',
    ];

    /**
     * Gửi notification đặt lại mật khẩu.
     *
     * Override để dùng custom notification thay vì
     * notification mặc định (tránh lỗi Route [password.reset] not defined).
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new StudentResetPasswordNotification($token));
    }
}

