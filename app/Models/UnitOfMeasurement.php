<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitOfMeasurement extends Model
{
    protected $table = 'unit_of_measurements';

    protected $fillable = [
        'type',
        'name',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean'
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'unit_id');
    }
}
