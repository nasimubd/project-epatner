<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageTransactionLine extends Model
{
    protected $fillable = [
        'damage_transaction_id',
        'product_id',
        'quantity',
        'unit_price',
        'total_value',
        'damage_reason'
    ];

    public function damageTransaction()
    {
        return $this->belongsTo(DamageTransaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getBatchAttribute()
    {
        // Get the most recent batch for this product
        return ProductBatch::where('product_id', $this->product_id)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
