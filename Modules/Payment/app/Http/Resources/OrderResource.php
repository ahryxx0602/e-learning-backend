<?php

namespace Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'order_code'      => $this->order_code,
            'subtotal'        => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'total_amount'    => $this->total_amount,
            'coupon_code'     => $this->coupon_code,
            'status'          => $this->status,
            'payment_method'  => $this->payment_method,
            'note'            => $this->note,
            'paid_at'         => $this->paid_at?->toISOString(),
            'items'           => OrderItemResource::collection($this->whenLoaded('items')),
            'transactions'    => TransactionResource::collection($this->whenLoaded('transactions')),
            'student'         => $this->whenLoaded('student', fn() => [
                'id'     => $this->student->id,
                'name'   => $this->student->name,
                'email'  => $this->student->email,
                'avatar' => $this->student->avatar,
            ]),
            'created_at'      => $this->created_at?->toISOString(),
            'updated_at'      => $this->updated_at?->toISOString(),
        ];
    }
}
