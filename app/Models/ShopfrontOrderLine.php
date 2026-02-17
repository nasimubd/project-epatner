<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopfrontOrderLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'is_common_product',
        'product_name',
        'quantity',
        'unit_price',
        'line_total'
    ];

    /**
     * Get the order that owns the order line.
     */
    public function order()
    {
        return $this->belongsTo(ShopfrontOrder::class, 'order_id');
    }

    /**
     * Get the product for this order line (if it's a business product).
     */
    public function product()
    {
        if (!$this->is_common_product) {
            return $this->belongsTo(Product::class);
        }
        return null;
    }

    /**
     * Get the common product for this order line (if it's a common product).
     */
    /**
     * Get the common product for this order line.
     */
    public function commonProduct()
    {
        return $this->belongsTo(CommonProduct::class, 'product_id');
    }
}
