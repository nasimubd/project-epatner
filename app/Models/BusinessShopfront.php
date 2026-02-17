<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessShopfront extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'shopfront_id',
        'is_active',
        'qr_code'
    ];

    /**
     * Get the business that owns the shopfront.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the orders for this shopfront.
     */
    public function orders()
    {
        return $this->hasMany(ShopfrontOrder::class, 'business_id', 'business_id');
    }

    public function images()
    {
        return $this->hasMany(ShopfrontImage::class, 'shopfront_id', 'shopfront_id');
    }

    public function heroBanner()
    {
        return $this->hasOne(ShopfrontImage::class, 'shopfront_id', 'shopfront_id')
            ->where('image_type', ShopfrontImage::TYPE_HERO_BANNER)
            ->where('is_active', true);
    }

    public function categoryImages()
    {
        return $this->hasMany(ShopfrontImage::class, 'shopfront_id', 'shopfront_id')
            ->where('image_type', ShopfrontImage::TYPE_CATEGORY)
            ->where('is_active', true);
    }

    public function getCategoryImage($categoryId)
    {
        return $this->categoryImages()->where('reference_id', $categoryId)->first();
    }

    // Add this method to your existing BusinessShopfront model

    public function generalCategoryImages()
    {
        return $this->hasMany(ShopfrontImage::class, 'shopfront_id', 'shopfront_id')
            ->where('image_type', ShopfrontImage::TYPE_GENERAL_CATEGORY)
            ->where('is_active', true);
    }

    // Also add a helper method to get category image by reference name
    public function getGeneralCategoryImage($referenceName)
    {
        return $this->generalCategoryImages()
            ->where('reference_name', $referenceName)
            ->first();
    }
}
