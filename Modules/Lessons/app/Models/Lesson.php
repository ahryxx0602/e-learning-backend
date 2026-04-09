<?php

namespace Modules\Lessons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'lessons';

    /**
     * Các cột được phép mass-assign.
     */
    protected $fillable = [
        'course_id',
        'section_id',
        'title',
        'slug',
        'description',
        'type',
        'content',
        'video_id',
        'document_id',
        'order',
        'is_preview',
        'duration',
        'status',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'is_preview'  => 'boolean',
        'status'      => 'integer',
        'order'       => 'integer',
        'duration'    => 'integer',
        'course_id'   => 'integer',
        'section_id'  => 'integer',
        'video_id'    => 'integer',
        'document_id' => 'integer',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];

    // ── Scopes ──

    /**
     * Scope: chỉ lấy lessons đã published (status = 1).
     */
    public function scopePublished($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope: sắp xếp theo thứ tự order tăng dần.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    // ── Relationships ──

    /**
     * Lesson thuộc về một Course.
     */
    public function course()
    {
        return $this->belongsTo(\Modules\Course\Models\Course::class, 'course_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * Lesson có nhiều LessonProgress (tiến trình học của học viên).
     */
    public function progresses()
    {
        return $this->hasMany(LessonProgress::class, 'lesson_id');
    }

    /**
     * Quan hệ tới file video của bài học.
     */
    public function video()
    {
        return $this->belongsTo(\Modules\Upload\Models\MediaFile::class, 'video_id');
    }

    /**
     * Quan hệ tới file tài liệu của bài học.
     */
    public function document()
    {
        return $this->belongsTo(\Modules\Upload\Models\MediaFile::class, 'document_id');
    }
}
