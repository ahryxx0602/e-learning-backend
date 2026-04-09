<?php

namespace Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'course_id',
        'price',
        'sale_price',
        'final_price',
    ];

    protected $casts = [
        'order_id'    => 'integer',
        'course_id'   => 'integer',
        'price'       => 'decimal:2',
        'sale_price'  => 'decimal:2',
        'final_price' => 'decimal:2',
    ];

    // ── Relationships ──

    /**
     * OrderItem thuộc về một Order.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * OrderItem liên kết với một Course.
     */
    public function course()
    {
        return $this->belongsTo(\Modules\Course\Models\Course::class, 'course_id');
    }
}
