<?php

namespace Modules\Lessons\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Section extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'sections';

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order',
        'status',
    ];

    protected $casts = [
        'status'    => 'integer',
        'order'     => 'integer',
        'course_id' => 'integer',
    ];

    // ── Scopes ──

    public function scopePublished($query)
    {
        return $query->where('status', 1);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }

    // ── Relationships ──

    public function course()
    {
        return $this->belongsTo(\Modules\Course\Models\Course::class, 'course_id');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'section_id')->ordered();
    }
}
