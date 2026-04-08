<?php

namespace Modules\Lessons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LessonProgress extends Model
{
    use HasFactory;

    /**
     * Bảng tương ứng trong database.
     */
    protected $table = 'lesson_progress';

    /**
     * Các cột được phép mass-assign.
     */
    protected $fillable = [
        'student_id',
        'lesson_id',
        'course_id',
        'is_completed',
        'watched_seconds',
        'completed_at',
    ];

    /**
     * Các cột cần cast kiểu dữ liệu.
     */
    protected $casts = [
        'is_completed'    => 'boolean',
        'watched_seconds' => 'integer',
        'completed_at'    => 'datetime',
        'student_id'      => 'integer',
        'lesson_id'       => 'integer',
        'course_id'       => 'integer',
    ];

    // ── Relationships ──

    /**
     * Progress thuộc về một Lesson.
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    /**
     * Progress thuộc về một Student.
     */
    public function student()
    {
        return $this->belongsTo(\Modules\Students\Models\Student::class, 'student_id');
    }

    /**
     * Progress thuộc về một Course.
     */
    public function course()
    {
        return $this->belongsTo(\Modules\Course\Models\Course::class, 'course_id');
    }
}
