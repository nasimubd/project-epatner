<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'common_product_id',
        'name',
        'barcode',
        'category_id',
        'unit_id',
        'quantity_alert',
        'opening_stock',
        'opening_date',
        'expiry_date',
        'dealer_price',
        'trade_price',
        'profit_margin',
        'tax',
        'image',
        'status',
        'current_stock'
    ];

    protected $casts = [
        'opening_date' => 'date',
        'expiry_date' => 'date',
        'status' => 'boolean'
    ];


    // Add relationship to batches
    public function batches()
    {
        return $this->hasMany(ProductBatch::class);
    }

    // Add method to get current stock considering batches
    public function getCurrentStock()
    {
        return $this->batches()->sum('remaining_quantity');
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function unit()
    {
        return $this->belongsTo(UnitOfMeasurement::class);
    }

    public function inventoryTransactionLines()
    {
        return $this->hasMany(InventoryTransactionLine::class);
    }

    public function damageTransactionLines()
    {
        return $this->hasMany(DamageTransactionLine::class, 'product_id');
    }

    public function returnedProducts()
    {
        return $this->hasMany(ReturnedProduct::class, 'product_id');
    }


    public function scopeWithCommon($query, $productId)
    {
        $localProduct = $query->find($productId);

        if (!$localProduct) {
            // Return a product instance from common database
            $commonProduct = CommonProduct::find($productId);
            if ($commonProduct) {
                return new static([
                    'id' => $commonProduct->product_id,
                    'name' => $commonProduct->product_name,
                    'barcode' => $commonProduct->barcode,
                    'category_id' => $commonProduct->category_id,
                    'unit_id' => $commonProduct->unit_id,
                    'is_common' => true
                ]);
            }
        }

        return $localProduct;
    }
}
