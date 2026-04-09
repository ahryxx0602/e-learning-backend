<?php

namespace Modules\Course\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Teachers\Http\Resources\TeacherResource;
use Modules\Categories\Http\Resources\CategoryResource;

class CourseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'slug'           => $this->slug,
            'description'    => $this->description,
            'thumbnail'      => $this->thumbnail
                                    ? (str_starts_with($this->thumbnail, 'http') || str_starts_with($this->thumbnail, '/storage')
                                        ? $this->thumbnail
                                        : '/storage/' . $this->thumbnail)
                                    : null,
            'price'          => $this->price,
            'sale_price'     => $this->sale_price,
            'level'          => $this->level,
            'total_lessons'  => $this->total_lessons,
            'total_students' => $this->total_students,
            'rating'         => $this->rating,
            'status'         => $this->status,
            'teacher'        => new TeacherResource($this->whenLoaded('teacher')),
            'categories'     => CategoryResource::collection($this->whenLoaded('categories')),
            'is_purchased'   => $this->when(
                auth('api')->check(),
                function () {
                    if (!$this->relationLoaded('students')) {
                        return false;
                    }
                    $studentId = auth('api')->id();
                    return $this->students->contains(fn($s) => $s->id === $studentId);
                }
            ),
            'created_at'     => $this->created_at?->toISOString(),
            'updated_at'     => $this->updated_at?->toISOString(),
        ];
    }
}
