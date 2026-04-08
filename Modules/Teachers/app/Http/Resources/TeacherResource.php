<?php

namespace Modules\Teachers\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'exp'         => $this->exp,
            'image'       => $this->image,
            'status'      => $this->status,
            'courses'     => $this->whenLoaded('courses', function () {
                return $this->courses->map(fn ($course) => [
                    'id'        => $course->id,
                    'name'      => $course->name,
                    'slug'      => $course->slug,
                    'thumbnail' => $course->thumbnail,
                    'price'     => $course->price,
                    'sale_price'=> $course->sale_price,
                ]);
            }),
            'courses_count' => $this->whenCounted('courses'),
            'created_at'  => $this->created_at->toISOString(),
            'updated_at'  => $this->updated_at->toISOString(),
        ];
    }
}
