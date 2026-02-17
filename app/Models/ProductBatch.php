<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'dealer_price',
        'trade_price',
        'remaining_quantity',
        'batch_date',
        'expiry_date',
        'is_opening_batch'
    ];

    protected $casts = [
        'dealer_price' => 'decimal:2',
        'trade_price' => 'decimal:2',
        'remaining_quantity' => 'decimal:3', // Allow decimal quantities
        'batch_date' => 'date',
        'expiry_date' => 'date',
        'is_opening_batch' => 'boolean'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
