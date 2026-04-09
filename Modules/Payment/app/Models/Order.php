<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $fillable = [
        'order_code',
        'student_id',
        'subtotal',
        'discount_amount',
        'total_amount',
        'coupon_code',
        'status',
        'payment_method',
        'note',
        'paid_at',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount'    => 'decimal:2',
        'student_id'      => 'integer',
        'paid_at'         => 'datetime',
        'created_at'      => 'datetime',
        'updated_at'      => 'datetime',
        'deleted_at'      => 'datetime',
    ];

    // ── Relationships ──

    /**
     * Order thuộc về một Student.
     */
    public function student()
    {
        return $this->belongsTo(\Modules\Students\Models\Student::class, 'student_id');
    }

    /**
     * Order có nhiều OrderItem.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    /**
     * Order có nhiều Transaction (retry payment tạo thêm transaction).
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'order_id');
    }

    // ── Scopes ──

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // ── Helpers ──

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
