<?php

namespace Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'course'      => $this->whenLoaded('course', fn() => [
                'id'        => $this->course->id,
                'name'      => $this->course->name,
                'slug'      => $this->course->slug,
                'thumbnail' => $this->course->thumbnail
                    ? (str_starts_with($this->course->thumbnail, 'http') || str_starts_with($this->course->thumbnail, '/storage')
                        ? $this->course->thumbnail
                        : '/storage/' . $this->course->thumbnail)
                    : null,
            ]),
            'price'       => $this->price,
            'sale_price'  => $this->sale_price,
            'final_price' => $this->final_price,
        ];
    }
}
