<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerShopAccount extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_customer_shop_accounts';
    protected $primaryKey = 'account_id';

    protected $fillable = [
        'ledger_id',
        'shop_id',
        'account_number',
        'account_type',
        'balance',
        'credit_limit',
        'status',
        'last_transaction_at',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'credit_limit' => 'decimal:2',
        'status' => 'boolean',
        'last_transaction_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    // RELATIONSHIPS

    /**
     * Relationship to customer ledger
     */
    public function customerLedger()
    {
        return $this->belongsTo(CustomerLedger::class, 'ledger_id', 'ledger_id');
    }

    /**
     * Relationship to shop
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

    // SCOPES

    /**
     * Scope for active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    /**
     * Scope for accounts by shop
     */
    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope for accounts by customer
     */
    public function scopeByCustomer($query, $ledgerId)
    {
        return $query->where('ledger_id', $ledgerId);
    }

    /**
     * Scope for accounts with balance
     */
    public function scopeWithBalance($query)
    {
        return $query->where('balance', '!=', 0);
    }

    // ACCESSORS & MUTATORS

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute()
    {
        return number_format($this->balance, 2);
    }

    /**
     * Get account status text
     */
    public function getStatusTextAttribute()
    {
        return $this->status ? 'Active' : 'Inactive';
    }
}
