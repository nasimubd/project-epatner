<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DamageTransaction extends Model
{
    protected $fillable = [
        'business_id',
        'supplier_ledger_id',
        'customer_ledger_id',
        'inventory_transaction_id',
        'created_by',
        'transaction_date',
        'status',
        'remarks'
    ];

    public function customer()
    {
        return $this->belongsTo(Ledger::class, 'customer_ledger_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Ledger::class, 'supplier_ledger_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines()
    {
        return $this->hasMany(DamageTransactionLine::class);
    }

    public function customerLedger()
    {
        return $this->belongsTo(Ledger::class, 'customer_ledger_id');
    }

    public function supplierLedger()
    {
        return $this->belongsTo(Ledger::class, 'supplier_ledger_id');
    }

    public function inventoryTransaction()
    {
        return $this->belongsTo(InventoryTransaction::class);
    }
}
