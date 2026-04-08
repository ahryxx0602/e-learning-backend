<?php

namespace Modules\Students\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'name'              => $this->name,
            'email'             => $this->email,
            'avatar'            => $this->avatar,
            'date_of_birth'     => $this->date_of_birth?->format('Y-m-d'),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at'        => $this->created_at->toISOString(),
            'updated_at'        => $this->updated_at->toISOString(),
        ];
    }
}
