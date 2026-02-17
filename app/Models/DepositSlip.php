<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DepositSlip extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'ledger_id',
        'total_amount',
        'total_collection',
        'due_collection',
        'net_total',
        'note_denominations',
        'damage_lines',
        'market_short',
        'godown_short',
        'remarks',
        'status'
    ];

    protected $casts = [
        'note_denominations' => 'array',
        'damage_lines' => 'array',
        'total_amount' => 'decimal:2',
        'total_collection' => 'decimal:2',
        'due_collection' => 'decimal:2',
        'net_total' => 'decimal:2',
        'market_short' => 'decimal:2',
        'godown_short' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }
}
