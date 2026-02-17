<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopfrontOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'order_number',
        'customer_name',
        'customer_phone',
        'location',
        'status',
        'total_amount',
        'invoice_number'
    ];

    /**
     * Get the order lines for this order.
     */
    public function orderLines()
    {
        return $this->hasMany(ShopfrontOrderLine::class, 'order_id');
    }

    /**
     * Get the business that owns the order.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the shopfront that owns the order.
     */
    public function shopfront()
    {
        return $this->belongsTo(BusinessShopfront::class, 'business_id', 'business_id');
    }
    // Add this relationship to your ShopfrontOrder model
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'shopfront_order_id');
    }
}
