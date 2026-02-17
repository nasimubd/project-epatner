<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TransactionLine;

class Ledger extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'contact',
        'location',
        'ledger_type',
        'opening_balance',
        'balance_type',
        'current_balance',
        'common_customer_id',
        'status'
    ];

    // If you have relationships:
    // e.g., A ledger can have many transaction lines
    public function transactionLines()
    {
        return $this->hasMany(TransactionLine::class, 'ledger_id');
    }
    // In Ledger Model
    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    /**
     * Scope to get customer ledgers
     */
    public function scopeCustomers($query)
    {
        return $query->where('ledger_type', 'Sundry Debtors (Customer)');
    }

    /**
     * Check if this is a common customer
     */
    public function isCommonCustomer()
    {
        return !is_null($this->common_customer_id);
    }
}
