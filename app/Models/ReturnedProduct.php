<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnedProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'inventory_transaction_id',
        'product_id',
        'batch_id',
        'quantity',
        'unit_price',
        'total_amount',
        'line_discount',
        'return_reason',
        'created_by',
        'return_date',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function inventoryTransaction()
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
