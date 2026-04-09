<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'order_id',
        'gateway',
        'transaction_code',
        'bank_code',
        'card_type',
        'amount',
        'status',
        'gateway_response',
        'response_code',
        'paid_at',
    ];

    protected $casts = [
        'order_id'         => 'integer',
        'gateway_response' => 'json',
        'amount'           => 'decimal:2',
        'paid_at'          => 'datetime',
        'created_at'       => 'datetime',
        'updated_at'       => 'datetime',
    ];

    // ── Relationships ──

    /**
     * Transaction thuộc về một Order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // ── Helpers ──

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
