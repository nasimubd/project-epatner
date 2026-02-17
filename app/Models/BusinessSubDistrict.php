<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BusinessSubDistrict extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'district',
        'sub_district',
        'status'
    ];

    /**
     * Get the business that owns the sub-district
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope to get active sub-districts
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Get unique villages for business sub-districts from tbl_customer_ledgers
     */
    public static function getVillagesForBusiness($businessId)
    {
        $subDistricts = static::where('business_id', $businessId)
            ->active()
            ->get(['district', 'sub_district']);

        if ($subDistricts->isEmpty()) {
            return collect();
        }

        // Get villages from common database based on business sub-districts
        $villages = collect();

        foreach ($subDistricts as $subDistrict) {
            try {
                $commonVillages = DB::connection('mysql_common')
                    ->table('tbl_customer_ledgers')
                    ->where('district', $subDistrict->district)
                    ->where('sub_district', $subDistrict->sub_district)
                    ->whereNotNull('village')
                    ->where('village', '!=', '')
                    ->where('village', '!=', 'NULL')
                    ->where('status', '!=', 'inactive')
                    ->distinct()
                    ->pluck('village');

                $villages = $villages->merge($commonVillages);
            } catch (\Exception $e) {
                Log::error("Error fetching villages for {$subDistrict->district}/{$subDistrict->sub_district}: " . $e->getMessage());
            }
        }

        return $villages->unique()->sort()->values();
    }

    /**
     * Get customers count for this sub-district
     */
    public function getCustomersCount()
    {
        try {
            return DB::connection('mysql_common')
                ->table('tbl_customer_ledgers')
                ->where('district', $this->district)
                ->where('sub_district', $this->sub_district)
                ->where('status', '!=', 'inactive')
                ->count();
        } catch (\Exception $e) {
            Log::error("Error getting customer count for {$this->district}/{$this->sub_district}: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get villages for this specific sub-district
     */
    public function getVillages()
    {
        try {
            return DB::connection('mysql_common')
                ->table('tbl_customer_ledgers')
                ->where('district', $this->district)
                ->where('sub_district', $this->sub_district)
                ->whereNotNull('village')
                ->where('village', '!=', '')
                ->where('village', '!=', 'NULL')
                ->where('status', '!=', 'inactive')
                ->distinct()
                ->orderBy('village')
                ->pluck('village');
        } catch (\Exception $e) {
            Log::error("Error getting villages for {$this->district}/{$this->sub_district}: " . $e->getMessage());
            return collect();
        }
    }
}
