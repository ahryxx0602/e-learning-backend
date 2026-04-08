<?php

namespace Modules\Users\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable, HasApiTokens, HasRoles;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'users';

    /**
     * Các cột được phép mass-assign.
     * TODO: Thêm các cột cần thiết.
     */


    /**
     * Guard name
     */
    protected $guard_name = 'admin';


    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
    ];

    // Các cột cần ẩn
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

// ── Relationships ──

}
