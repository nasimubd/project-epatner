<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductCategory extends Model
{
    protected $fillable = [
        'business_id',
        'ledger_id',
        'name',
        'slug',
        'status',
        'common_category_id'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'category_id');
    }

    public function staffs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'category_staff', 'product_category_id', 'user_id');
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }
}
