<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Log;

class CustomerLedger extends Model
{
    use HasFactory;

    protected $connection = 'mysql_common';
    protected $table = 'tbl_customer_ledgers';
    protected $primaryKey = 'ledger_id';

    protected $fillable = [
        'ledger_name',
        'type',
        'contact_number',
        'district',
        'sub_district',
        'village',
        'landmark',
        'location',
        'customer_source',
        'customer_status',
        'status',
        'qr_code_svg',
        'qr_code_data',
        'qr_generated_at',
        'global_customer_uuid',
        'data_quality_score',
        'duplicate_flags',
        'is_merged',
        'merged_into'
    ];

    protected $casts = [
        'duplicate_flags' => 'array',
        'is_merged' => 'boolean',
        'qr_generated_at' => 'datetime'
    ];

    // RELATIONSHIPS

    public function getStatusLabelAttribute()
    {
        $statusLabels = [
            'active' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>',
            'inactive' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>',
            'suspended' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Suspended</span>',
            'blocked' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Blocked</span>'
        ];

        return $statusLabels[$this->status] ?? $statusLabels['active'];
    }

    /**
     * Generate and store permanent QR code for customer
     */
    public function generatePermanentQrCode()
    {
        try {
            // Create QR data with customer information
            $qrData = [
                'type' => 'customer',
                'id' => $this->ledger_id,
                'name' => $this->ledger_name,
                'phone' => $this->contact_number,
                'uuid' => $this->global_customer_uuid,
                'generated_at' => now()->toISOString()
            ];

            $qrDataString = json_encode($qrData);

            // Generate QR code as SVG
            $qrCodeSvg = QrCode::format('svg')
                ->size(200)
                ->margin(1)
                ->generate($qrDataString);

            // Store in database
            $this->update([
                'qr_code_svg' => $qrCodeSvg,
                'qr_code_data' => $qrDataString,
                'qr_generated_at' => now()
            ]);

            return $qrCodeSvg;
        } catch (\Exception $e) {
            Log::error('QR Code generation failed for customer ' . $this->ledger_id . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if customer has QR code
     */
    public function hasQrCode()
    {
        return !empty($this->qr_code_svg);
    }

    /**
     * Get QR code or generate if not exists
     */
    public function getQrCode()
    {
        if (!$this->hasQrCode()) {
            return $this->generatePermanentQrCode();
        }

        return $this->qr_code_svg;
    }

    /**
     * Relationship to shop
     */
    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id', 'shop_id');
    }

    /**
     * Relationship to customer shop accounts
     */
    public function shopAccounts()
    {
        return $this->hasMany(CustomerShopAccount::class, 'ledger_id', 'ledger_id');
    }

    /**
     * Relationship to merged customers (customers merged into this one)
     */
    public function mergedCustomers()
    {
        return $this->hasMany(CustomerLedger::class, 'merged_into', 'ledger_id');
    }

    /**
     * Relationship to the customer this was merged into
     */
    public function mergedInto()
    {
        return $this->belongsTo(CustomerLedger::class, 'merged_into', 'ledger_id');
    }

    /**
     * Get all customers with the same global UUID (across shops)
     */
    public function globalCustomers()
    {
        return $this->hasMany(CustomerLedger::class, 'global_customer_uuid', 'global_customer_uuid')
            ->where('ledger_id', '!=', $this->ledger_id);
    }

    // SCOPES

    /**
     * Scope for active (non-merged) customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_merged', false);
    }

    /**
     * Scope for customers by shop
     */
    public function scopeByShop($query, $shopId)
    {
        return $query->where('shop_id', $shopId);
    }

    /**
     * Scope for customers by global UUID
     */
    public function scopeByGlobalUuid($query, $uuid)
    {
        return $query->where('global_customer_uuid', $uuid);
    }

    /**
     * Scope for customers by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('customer_status', $status);
    }

    /**
     * Scope for searching customers across all fields
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('ledger_name', 'like', "%{$search}%")
                ->orWhere('contact_number', 'like', "%{$search}%")
                ->orWhere('district', 'like', "%{$search}%")
                ->orWhere('sub_district', 'like', "%{$search}%")
                ->orWhere('village', 'like', "%{$search}%")
                ->orWhere('landmark', 'like', "%{$search}%")
                ->orWhere('global_customer_uuid', 'like', "%{$search}%");
        });
    }

    // CROSS-SHOP DUPLICATE DETECTION METHODS

    /**
     * Find potential duplicates across all shops
     */
    public static function findCrossShopDuplicates($customerData, $excludeId = null, $confidenceThreshold = 60)
    {
        $duplicates = [];

        // 1. Phone number duplicates (EXACT match - highest priority)
        if (!empty($customerData['phone'])) {
            $phoneMatches = static::on('mysql_common')
                ->active()
                ->where('contact_number', $customerData['phone'])
                ->when($excludeId, function ($query) use ($excludeId) {
                    return $query->where('ledger_id', '!=', $excludeId);
                })
                ->with('shop')
                ->get();

            foreach ($phoneMatches as $match) {
                $duplicates[] = [
                    'type' => 'phone',
                    'confidence' => 100,
                    'customer' => $match,
                    'reason' => 'Exact phone number match',
                    'shop_info' => $match->shop,
                    'risk_level' => 'critical'
                ];
            }
        }

        // 2. Name-based duplicates with location context
        if (!empty($customerData['name'])) {
            $nameMatches = static::findNameBasedDuplicates(
                $customerData['name'],
                $customerData,
                $excludeId
            );
            $duplicates = array_merge($duplicates, $nameMatches);
        }

        // 3. Location-based duplicates
        if (!empty($customerData['district']) && !empty($customerData['sub_district']) && !empty($customerData['village'])) {
            $locationMatches = static::findLocationBasedDuplicates(
                $customerData,
                $excludeId
            );
            $duplicates = array_merge($duplicates, $locationMatches);
        }

        // 4. Global UUID matches (same customer across shops)
        if (!empty($customerData['global_customer_uuid'])) {
            $globalMatches = static::findGlobalUuidMatches(
                $customerData['global_customer_uuid'],
                $excludeId
            );
            $duplicates = array_merge($duplicates, $globalMatches);
        }

        // Remove duplicates and filter by confidence threshold
        $uniqueDuplicates = collect($duplicates)
            ->unique(function ($item) {
                return $item['customer']->ledger_id;
            })
            ->filter(function ($item) use ($confidenceThreshold) {
                return $item['confidence'] >= $confidenceThreshold;
            })
            ->sortByDesc('confidence')
            ->values();

        return $uniqueDuplicates;
    }

    /**
     * Find name-based duplicates with enhanced algorithms
     */
    private static function findNameBasedDuplicates($name, $customerData, $excludeId = null)
    {
        $duplicates = [];

        if (strlen($name) < 3) {
            return $duplicates;
        }

        // Get potential name matches using multiple strategies
        $nameMatches = CustomerLedger::on('mysql_common')
            ->active()
            ->where(function ($query) use ($name) {
                $query->where('ledger_name', 'like', "%{$name}%")
                    ->orWhereRaw('SOUNDEX(ledger_name) = SOUNDEX(?)', [$name]);
            })
            ->when($excludeId, function ($query) use ($excludeId) {
                return $query->where('ledger_id', '!=', $excludeId);
            })
            ->with('shop')
            ->get();

        foreach ($nameMatches as $match) {
            $nameSimilarity = self::calculateAdvancedNameSimilarity($name, $match->ledger_name);

            if ($nameSimilarity >= 40) {
                // Boost confidence if location context matches
                $locationBoost = 0;
                if (!empty($customerData['district']) && $match->district === $customerData['district']) {
                    $locationBoost += 20;
                    if (!empty($customerData['sub_district']) && $match->sub_district === $customerData['sub_district']) {
                        $locationBoost += 15;
                        if (!empty($customerData['village']) && $match->village === $customerData['village']) {
                            $locationBoost += 25;
                        }
                    }
                }

                $finalConfidence = min(100, $nameSimilarity + $locationBoost);

                $duplicates[] = [
                    'type' => 'name',
                    'confidence' => $finalConfidence,
                    'customer' => $match,
                    'reason' => "Name similarity: {$nameSimilarity}%" . ($locationBoost > 0 ? " + Location context: +{$locationBoost}%" : ""),
                    'shop_info' => $match->shop,
                    'risk_level' => $finalConfidence >= 80 ? 'high' : ($finalConfidence >= 60 ? 'medium' : 'low')
                ];
            }
        }

        return $duplicates;
    }

    /**
     * Find location-based duplicates
     */
    private static function findLocationBasedDuplicates($customerData, $excludeId = null)
    {
        $duplicates = [];

        // Exact location matches
        $exactMatches = static::on('mysql_common')
            ->active()
            ->where('district', $customerData['district'])
            ->where('sub_district', $customerData['sub_district'])
            ->where('village', $customerData['village'])
            ->when($excludeId, function ($query) use ($excludeId) {
                return $query->where('ledger_id', '!=', $excludeId);
            })
            ->with('shop')
            ->get();

        foreach ($exactMatches as $match) {
            $duplicates[] = [
                'type' => 'location',
                'confidence' => 95,
                'customer' => $match,
                'reason' => 'Exact location match',
                'shop_info' => $match->shop,
                'risk_level' => 'high'
            ];
        }

        // Similar location matches
        $similarMatches = static::on('mysql_common')
            ->active()
            ->where('district', $customerData['district'])
            ->where('sub_district', $customerData['sub_district'])
            ->where('village', '!=', $customerData['village'])
            ->when($excludeId, function ($query) use ($excludeId) {
                return $query->where('ledger_id', '!=', $excludeId);
            })
            ->with('shop')
            ->get();

        foreach ($similarMatches as $match) {
            $villageSimilarity = LocationData::calculateStringSimilarity(
                $customerData['village'],
                $match->village
            );

            if ($villageSimilarity >= 70) {
                $duplicates[] = [
                    'type' => 'location',
                    'confidence' => $villageSimilarity,
                    'customer' => $match,
                    'reason' => "Similar village name: {$villageSimilarity}%",
                    'shop_info' => $match->shop,
                    'risk_level' => $villageSimilarity >= 85 ? 'medium' : 'low'
                ];
            }
        }

        return $duplicates;
    }

    /**
     * Find global UUID matches (same customer across shops)
     */
    private static function findGlobalUuidMatches($globalUuid, $excludeId = null)
    {
        $duplicates = [];

        $globalMatches = static::on('mysql_common')
            ->active()
            ->where('global_customer_uuid', $globalUuid)
            ->when($excludeId, function ($query) use ($excludeId) {
                return $query->where('ledger_id', '!=', $excludeId);
            })
            ->with('shop')
            ->get();

        foreach ($globalMatches as $match) {
            $duplicates[] = [
                'type' => 'global',
                'confidence' => 100,
                'customer' => $match,
                'reason' => 'Same customer in different shop',
                'shop_info' => $match->shop,
                'risk_level' => 'info' // This is expected behavior
            ];
        }

        return $duplicates;
    }

    /**
     * Calculate advanced name similarity using multiple algorithms
     */
    private static function calculateAdvancedNameSimilarity($name1, $name2)
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));

        if ($name1 === $name2) {
            return 100;
        }

        $similarities = [];

        // 1. Levenshtein distance
        $maxLen = max(strlen($name1), strlen($name2));
        if ($maxLen > 0) {
            $distance = levenshtein($name1, $name2);
            $similarities[] = (1 - $distance / $maxLen) * 100;
        }

        // 2. Similar text percentage
        similar_text($name1, $name2, $percent);
        $similarities[] = $percent;

        // 3. Soundex comparison
        if (soundex($name1) === soundex($name2)) {
            $similarities[] = 85;
        }

        // 4. Word-based comparison (for multi-word names)
        $words1 = explode(' ', $name1);
        $words2 = explode(' ', $name2);
        $wordMatches = 0;
        $totalWords = max(count($words1), count($words2));

        foreach ($words1 as $word1) {
            foreach ($words2 as $word2) {
                if (strlen($word1) >= 3 && strlen($word2) >= 3) {
                    $wordSimilarity = similar_text($word1, $word2, $percent);
                    if ($percent >= 80) {
                        $wordMatches++;
                        break;
                    }
                }
            }
        }

        if ($totalWords > 0) {
            $similarities[] = ($wordMatches / $totalWords) * 100;
        }

        // 5. Initials comparison
        $initials1 = static::getInitials($name1);
        $initials2 = static::getInitials($name2);
        if ($initials1 === $initials2 && strlen($initials1) >= 2) {
            $similarities[] = 70;
        }

        return round(max($similarities), 2);
    }

    /**
     * Get initials from a name
     */
    private static function getInitials($name)
    {
        $words = explode(' ', trim($name));
        $initials = '';

        foreach ($words as $word) {
            if (strlen($word) > 0) {
                $initials .= strtoupper($word[0]);
            }
        }

        return $initials;
    }

    /**
     * Calculate data quality score for a customer
     */
    public function calculateDataQualityScore()
    {
        $score = 0;
        $maxScore = 100;

        // Name quality (25 points)
        if (!empty($this->ledger_name)) {
            $score += 15;
            if (strlen($this->ledger_name) >= 3) {
                $score += 5;
            }
            if (preg_match('/^[a-zA-Z\s]+$/', $this->ledger_name)) {
                $score += 5;
            }
        }

        // Contact information (25 points)
        if (!empty($this->contact_number)) {
            $score += 15;
            if (preg_match('/^[0-9+\-\s()]+$/', $this->contact_number) && strlen($this->contact_number) >= 10) {
                $score += 10;
            }
        }

        // Location information (30 points)
        if (!empty($this->district) && !empty($this->sub_district) && !empty($this->village)) {
            $score += 20;

            // Check if location exists in master data
            if (LocationData::locationExists($this->district, $this->sub_district, $this->village)) {
                $score += 10;
            }
        }

        // Additional information (20 points)
        if (!empty($this->type)) {
            $score += 5;
        }
        if (!empty($this->landmark)) {
            $score += 5;
        }
        if (!empty($this->global_customer_uuid)) {
            $score += 5;
        }
        if ($this->shop_id) {
            $score += 5;
        }

        return min($score, $maxScore);
    }

    /**
     * Get data quality grade based on score
     */
    public function getDataQualityGrade()
    {
        $score = $this->data_quality_score ?? $this->calculateDataQualityScore();

        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    /**
     * Update data quality score
     */
    public function updateDataQualityScore()
    {
        $this->data_quality_score = $this->calculateDataQualityScore();
        $this->save();

        return $this->data_quality_score;
    }

    /**
     * Get duplicate risk assessment
     */
    public function getDuplicateRiskAssessment()
    {
        $riskFactors = [];
        $riskScore = 0;

        // Check for common risk patterns
        if (empty($this->contact_number)) {
            $riskFactors[] = 'No phone number provided';
            $riskScore += 30;
        }

        if (empty($this->district) || empty($this->sub_district) || empty($this->village)) {
            $riskFactors[] = 'Incomplete location information';
            $riskScore += 25;
        }

        if (strlen($this->ledger_name) < 3) {
            $riskFactors[] = 'Very short name';
            $riskScore += 20;
        }

        // Check for common duplicate patterns
        $commonNames = ['test', 'customer', 'user', 'temp', 'demo'];
        foreach ($commonNames as $commonName) {
            if (stripos($this->ledger_name, $commonName) !== false) {
                $riskFactors[] = 'Contains common/test name pattern';
                $riskScore += 40;
                break;
            }
        }

        // Check for sequential patterns in name
        if (preg_match('/\d+$/', $this->ledger_name)) {
            $riskFactors[] = 'Name ends with numbers (possible sequential duplicate)';
            $riskScore += 15;
        }

        $riskLevel = 'low';
        if ($riskScore >= 60) {
            $riskLevel = 'high';
        } elseif ($riskScore >= 30) {
            $riskLevel = 'medium';
        }

        return [
            'risk_level' => $riskLevel,
            'risk_score' => min($riskScore, 100),
            'risk_factors' => $riskFactors
        ];
    }

    /**
     * Generate QR code for customer (following BusinessShopfront pattern)
     */
    public function generateQrCode()
    {
        // Generate unique QR identifier (similar to shopfront_id)
        $qrIdentifier = 'CUST-' . strtoupper(Str::random(8));

        // Create QR code data - customer info in JSON format
        $qrData = json_encode([
            'type' => 'customer',
            'customer_id' => $this->ledger_id,
            'qr_id' => $qrIdentifier,
            'name' => $this->ledger_name,
            'phone' => $this->contact_number
        ]);

        // Generate QR code (same as BusinessShopfront)
        $qrCode = QrCode::size(300)->generate($qrData);

        // Store QR code in database (same pattern as BusinessShopfront)
        $this->qr_code = $qrCode;
        $this->save();

        return $qrCode;
    }



    /**
     * Get customer's cross-shop presence
     */
    public function getCrossShopPresence()
    {
        if (empty($this->global_customer_uuid)) {
            return collect();
        }

        return static::on('mysql_common')
            ->active()
            ->where('global_customer_uuid', $this->global_customer_uuid)
            ->where('ledger_id', '!=', $this->ledger_id)
            ->with('shop')
            ->get()
            ->map(function ($customer) {
                return [
                    'shop' => $customer->shop,
                    'customer_id' => $customer->ledger_id,
                    'customer_name' => $customer->ledger_name,
                    'created_at' => $customer->created_at,
                    'last_activity' => $customer->last_activity_at
                ];
            });
    }

    /**
     * Check if customer exists in specific shop
     */
    public function existsInShop($shopId)
    {
        if (empty($this->global_customer_uuid)) {
            return false;
        }

        return static::on('mysql_common')
            ->active()
            ->where('global_customer_uuid', $this->global_customer_uuid)
            ->where('shop_id', $shopId)
            ->exists();
    }

    /**
     * Get master customer record (primary record across all shops)
     */
    public function getMasterRecord()
    {
        if (empty($this->global_customer_uuid)) {
            return $this;
        }

        // Find the customer with the highest data quality score
        return static::on('mysql_common')
            ->active()
            ->where('global_customer_uuid', $this->global_customer_uuid)
            ->orderByDesc('data_quality_score')
            ->orderBy('created_at') // Oldest record as tiebreaker
            ->first() ?? $this;
    }

    /**
     * Merge customer data from another customer
     */
    public function mergeFromCustomer(CustomerLedger $sourceCustomer, $strategy = 'best_data')
    {
        DB::beginTransaction();

        try {
            $mergedData = [];

            switch ($strategy) {
                case 'keep_target':
                    // Keep current customer data, only fill missing fields
                    $mergedData = $this->fillMissingDataFrom($sourceCustomer);
                    break;

                case 'keep_source':
                    // Use source customer data, keep target's shop context
                    $mergedData = $sourceCustomer->toArray();
                    $mergedData['shop_id'] = $this->shop_id;
                    $mergedData['ledger_id'] = $this->ledger_id;
                    break;

                case 'best_data':
                default:
                    // Choose best data from both records
                    $mergedData = $this->mergeBestDataFrom($sourceCustomer);
                    break;
            }

            // Update current customer
            $this->update($mergedData);

            // Mark source customer as merged
            $sourceCustomer->update([
                'is_merged' => true,
                'merged_into' => $this->ledger_id,
                'duplicate_flags' => array_merge(
                    $sourceCustomer->duplicate_flags ?? [],
                    [
                        'merged_at' => now(),
                        'merged_strategy' => $strategy,
                        'merged_into_customer' => $this->ledger_id
                    ]
                )
            ]);

            // Update data quality score
            $this->updateDataQualityScore();

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Fill missing data from another customer
     */
    private function fillMissingDataFrom(CustomerLedger $sourceCustomer)
    {
        $data = $this->toArray();

        $fillableFields = [
            'contact_number',
            'district',
            'sub_district',
            'village',
            'landmark',
            'type'
        ];

        foreach ($fillableFields as $field) {
            if (empty($data[$field]) && !empty($sourceCustomer->$field)) {
                $data[$field] = $sourceCustomer->$field;
            }
        }

        return $data;
    }

    /**
     * Merge best data from both customers
     */
    private function mergeBestDataFrom(CustomerLedger $sourceCustomer)
    {
        $data = $this->toArray();

        // Choose best values based on data quality
        $data['ledger_name'] = $this->chooseBestValue(
            $this->ledger_name,
            $sourceCustomer->ledger_name,
            'length'
        );

        $data['contact_number'] = $this->chooseBestValue(
            $this->contact_number,
            $sourceCustomer->contact_number,
            'phone'
        );

        $data['district'] = $this->chooseBestValue(
            $this->district,
            $sourceCustomer->district,
            'length'
        );

        $data['sub_district'] = $this->chooseBestValue(
            $this->sub_district,
            $sourceCustomer->sub_district,
            'length'
        );

        $data['village'] = $this->chooseBestValue(
            $this->village,
            $sourceCustomer->village,
            'length'
        );

        $data['landmark'] = $this->chooseBestValue(
            $this->landmark,
            $sourceCustomer->landmark,
            'length'
        );

        // Keep the more recent activity date
        if (
            $sourceCustomer->last_activity_at &&
            (!$this->last_activity_at || $sourceCustomer->last_activity_at > $this->last_activity_at)
        ) {
            $data['last_activity_at'] = $sourceCustomer->last_activity_at;
        }

        return $data;
    }

    /**
     * Choose the best value between two options
     */
    private function chooseBestValue($value1, $value2, $criteria = 'length')
    {
        if (empty($value1)) return $value2;
        if (empty($value2)) return $value1;

        switch ($criteria) {
            case 'phone':
                // Prefer longer phone numbers with proper format
                $phone1Score = $this->scorePhoneNumber($value1);
                $phone2Score = $this->scorePhoneNumber($value2);
                return $phone1Score >= $phone2Score ? $value1 : $value2;

            case 'length':
            default:
                // Prefer longer, more complete values
                return strlen($value1) >= strlen($value2) ? $value1 : $value2;
        }
    }

    /**
     * Score phone number quality
     */
    private function scorePhoneNumber($phone)
    {
        $score = 0;

        if (strlen($phone) >= 10) $score += 10;
        if (strlen($phone) >= 11) $score += 5;
        if (preg_match('/^[0-9+\-\s()]+$/', $phone)) $score += 10;
        if (preg_match('/^\+?[0-9]{10,15}$/', preg_replace('/[\s\-()]/', '', $phone))) $score += 15;

        return $score;
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        // Generate UUID on creation if not provided
        static::creating(function ($customer) {
            if (empty($customer->global_customer_uuid)) {
                $customer->global_customer_uuid = Str::uuid()->toString();
            }

            // Set initial data quality score
            $customer->data_quality_score = $customer->calculateDataQualityScore();

            // Set customer source if not provided
            if (empty($customer->customer_source)) {
                $customer->customer_source = 'manual';
            }

            // Set last activity
            $customer->last_activity_at = now();
        });

        // Update data quality score on updates
        static::updating(function ($customer) {
            $customer->data_quality_score = $customer->calculateDataQualityScore();
            $customer->last_activity_at = now();
        });
    }
}
