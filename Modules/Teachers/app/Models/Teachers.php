<?php

namespace Modules\Teachers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Teachers extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'teachers';

    /**
     * Các cột được phép mass-assign.
     */
    protected $fillable = [
        'name',
        'date_of_birth',
        'slug',
        'description',
        'exp',
        'image',
        'status',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'exp' => 'float',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];
    // ── Scopes ──

    /**
     * Scope: chỉ lấy teachers đang active.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    // ── Relationships ──

    /**
     * Teacher có nhiều Course.
     * Relationship sẽ hoạt động khi Module Courses được tạo.
     */
    public function courses()
    {
        return $this->hasMany(\Modules\Courses\Models\Course::class , 'teacher_id');
    }
}
