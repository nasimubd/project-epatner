<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommonProduct extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_common_product';
    protected $primaryKey = 'product_id';

    protected $fillable = [
        'product_name',
        'barcode',
        'image',
        'category_id',
        'unit_id',
    ];



    // Define relationships
    public function category()
    {
        return $this->belongsTo(CommonCategory::class, 'category_id', 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(CommonUnit::class, 'unit_id', 'unit_id');
    }
}
