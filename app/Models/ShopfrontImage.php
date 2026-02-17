<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopfrontImage extends Model
{
    use HasFactory;

    const TYPE_HERO_BANNER = 'hero_banner';
    const TYPE_CATEGORY = 'category';
    const TYPE_GENERAL_CATEGORY = 'general_category';

    protected $fillable = [
        'shopfront_id',
        'image_type',
        'reference_id',
        'reference_name',
        'image',
        'image_name',
        'mime_type',
        'image_size',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'image_size' => 'integer'
    ];

    // Accessor to get image data URI for display (similar to Product model)
    public function getImageDataUriAttribute()
    {
        if ($this->image) {
            return 'data:image/jpeg;base64,' . base64_encode($this->image);
        }
        return null;
    }

    // Relationship to shopfront
    public function shopfront()
    {
        return $this->belongsTo(BusinessShopfront::class, 'shopfront_id', 'shopfront_id');
    }

    // Relationship to category (when image_type is 'category')
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'reference_id');
    }
}
