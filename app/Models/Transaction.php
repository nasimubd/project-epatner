<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TransactionLine;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'transaction_type',
        'transaction_date',
        'amount',
        'narration',
        'shopfront_order_id',
    ];

    // A transaction has many lines
    public function transactionLines()
    {
        return $this->hasMany(TransactionLine::class, 'transaction_id');
    }

    public function inventoryTransaction()
    {
        return $this->belongsTo(InventoryTransaction::class);
    }

    public function entries()
    {
        return $this->hasMany(TransactionLine::class, 'transaction_id');
    }

    // Add this relationship to your Transaction model
    public function shopfrontOrder()
    {
        return $this->belongsTo(ShopfrontOrder::class, 'shopfront_order_id');
    }
}
