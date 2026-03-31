<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    const STATUS_BOOKING = 'booking';
    const STATUS_PAID = 'paid';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'total_price',
        'shipping_cost',
        'payment_proof_path',
        'tracking_number',
        'status',
        'paid_at',
        'payment_requested_at',
        'payment_due_at',
        'shipped_at',
        'completed_at',
        'cancelled_at'
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'paid_at' => 'datetime',
        'payment_requested_at' => 'datetime',
        'payment_due_at' => 'datetime',
        'shipped_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
