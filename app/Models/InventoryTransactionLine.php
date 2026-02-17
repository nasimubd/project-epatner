<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransactionLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_transaction_id',
        'product_id',
        'batch_id',
        'quantity',
        'unit_price',
        'dealer_price',
        'line_discount',
        'line_total',
    ];

    public function transaction()
    {
        return $this->belongsTo(InventoryTransaction::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class);
    }

    public function inventoryTransaction()
    {
        return $this->belongsTo(InventoryTransaction::class);
    }
}
