<?php

namespace Modules\Payment\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'gateway'          => $this->gateway,
            'transaction_code' => $this->transaction_code,
            'bank_code'        => $this->bank_code,
            'card_type'        => $this->card_type,
            'amount'           => $this->amount,
            'status'           => $this->status,
            'response_code'    => $this->response_code,
            'paid_at'          => $this->paid_at?->toISOString(),
            'created_at'       => $this->created_at?->toISOString(),
        ];
    }
}
