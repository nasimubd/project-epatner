<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommonUnit extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_common_unit';
    protected $primaryKey = 'unit_id';

    protected $fillable = [
        'unit_name',
    ];

    public function products()
    {
        return $this->hasMany(CommonProduct::class, 'unit_id', 'unit_id');
    }
}
