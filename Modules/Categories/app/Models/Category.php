<?php

namespace Modules\Categories\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use HasFactory, SoftDeletes, NodeTrait;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'categories';

    /**
     * Các cột được phép mass-assign.
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'status',
        'order',
        'parent_id',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'status'     => 'integer',
        'order'      => 'integer',
        'parent_id'  => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Các cột ẩn khi serialize (JSON).
     */
    protected $hidden = [
        '_lft',
        '_rgt',
    ];

// ── Scopes ──

    /**
     * Scope: chỉ lấy categories đang active.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

// ── Accessors ──

    /**
     * Check category có phải root (không có parent) hay không.
     */
    public function getIsRootAttribute(): bool
    {
        return is_null($this->parent_id);
    }

// ── Relationships ──

    /**
     * Category có nhiều Courses (many-to-many qua categories_courses).
     */
    public function courses()
    {
        return $this->belongsToMany(
            \Modules\Course\Models\Course::class,
            'categories_courses',
            'category_id',
            'course_id'
        );
    }
}
