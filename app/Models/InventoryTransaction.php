<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'entry_type',
        'transaction_date',
        'ledger_id',
        'payment_method',
        'subtotal',
        'round_off',
        'discount',
        'grand_total',
        'invoice_id',
    ];

    protected $casts = [
        'transaction_date' => 'datetime'
    ];


    public function lines()
    {
        return $this->hasMany(InventoryTransactionLine::class);
    }

    public function creators()
    {
        return $this->belongsToMany(Staff::class, 'inventory_transaction_contributors', 'transaction_id', 'staff_id')
            ->withPivot(['product_category_id', 'product_id']) // Add product_id to pivot
            ->withTimestamps();
    }

    public function contributors()
    {
        return $this->belongsToMany(Staff::class, 'inventory_transaction_contributors', 'transaction_id', 'staff_id')
            ->withPivot(['product_category_id', 'product_id'])
            ->withTimestamps();
    }


    public function damageTransactions()
    {
        return $this->hasMany(DamageTransaction::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }
    // NEWLY ADDED
    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'inventory_transaction_lines', 'inventory_transaction_id', 'product_id')
            ->withPivot(['quantity', 'unit_price', 'line_total', 'batch_id'])
            ->withTimestamps();
    }


    /**
     * Get the primary staff member associated with this transaction.
     * This is a convenience method that returns the first creator.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /**
     * Get all returned products for this transaction
     */
    public function returned_products()
    {
        return $this->hasMany(ReturnedProduct::class, 'inventory_transaction_id');
    }
}
