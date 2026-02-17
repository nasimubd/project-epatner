<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class LocationData extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_location_data';

    protected $fillable = [
        'district',
        'sub_district',
        'village'
    ];

    /**
     * Scope for searching locations
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('district', 'like', "%{$search}%")
                ->orWhere('sub_district', 'like', "%{$search}%")
                ->orWhere('village', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for filtering by district
     */
    public function scopeByDistrict($query, $district)
    {
        return $query->where('district', $district);
    }

    /**
     * Scope for filtering by sub-district
     */
    public function scopeBySubDistrict($query, $subDistrict)
    {
        return $query->where('sub_district', $subDistrict);
    }

    /**
     * Get all districts
     */
    public static function getDistricts()
    {
        return static::on('mysql_common')
            ->distinct()
            ->orderBy('district')
            ->pluck('district');
    }

    /**
     * Get sub-districts by district
     */
    public static function getSubDistricts($district = null)
    {
        $query = static::on('mysql_common')->distinct();

        if ($district) {
            $query->where('district', $district);
        }

        return $query->orderBy('sub_district')->pluck('sub_district');
    }

    /**
     * Get villages by district and sub-district
     */
    public static function getVillages($district = null, $subDistrict = null)
    {
        $query = static::on('mysql_common')->distinct();

        if ($district) {
            $query->where('district', $district);
        }

        if ($subDistrict) {
            $query->where('sub_district', $subDistrict);
        }

        return $query->orderBy('village')->pluck('village');
    }

    /**
     * Check if location combination exists
     */
    public static function locationExists($district, $subDistrict, $village, $excludeId = null)
    {
        $query = static::on('mysql_common')
            ->where('district', $district)
            ->where('sub_district', $subDistrict)
            ->where('village', $village);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    // NEW METHODS FOR MULTI-SHOP SYSTEM

    /**
     * Get location hierarchy for cross-shop analysis
     */
    public static function getLocationHierarchy($shopId = null)
    {
        $query = DB::connection('mysql_common')
            ->table('tbl_location_data as ld')
            ->leftJoin('tbl_customer_ledgers as cl', function ($join) use ($shopId) {
                $join->on('ld.district', '=', 'cl.district')
                    ->on('ld.sub_district', '=', 'cl.sub_district')
                    ->on('ld.village', '=', 'cl.village');
                if ($shopId) {
                    $join->where('cl.shop_id', $shopId);
                }
            })
            ->select([
                'ld.district',
                'ld.sub_district',
                'ld.village',
                DB::raw('COUNT(cl.ledger_id) as customer_count'),
                DB::raw('COUNT(DISTINCT cl.shop_id) as shop_count')
            ])
            ->groupBy('ld.district', 'ld.sub_district', 'ld.village')
            ->orderBy('ld.district')
            ->orderBy('ld.sub_district')
            ->orderBy('ld.village');

        return $query->get();
    }

    /**
     * Find similar locations across all shops
     */
    public static function findSimilarLocations($district, $subDistrict, $village, $threshold = 70)
    {
        $allLocations = static::on('mysql_common')
            ->select('district', 'sub_district', 'village')
            ->distinct()
            ->get();

        $similarLocations = [];

        foreach ($allLocations as $location) {
            $similarity = static::calculateLocationSimilarity(
                [$district, $subDistrict, $village],
                [$location->district, $location->sub_district, $location->village]
            );

            if ($similarity >= $threshold && $similarity < 100) {
                $similarLocations[] = [
                    'location' => $location,
                    'similarity' => $similarity,
                    'customer_count' => static::getCustomerCountForLocation(
                        $location->district,
                        $location->sub_district,
                        $location->village
                    )
                ];
            }
        }

        // Sort by similarity descending
        usort($similarLocations, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return collect($similarLocations);
    }

    /**
     * Get customer count for a specific location across all shops
     */
    public static function getCustomerCountForLocation($district, $subDistrict, $village)
    {
        return DB::connection('mysql_common')
            ->table('tbl_customer_ledgers')
            ->where('district', $district)
            ->where('sub_district', $subDistrict)
            ->where('village', $village)
            ->where('is_merged', false)
            ->count();
    }

    /**
     * Get shop distribution for a location
     */
    public static function getShopDistributionForLocation($district, $subDistrict, $village)
    {
        return DB::connection('mysql_common')
            ->table('tbl_customer_ledgers as cl')
            ->join('tbl_shops as s', 'cl.shop_id', '=', 's.shop_id')
            ->where('cl.district', $district)
            ->where('cl.sub_district', $subDistrict)
            ->where('cl.village', $village)
            ->where('cl.is_merged', false)
            ->select([
                's.shop_id',
                's.shop_name',
                's.shop_code',
                DB::raw('COUNT(cl.ledger_id) as customer_count')
            ])
            ->groupBy('s.shop_id', 's.shop_name', 's.shop_code')
            ->orderBy('customer_count', 'desc')
            ->get();
    }

    /**
     * Calculate location similarity percentage
     */
    public static function calculateLocationSimilarity($location1, $location2)
    {
        $totalSimilarity = 0;
        $validComparisons = 0;

        // Compare each level of the location hierarchy
        for ($i = 0; $i < min(count($location1), count($location2)); $i++) {
            if (!empty($location1[$i]) && !empty($location2[$i])) {
                $similarity = static::calculateStringSimilarity($location1[$i], $location2[$i]);

                // Weight the similarity based on hierarchy level
                $weight = 1;
                switch ($i) {
                    case 0: // District - highest weight
                        $weight = 3;
                        break;
                    case 1: // Sub District - medium weight
                        $weight = 2;
                        break;
                    case 2: // Village - normal weight
                        $weight = 1;
                        break;
                }

                $totalSimilarity += ($similarity * $weight);
                $validComparisons += $weight;
            }
        }

        return $validComparisons > 0 ? round($totalSimilarity / $validComparisons, 2) : 0;
    }

    /**
     * Calculate string similarity using multiple algorithms
     */
    public static function calculateStringSimilarity($str1, $str2)
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 100;
        }

        // Use multiple similarity algorithms and take the best result
        $similarities = [];

        // 1. Levenshtein distance
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen > 0) {
            $distance = levenshtein($str1, $str2);
            $similarities[] = (1 - $distance / $maxLen) * 100;
        }

        // 2. Similar text percentage
        similar_text($str1, $str2, $percent);
        $similarities[] = $percent;

        // 3. Soundex comparison
        if (soundex($str1) === soundex($str2)) {
            $similarities[] = 85; // High similarity for phonetic match
        }

        // 4. Substring matching
        $substringSimilarity = 0;
        if (strpos($str1, $str2) !== false || strpos($str2, $str1) !== false) {
            $substringSimilarity = 75;
        }
        $similarities[] = $substringSimilarity;

        // Return the highest similarity score
        return round(max($similarities), 2);
    }

    /**
     * Get location suggestions for autocomplete (cross-shop)
     */
    public static function getLocationSuggestions($searchTerm, $type = 'all', $limit = 10)
    {
        $query = static::on('mysql_common');

        switch ($type) {
            case 'district':
                $query->select('district as suggestion')
                    ->distinct()
                    ->where('district', 'like', "%{$searchTerm}%")
                    ->orderBy('district');
                break;

            case 'sub_district':
                $query->select('sub_district as suggestion')
                    ->distinct()
                    ->where('sub_district', 'like', "%{$searchTerm}%")
                    ->orderBy('sub_district');
                break;

            case 'village':
                $query->select('village as suggestion')
                    ->distinct()
                    ->where('village', 'like', "%{$searchTerm}%")
                    ->orderBy('village');
                break;

            default:
                $query->select(DB::raw("CONCAT(district, ', ', sub_district, ', ', village) as suggestion"))
                    ->where(function ($q) use ($searchTerm) {
                        $q->where('district', 'like', "%{$searchTerm}%")
                            ->orWhere('sub_district', 'like', "%{$searchTerm}%")
                            ->orWhere('village', 'like', "%{$searchTerm}%");
                    })
                    ->orderBy('district')
                    ->orderBy('sub_district')
                    ->orderBy('village');
        }

        return $query->limit($limit)->pluck('suggestion');
    }

    /**
     * Validate location data quality
     */
    public static function validateLocationQuality($district, $subDistrict, $village)
    {
        $score = 0;
        $maxScore = 100;
        $issues = [];

        // Check if all fields are provided (30 points)
        if (!empty($district) && !empty($subDistrict) && !empty($village)) {
            $score += 30;
        } else {
            $issues[] = 'Missing required location fields';
        }

        // Check field lengths (20 points)
        if (strlen($district) >= 3 && strlen($subDistrict) >= 3 && strlen($village) >= 3) {
            $score += 20;
        } else {
            $issues[] = 'Location names too short (minimum 3 characters)';
        }

        // Check if location exists in master data (30 points)
        if (static::locationExists($district, $subDistrict, $village)) {
            $score += 30;
        } else {
            $issues[] = 'Location not found in master location data';
        }

        // Check for common formatting issues (20 points)
        $formatScore = 20;
        if (preg_match('/[0-9]/', $district . $subDistrict . $village)) {
            $formatScore -= 5;
            $issues[] = 'Location names contain numbers';
        }
        if (preg_match('/[^a-zA-Z0-9\s\-]/', $district . $subDistrict . $village)) {
            $formatScore -= 5;
            $issues[] = 'Location names contain special characters';
        }
        $score += $formatScore;

        return [
            'score' => min($score, $maxScore),
            'grade' => static::getQualityGrade($score),
            'issues' => $issues
        ];
    }

    /**
     * Get quality grade based on score
     */
    private static function getQualityGrade($score)
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }
}
