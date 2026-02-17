<?php

namespace App\Services;

use App\Models\CustomerLedger;
use App\Models\LocationData;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DuplicateDetectionService
{
    private $confidenceThresholds = [
        'critical' => 90,
        'high' => 75,
        'medium' => 60,
        'low' => 40
    ];

    /**
     * Comprehensive duplicate detection across all shops
     */
    public function detectDuplicates($customerData, $excludeId = null, $options = [])
    {
        $options = array_merge([
            'include_cross_shop' => true,
            'include_merged' => false,
            'confidence_threshold' => 40,
            'max_results' => 20,
            'enable_caching' => true
        ], $options);

        $cacheKey = $this->generateCacheKey($customerData, $excludeId, $options);

        if ($options['enable_caching'] && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $duplicates = [];

        // 1. Critical duplicates (phone, exact matches)
        $criticalDuplicates = $this->findCriticalDuplicates($customerData, $excludeId, $options);
        $duplicates = array_merge($duplicates, $criticalDuplicates);

        // 2. High confidence duplicates (name + location)
        $highConfidenceDuplicates = $this->findHighConfidenceDuplicates($customerData, $excludeId, $options);
        $duplicates = array_merge($duplicates, $highConfidenceDuplicates);

        // 3. Medium confidence duplicates (fuzzy matching)
        $mediumConfidenceDuplicates = $this->findMediumConfidenceDuplicates($customerData, $excludeId, $options);
        $duplicates = array_merge($duplicates, $mediumConfidenceDuplicates);

        // 4. Low confidence duplicates (broad matching)
        $lowConfidenceDuplicates = $this->findLowConfidenceDuplicates($customerData, $excludeId, $options);
        $duplicates = array_merge($duplicates, $lowConfidenceDuplicates);

        // Process and rank results
        $processedDuplicates = $this->processAndRankDuplicates($duplicates, $options);

        if ($options['enable_caching']) {
            Cache::put($cacheKey, $processedDuplicates, now()->addMinutes(15));
        }

        return $processedDuplicates;
    }

    /**
     * Find critical duplicates (phone numbers, exact matches)
     */
    private function findCriticalDuplicates($customerData, $excludeId, $options)
    {
        $duplicates = [];

        // Phone number duplicates
        if (!empty($customerData['phone'])) {
            $phoneMatches = CustomerLedger::on('mysql_common')
                ->when(!$options['include_merged'], function ($query) {
                    return $query->active();
                })
                ->where('contact_number', $customerData['phone'])
                ->when($excludeId, function ($query) use ($excludeId) {
                    return $query->where('ledger_id', '!=', $excludeId);
                })
                ->with(['shop'])
                ->get();

            foreach ($phoneMatches as $match) {
                $duplicates[] = $this->createDuplicateRecord($match, [
                    'type' => 'phone',
                    'confidence' => 100,
                    'reason' => 'Exact phone number match',
                    'risk_level' => 'critical',
                    'match_details' => [
                        'phone_match' => $customerData['phone']
                    ]
                ]);
            }
        }

        // Exact name + location duplicates
        if (
            !empty($customerData['name']) && !empty($customerData['district']) &&
            !empty($customerData['sub_district']) && !empty($customerData['village'])
        ) {

            $exactMatches = CustomerLedger::on('mysql_common')
                ->when(!$options['include_merged'], function ($query) {
                    return $query->active();
                })
                ->where('ledger_name', $customerData['name'])
                ->where('district', $customerData['district'])
                ->where('sub_district', $customerData['sub_district'])
                ->where('village', $customerData['village'])
                ->when($excludeId, function ($query) use ($excludeId) {
                    return $query->where('ledger_id', '!=', $excludeId);
                })
                ->with(['shop'])
                ->get();

            foreach ($exactMatches as $match) {
                $duplicates[] = $this->createDuplicateRecord($match, [
                    'type' => 'exact',
                    'confidence' => 95,
                    'reason' => 'Exact name and location match',
                    'risk_level' => 'critical',
                    'match_details' => [
                        'name_match' => true,
                        'location_match' => true
                    ]
                ]);
            }
        }

        return $duplicates;
    }

    /**
     * Find high confidence duplicates
     */
    private function findHighConfidenceDuplicates($customerData, $excludeId, $options)
    {
        $duplicates = [];

        if (empty($customerData['name']) || strlen($customerData['name']) < 3) {
            return $duplicates;
        }

        // Name similarity with location context
        $nameMatches = CustomerLedger::on('mysql_common')
            ->when(!$options['include_merged'], function ($query) {
                return $query->active();
            })
            ->where(function ($query) use ($customerData) {
                $name = $customerData['name'];
                $query->where('ledger_name', 'like', "%{$name}%")
                    ->orWhereRaw('SOUNDEX(ledger_name) = SOUNDEX(?)', [$name]);
            })
            ->when($excludeId, function ($query) use ($excludeId) {
                return $query->where('ledger_id', '!=', $excludeId);
            })
            ->with(['shop'])
            ->get();

        foreach ($nameMatches as $match) {
            $nameSimilarity = $this->calculateNameSimilarity($customerData['name'], $match->ledger_name);

            if ($nameSimilarity >= 70) {
                $locationBoost = $this->calculateLocationBoost($customerData, $match);
                $finalConfidence = min(100, $nameSimilarity + $locationBoost);

                if ($finalConfidence >= $this->confidenceThresholds['high']) {
                    $duplicates[] = $this->createDuplicateRecord($match, [
                        'type' => 'name_location',
                        'confidence' => $finalConfidence,
                        'reason' => "Name similarity: {$nameSimilarity}%" .
                            ($locationBoost > 0 ? " + Location context: +{$locationBoost}%" : ""),
                        'risk_level' => 'high',
                        'match_details' => [
                            'name_similarity' => $nameSimilarity,
                            'location_boost' => $locationBoost
                        ]
                    ]);
                }
            }
        }

        return $duplicates;
    }

    /**
     * Find medium confidence duplicates
     */
    private function findMediumConfidenceDuplicates($customerData, $excludeId, $options)
    {
        $duplicates = [];

        // Similar location with different names
        if (!empty($customerData['district']) && !empty($customerData['sub_district'])) {
            $locationMatches = CustomerLedger::on('mysql_common')
                ->when(!$options['include_merged'], function ($query) {
                    return $query->active();
                })
                ->where('district', $customerData['district'])
                ->where('sub_district', $customerData['sub_district'])
                ->when($excludeId, function ($query) use ($excludeId) {
                    return $query->where('ledger_id', '!=', $excludeId);
                })
                ->with(['shop'])
                ->get();

            foreach ($locationMatches as $match) {
                $locationSimilarity = $this->calculateLocationSimilarity($customerData, $match);
                $nameSimilarity = 0;

                if (!empty($customerData['name'])) {
                    $nameSimilarity = $this->calculateNameSimilarity($customerData['name'], $match->ledger_name);
                }

                $combinedConfidence = ($locationSimilarity * 0.6) + ($nameSimilarity * 0.4);

                if (
                    $combinedConfidence >= $this->confidenceThresholds['medium'] &&
                    $combinedConfidence < $this->confidenceThresholds['high']
                ) {

                    $duplicates[] = $this->createDuplicateRecord($match, [
                        'type' => 'location_similar',
                        'confidence' => round($combinedConfidence, 2),
                        'reason' => "Location similarity: {$locationSimilarity}%, Name similarity: {$nameSimilarity}%",
                        'risk_level' => 'medium',
                        'match_details' => [
                            'location_similarity' => $locationSimilarity,
                            'name_similarity' => $nameSimilarity
                        ]
                    ]);
                }
            }
        }

        // Partial phone matches (similar numbers)
        if (!empty($customerData['phone']) && strlen($customerData['phone']) >= 10) {
            $phonePattern = substr($customerData['phone'], -8); // Last 8 digits

            $partialPhoneMatches = CustomerLedger::on('mysql_common')
                ->when(!$options['include_merged'], function ($query) {
                    return $query->active();
                })
                ->where('contact_number', 'like', "%{$phonePattern}")
                ->where('contact_number', '!=', $customerData['phone'])
                ->when($excludeId, function ($query) use ($excludeId) {
                    return $query->where('ledger_id', '!=', $excludeId);
                })
                ->with(['shop'])
                ->get();

            foreach ($partialPhoneMatches as $match) {
                $phoneSimilarity = $this->calculatePhoneSimilarity($customerData['phone'], $match->contact_number);

                if ($phoneSimilarity >= $this->confidenceThresholds['medium']) {
                    $duplicates[] = $this->createDuplicateRecord($match, [
                        'type' => 'phone_similar',
                        'confidence' => $phoneSimilarity,
                        'reason' => "Similar phone number: {$phoneSimilarity}%",
                        'risk_level' => 'medium',
                        'match_details' => [
                            'phone_similarity' => $phoneSimilarity
                        ]
                    ]);
                }
            }
        }

        return $duplicates;
    }

    /**
     * Find low confidence duplicates
     */
    private function findLowConfidenceDuplicates($customerData, $excludeId, $options)
    {
        $duplicates = [];

        // Broad name matching
        if (!empty($customerData['name']) && strlen($customerData['name']) >= 3) {
            $nameWords = explode(' ', strtolower(trim($customerData['name'])));
            $significantWords = array_filter($nameWords, function ($word) {
                return strlen($word) >= 3 && !in_array($word, ['the', 'and', 'for', 'are', 'but', 'not', 'you', 'all']);
            });

            if (!empty($significantWords)) {
                $query = CustomerLedger::on('mysql_common')
                    ->when(!$options['include_merged'], function ($query) {
                        return $query->active();
                    })
                    ->where(function ($q) use ($significantWords) {
                        foreach ($significantWords as $word) {
                            $q->orWhere('ledger_name', 'like', "%{$word}%");
                        }
                    })
                    ->when($excludeId, function ($query) use ($excludeId) {
                        return $query->where('ledger_id', '!=', $excludeId);
                    })
                    ->with(['shop'])
                    ->limit(50); // Limit broad searches

                $broadMatches = $query->get();

                foreach ($broadMatches as $match) {
                    $nameSimilarity = $this->calculateNameSimilarity($customerData['name'], $match->ledger_name);

                    if (
                        $nameSimilarity >= $this->confidenceThresholds['low'] &&
                        $nameSimilarity < $this->confidenceThresholds['medium']
                    ) {

                        $duplicates[] = $this->createDuplicateRecord($match, [
                            'type' => 'name_broad',
                            'confidence' => $nameSimilarity,
                            'reason' => "Broad name similarity: {$nameSimilarity}%",
                            'risk_level' => 'low',
                            'match_details' => [
                                'name_similarity' => $nameSimilarity,
                                'matching_words' => array_intersect($nameWords, explode(' ', strtolower($match->ledger_name)))
                            ]
                        ]);
                    }
                }
            }
        }

        return $duplicates;
    }

    /**
     * Create a standardized duplicate record
     */
    private function createDuplicateRecord($customer, $matchInfo)
    {
        return [
            'customer' => $customer,
            'shop_info' => $customer->shop,
            'type' => $matchInfo['type'],
            'confidence' => $matchInfo['confidence'],
            'reason' => $matchInfo['reason'],
            'risk_level' => $matchInfo['risk_level'],
            'match_details' => $matchInfo['match_details'],
            'cross_shop' => $customer->shop_id !== request()->get('current_shop_id'),
            'data_quality_score' => $customer->data_quality_score ?? $customer->calculateDataQualityScore(),
            'last_activity' => $customer->last_activity_at,
            'created_at' => $customer->created_at
        ];
    }

    /**
     * Process and rank duplicates
     */
    private function processAndRankDuplicates($duplicates, $options)
    {
        // Remove exact duplicates
        $uniqueDuplicates = collect($duplicates)
            ->unique(function ($item) {
                return $item['customer']->ledger_id;
            })
            ->filter(function ($item) use ($options) {
                return $item['confidence'] >= $options['confidence_threshold'];
            });

        // Sort by confidence and risk level
        $sortedDuplicates = $uniqueDuplicates->sort(function ($a, $b) {
            // First sort by risk level priority
            $riskPriority = ['critical' => 4, 'high' => 3, 'medium' => 2, 'low' => 1];
            $aPriority = $riskPriority[$a['risk_level']] ?? 0;
            $bPriority = $riskPriority[$b['risk_level']] ?? 0;

            if ($aPriority !== $bPriority) {
                return $bPriority <=> $aPriority;
            }

            // Then by confidence
            if ($a['confidence'] !== $b['confidence']) {
                return $b['confidence'] <=> $a['confidence'];
            }

            // Finally by data quality score
            return ($b['data_quality_score'] ?? 0) <=> ($a['data_quality_score'] ?? 0);
        });

        // Limit results
        $limitedDuplicates = $sortedDuplicates->take($options['max_results']);

        // Add summary statistics
        return [
            'duplicates' => $limitedDuplicates->values(),
            'summary' => [
                'total_found' => $uniqueDuplicates->count(),
                'returned' => $limitedDuplicates->count(),
                'by_risk_level' => $uniqueDuplicates->groupBy('risk_level')->map->count(),
                'by_type' => $uniqueDuplicates->groupBy('type')->map->count(),
                'cross_shop_count' => $uniqueDuplicates->where('cross_shop', true)->count(),
                'avg_confidence' => round($uniqueDuplicates->avg('confidence'), 2)
            ]
        ];
    }

    /**
     * Calculate name similarity using multiple algorithms
     */
    private function calculateNameSimilarity($name1, $name2)
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));

        if ($name1 === $name2) {
            return 100;
        }

        $similarities = [];

        // Levenshtein distance
        $maxLen = max(strlen($name1), strlen($name2));
        if ($maxLen > 0) {
            $distance = levenshtein($name1, $name2);
            $similarities[] = (1 - $distance / $maxLen) * 100;
        }

        // Similar text percentage
        similar_text($name1, $name2, $percent);
        $similarities[] = $percent;

        // Soundex comparison
        if (soundex($name1) === soundex($name2)) {
            $similarities[] = 85;
        }

        // Word-based comparison
        $words1 = explode(' ', $name1);
        $words2 = explode(' ', $name2);
        $wordMatches = 0;
        $totalWords = max(count($words1), count($words2));

        foreach ($words1 as $word1) {
            foreach ($words2 as $word2) {
                if (strlen($word1) >= 3 && strlen($word2) >= 3) {
                    similar_text($word1, $word2, $wordPercent);
                    if ($wordPercent >= 80) {
                        $wordMatches++;
                        break;
                    }
                }
            }
        }

        if ($totalWords > 0) {
            $similarities[] = ($wordMatches / $totalWords) * 100;
        }

        return round(max($similarities), 2);
    }

    /**
     * Calculate location boost based on matching location components
     */
    private function calculateLocationBoost($customerData, $match)
    {
        $boost = 0;

        if (!empty($customerData['district']) && $match->district === $customerData['district']) {
            $boost += 15;

            if (!empty($customerData['sub_district']) && $match->sub_district === $customerData['sub_district']) {
                $boost += 20;

                if (!empty($customerData['village']) && $match->village === $customerData['village']) {
                    $boost += 25;
                } elseif (!empty($customerData['village']) && !empty($match->village)) {
                    // Calculate village similarity
                    $villageSimilarity = LocationData::calculateStringSimilarity(
                        $customerData['village'],
                        $match->village
                    );
                    if ($villageSimilarity >= 70) {
                        $boost += round($villageSimilarity * 0.2); // Up to 20 points for similar village
                    }
                }
            } elseif (!empty($customerData['sub_district']) && !empty($match->sub_district)) {
                // Calculate sub-district similarity
                $subDistrictSimilarity = LocationData::calculateStringSimilarity(
                    $customerData['sub_district'],
                    $match->sub_district
                );
                if ($subDistrictSimilarity >= 70) {
                    $boost += round($subDistrictSimilarity * 0.15); // Up to 15 points for similar sub-district
                }
            }
        }

        // Additional boost for landmark similarity
        if (!empty($customerData['landmark']) && !empty($match->landmark)) {
            $landmarkSimilarity = LocationData::calculateStringSimilarity(
                $customerData['landmark'],
                $match->landmark
            );
            if ($landmarkSimilarity >= 60) {
                $boost += round($landmarkSimilarity * 0.1); // Up to 10 points for landmark
            }
        }

        return $boost;
    }

    /**
     * Calculate location similarity percentage
     */
    private function calculateLocationSimilarity($customerData, $match)
    {
        $totalSimilarity = 0;
        $validComparisons = 0;

        $locationFields = [
            'district' => 3,    // Weight: 3
            'sub_district' => 2, // Weight: 2
            'village' => 1       // Weight: 1
        ];

        foreach ($locationFields as $field => $weight) {
            if (!empty($customerData[$field]) && !empty($match->$field)) {
                $similarity = LocationData::calculateStringSimilarity(
                    $customerData[$field],
                    $match->$field
                );
                $totalSimilarity += ($similarity * $weight);
                $validComparisons += $weight;
            }
        }

        return $validComparisons > 0 ? round($totalSimilarity / $validComparisons, 2) : 0;
    }

    /**
     * Calculate phone similarity
     */
    private function calculatePhoneSimilarity($phone1, $phone2)
    {
        // Normalize phone numbers
        $normalized1 = preg_replace('/[^0-9]/', '', $phone1);
        $normalized2 = preg_replace('/[^0-9]/', '', $phone2);

        if ($normalized1 === $normalized2) {
            return 100;
        }

        // Check for common patterns
        $similarities = [];

        // Last 8 digits comparison
        if (strlen($normalized1) >= 8 && strlen($normalized2) >= 8) {
            $last8_1 = substr($normalized1, -8);
            $last8_2 = substr($normalized2, -8);

            if ($last8_1 === $last8_2) {
                $similarities[] = 85;
            } else {
                similar_text($last8_1, $last8_2, $percent);
                $similarities[] = $percent * 0.8; // Reduce weight for partial matches
            }
        }

        // Last 7 digits comparison
        if (strlen($normalized1) >= 7 && strlen($normalized2) >= 7) {
            $last7_1 = substr($normalized1, -7);
            $last7_2 = substr($normalized2, -7);

            if ($last7_1 === $last7_2) {
                $similarities[] = 75;
            }
        }

        // Overall similarity
        similar_text($normalized1, $normalized2, $percent);
        $similarities[] = $percent;

        return round(max($similarities), 2);
    }

    /**
     * Generate cache key for duplicate detection
     */
    private function generateCacheKey($customerData, $excludeId, $options)
    {
        $keyData = [
            'name' => $customerData['name'] ?? '',
            'phone' => $customerData['phone'] ?? '',
            'district' => $customerData['district'] ?? '',
            'sub_district' => $customerData['sub_district'] ?? '',
            'village' => $customerData['village'] ?? '',
            'exclude_id' => $excludeId,
            'options' => $options
        ];

        return 'duplicate_detection_' . md5(json_encode($keyData));
    }

    /**
     * Get duplicate statistics for dashboard
     */
    public function getDuplicateStatistics($shopId = null)
    {
        $query = CustomerLedger::on('mysql_common')->active();

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $totalCustomers = $query->count();

        // Find customers with potential duplicates
        $potentialDuplicates = 0;
        $criticalDuplicates = 0;
        $crossShopDuplicates = 0;

        // Phone number duplicates
        $phoneGroups = CustomerLedger::on('mysql_common')
            ->active()
            ->whereNotNull('contact_number')
            ->where('contact_number', '!=', '')
            ->when($shopId, function ($query) use ($shopId) {
                return $query->where('shop_id', $shopId);
            })
            ->groupBy('contact_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('contact_number');

        $criticalDuplicates += CustomerLedger::on('mysql_common')
            ->active()
            ->whereIn('contact_number', $phoneGroups)
            ->when($shopId, function ($query) use ($shopId) {
                return $query->where('shop_id', $shopId);
            })
            ->count();

        // Cross-shop duplicates (same global UUID)
        if (!$shopId) {
            $globalUuidGroups = CustomerLedger::on('mysql_common')
                ->active()
                ->whereNotNull('global_customer_uuid')
                ->groupBy('global_customer_uuid')
                ->havingRaw('COUNT(DISTINCT shop_id) > 1')
                ->pluck('global_customer_uuid');

            $crossShopDuplicates = CustomerLedger::on('mysql_common')
                ->active()
                ->whereIn('global_customer_uuid', $globalUuidGroups)
                ->count();
        }

        // Data quality statistics
        $dataQualityStats = CustomerLedger::on('mysql_common')
            ->active()
            ->when($shopId, function ($query) use ($shopId) {
                return $query->where('shop_id', $shopId);
            })
            ->selectRaw('
                AVG(data_quality_score) as avg_quality,
                COUNT(CASE WHEN data_quality_score >= 90 THEN 1 END) as grade_a,
                COUNT(CASE WHEN data_quality_score >= 80 AND data_quality_score < 90 THEN 1 END) as grade_b,
                COUNT(CASE WHEN data_quality_score >= 70 AND data_quality_score < 80 THEN 1 END) as grade_c,
                COUNT(CASE WHEN data_quality_score < 70 THEN 1 END) as grade_d_f
            ')
            ->first();

        return [
            'total_customers' => $totalCustomers,
            'critical_duplicates' => $criticalDuplicates,
            'cross_shop_duplicates' => $crossShopDuplicates,
            'duplicate_rate' => $totalCustomers > 0 ? round(($criticalDuplicates / $totalCustomers) * 100, 2) : 0,
            'data_quality' => [
                'average_score' => round($dataQualityStats->avg_quality ?? 0, 2),
                'grade_distribution' => [
                    'A' => $dataQualityStats->grade_a ?? 0,
                    'B' => $dataQualityStats->grade_b ?? 0,
                    'C' => $dataQualityStats->grade_c ?? 0,
                    'D/F' => $dataQualityStats->grade_d_f ?? 0
                ]
            ]
        ];
    }

    /**
     * Bulk duplicate detection for existing customers
     */
    public function bulkDuplicateDetection($shopId = null, $batchSize = 100)
    {
        $query = CustomerLedger::on('mysql_common')
            ->active()
            ->when($shopId, function ($query) use ($shopId) {
                return $query->where('shop_id', $shopId);
            });

        $totalCustomers = $query->count();
        $processedCount = 0;
        $duplicatesFound = 0;
        $results = [];

        $query->chunk($batchSize, function ($customers) use (&$processedCount, &$duplicatesFound, &$results) {
            foreach ($customers as $customer) {
                $customerData = [
                    'name' => $customer->ledger_name,
                    'phone' => $customer->contact_number,
                    'district' => $customer->district,
                    'sub_district' => $customer->sub_district,
                    'village' => $customer->village,
                    'landmark' => $customer->landmark
                ];

                $duplicates = $this->detectDuplicates($customerData, $customer->ledger_id, [
                    'confidence_threshold' => 70,
                    'max_results' => 5,
                    'enable_caching' => false
                ]);

                if (!empty($duplicates['duplicates'])) {
                    $duplicatesFound++;
                    $results[] = [
                        'customer' => $customer,
                        'duplicates' => $duplicates['duplicates']
                    ];
                }

                $processedCount++;
            }
        });

        return [
            'total_processed' => $processedCount,
            'duplicates_found' => $duplicatesFound,
            'duplicate_rate' => $processedCount > 0 ? round(($duplicatesFound / $processedCount) * 100, 2) : 0,
            'results' => $results
        ];
    }

    /**
     * Auto-merge obvious duplicates
     */
    public function autoMergeObviousDuplicates($shopId = null, $dryRun = true)
    {
        $mergeableCustomers = [];
        $mergeCount = 0;

        // Find customers with exact phone matches
        $phoneGroups = CustomerLedger::on('mysql_common')
            ->active()
            ->whereNotNull('contact_number')
            ->where('contact_number', '!=', '')
            ->when($shopId, function ($query) use ($shopId) {
                return $query->where('shop_id', $shopId);
            })
            ->groupBy('contact_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('contact_number');

        foreach ($phoneGroups as $phone) {
            $duplicateGroup = CustomerLedger::on('mysql_common')
                ->active()
                ->where('contact_number', $phone)
                ->when($shopId, function ($query) use ($shopId) {
                    return $query->where('shop_id', $shopId);
                })
                ->orderByDesc('data_quality_score')
                ->orderBy('created_at')
                ->get();

            if ($duplicateGroup->count() > 1) {
                $primaryCustomer = $duplicateGroup->first();
                $duplicatesToMerge = $duplicateGroup->skip(1);

                $mergeableCustomers[] = [
                    'primary' => $primaryCustomer,
                    'duplicates' => $duplicatesToMerge,
                    'reason' => 'Exact phone number match',
                    'confidence' => 100
                ];

                if (!$dryRun) {
                    foreach ($duplicatesToMerge as $duplicate) {
                        try {
                            $primaryCustomer->mergeFromCustomer($duplicate, 'best_data');
                            $mergeCount++;
                        } catch (\Exception $e) {
                            // Log error but continue
                            Log::error("Auto-merge failed for customers {$primaryCustomer->ledger_id} and {$duplicate->ledger_id}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        return [
            'mergeable_groups' => count($mergeableCustomers),
            'total_duplicates' => collect($mergeableCustomers)->sum(function ($group) {
                return $group['duplicates']->count();
            }),
            'merged_count' => $mergeCount,
            'dry_run' => $dryRun,
            'details' => $mergeableCustomers
        ];
    }

    /**
     * Clear duplicate detection cache
     */
    public function clearCache()
    {
        $pattern = 'duplicate_detection_*';
        $keys = Cache::getRedis()->keys($pattern);

        if (!empty($keys)) {
            Cache::getRedis()->del($keys);
        }

        return count($keys);
    }
}
