<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Business extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'contact_number',
        'email',
        'district'
    ];

    public function admins()
    {
        return $this->hasMany(BusinessAdmin::class);
    }

    public function users()
    {
        return $this->hasManyThrough(User::class, BusinessAdmin::class, 'business_id', 'id', 'id', 'user_id');
    }
    public function ledgers()
    {
        return $this->hasMany(Ledger::class);
    }

    /**
     * Get the sub-districts for this business
     */
    public function subDistricts()
    {
        return $this->hasMany(BusinessSubDistrict::class);
    }

    /**
     * Get active sub-districts for this business
     */
    public function activeSubDistricts()
    {
        return $this->hasMany(BusinessSubDistrict::class)->active();
    }
}
