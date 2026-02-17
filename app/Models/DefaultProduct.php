<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DefaultProduct extends Model
{
    protected $fillable = [
        'name',
        'barcode',
        'category_name',
        'brand_name',
        'dealer_price',
        'trade_price',
        'tax'
    ];
}
