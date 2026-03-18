<?php

namespace Modules\Categories\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'icon'        => $this->icon,
            'status'      => $this->status,
            'order'       => $this->order,
            'parent_id'   => $this->parent_id,
            'is_root'     => $this->is_root,
            'depth'       => $this->depth ?? null,
            'children'    => $this->whenLoaded('children', function () {
                return CategoryResource::collection($this->children);
            }),
            'parent'      => $this->whenLoaded('parent', function () {
                return $this->parent ? ['id' => $this->parent->id, 'name' => $this->parent->name] : null;
            }),
            'ancestors'   => $this->whenLoaded('ancestors', function () {
                return CategoryResource::collection($this->ancestors);
            }),
            'created_at'  => $this->created_at->toISOString(),
            'updated_at'  => $this->updated_at->toISOString(),
        ];
    }
}
