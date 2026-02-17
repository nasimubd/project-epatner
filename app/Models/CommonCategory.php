<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommonCategory extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_common_category';
    protected $primaryKey = 'category_id';

    protected $fillable = [
        'category_name',
        'slug',
    ];

    public function products()
    {
        return $this->hasMany(CommonProduct::class, 'category_id', 'category_id');
    }
}
