<?php

namespace Modules\Course\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'courses';

    /**
     * Các cột được phép mass-assign.
     */
    protected $fillable = [
        'teacher_id',
        'category_id',
        'name',
        'slug',
        'description',
        'thumbnail',
        'price',
        'sale_price',
        'level',
        'total_lessons',
        'total_students',
        'rating',
        'status',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'price'          => 'decimal:2',
        'sale_price'     => 'decimal:2',
        'rating'         => 'float',
        'total_lessons'  => 'integer',
        'total_students' => 'integer',
        'status'         => 'integer',
        'teacher_id'     => 'integer',
        'category_id'    => 'integer',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'deleted_at'     => 'datetime',
    ];

    // ── Scopes ──

    /**
     * Scope: chỉ lấy courses đã published (status = 1).
     */
    public function scopePublished($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: alias cho published.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    // ── Relationships ──

    /**
     * Course thuộc về một Teacher.
     */
    public function teacher()
    {
        return $this->belongsTo(\Modules\Teachers\Models\Teachers::class, 'teacher_id');
    }

    /**
     * Course thuộc nhiều Categories (many-to-many qua categories_courses).
     */
    public function categories()
    {
        return $this->belongsToMany(
            \Modules\Categories\Models\Category::class,
            'categories_courses',
            'course_id',
            'category_id'
        );
    }

    /**
     * Course có nhiều Students đã enroll (many-to-many qua students_course).
     */
    public function students()
    {
        return $this->belongsToMany(
            \Modules\Students\Models\Student::class,
            'students_course',
            'course_id',
            'student_id'
        )->withPivot('enrolled_at')->withTimestamps();
    }

    /**
     * Course có nhiều Sections chứa Lessons
     */
    public function sections()
    {
        return $this->hasMany(\Modules\Lessons\Models\Section::class, 'course_id')->ordered();
    }

    /**
     * Course có nhiều Lessons (phẳng) qua section. Tạm thời có thể giữ lại nếu logic cũ cần.
     */
    public function lessons()
    {
        return $this->hasManyThrough(\Modules\Lessons\Models\Lesson::class, \Modules\Lessons\Models\Section::class, 'course_id', 'section_id');
    }
}
