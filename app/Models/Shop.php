<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_shops';
    protected $primaryKey = 'shop_id';

    protected $fillable = [
        'shop_name',
        'shop_code',
        'business_id',
        'address',
        'contact_number',
        'email',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // RELATIONSHIPS

    /**
     * Relationship to customer ledgers
     */
    public function customerLedgers()
    {
        return $this->hasMany(CustomerLedger::class, 'shop_id', 'shop_id');
    }

    /**
     * Relationship to customer shop accounts
     */
    public function customerShopAccounts()
    {
        return $this->hasMany(CustomerShopAccount::class, 'shop_id', 'shop_id');
    }

    // SCOPES

    /**
     * Scope for active shops
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for shops by business
     */
    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    // ACCESSORS & MUTATORS

    /**
     * Get the shop's display name
     */
    public function getDisplayNameAttribute()
    {
        return $this->shop_name . ' (' . $this->shop_code . ')';
    }
}
