<?php

namespace Modules\Upload\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaFileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'type'           => $this->type,
            'status'         => $this->status,
            'original_name'  => $this->original_name,
            'url'            => $this->url,
            'mime_type'      => $this->mime_type,
            'size'           => $this->size,
            'size_mb'        => round($this->size / 1048576, 2),
            'duration'       => $this->duration,
            'width'          => $this->width,
            'height'         => $this->height,
            'bitrate'        => $this->bitrate,
            'codec'          => $this->codec,
            'created_at'     => $this->created_at?->toISOString(),
        ];
    }
}
