<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CustomerLedger;
use App\Models\LocationData;
use App\Models\CustomerDataQuality;
use App\Models\CustomerMergeHistory;
use App\Models\CustomerLedgerAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Str;

class CustomerLedgerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = CustomerLedger::on('mysql_common')->active(); // Only show non-merged records

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ledger_name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->orWhere('district', 'like', "%{$search}%")
                    ->orWhere('sub_district', 'like', "%{$search}%")
                    ->orWhere('village', 'like', "%{$search}%")
                    ->orWhere('landmark', 'like', "%{$search}%");
            });
        }

        // Type filter
        if ($request->has('ledger_type') && $request->ledger_type) {
            $query->where('type', $request->ledger_type);
        }

        // Quality grade filter
        if ($request->has('quality_grade') && $request->quality_grade) {
            $grade = $request->quality_grade;
            switch ($grade) {
                case 'A':
                    $query->whereRaw('data_quality_score >= 90');
                    break;
                case 'B':
                    $query->whereRaw('data_quality_score >= 80 AND data_quality_score < 90');
                    break;
                case 'C':
                    $query->whereRaw('data_quality_score >= 70 AND data_quality_score < 80');
                    break;
                case 'D':
                    $query->whereRaw('data_quality_score >= 60 AND data_quality_score < 70');
                    break;
                case 'F':
                    $query->whereRaw('data_quality_score < 60');
                    break;
            }
        }

        // Location filters
        if ($request->has('district') && $request->district) {
            $query->where('district', $request->district);
        }
        if ($request->has('sub_district') && $request->sub_district) {
            $query->where('sub_district', $request->sub_district);
        }
        if ($request->has('village') && $request->village) {
            $query->where('village', $request->village);
        }

        $customers = $query->orderBy('ledger_name')->paginate(10);

        // Get filter options
        $ledgerTypes = CustomerLedger::on('mysql_common')
            ->active()
            ->whereNotNull('type')
            ->distinct()
            ->pluck('type')
            ->filter()
            ->sort()
            ->values();

        $districts = LocationData::getDistricts();

        return view('super-admin.customer-ledgers.index', compact(
            'customers',
            'ledgerTypes',
            'districts'
        ));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get all districts for the dropdown
        $districts = LocationData::getDistricts();

        return view('super-admin.customer-ledgers.create', compact('districts'));
    }

    public function store(Request $request)
    {
        // First, validate the basic input
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ledger_type' => 'required|string|max:50',
            'phone' => 'nullable|string|max:20',
            'district' => 'nullable|string|max:100',
            'sub_district' => 'nullable|string|max:100',
            'village' => 'nullable|string|max:100',
            'landmark' => 'nullable|string|max:255',
        ]);

        // SERVER-SIDE DUPLICATE VALIDATION using new model method
        $duplicates = CustomerLedger::findCrossShopDuplicates([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'district' => $validated['district'],
            'sub_district' => $validated['sub_district'],
            'village' => $validated['village']
        ]);

        $duplicateErrors = [];

        // Check for critical duplicates (phone matches)
        $criticalDuplicates = $duplicates->filter(function ($duplicate) {
            return $duplicate['type'] === 'phone' || $duplicate['confidence'] >= 90;
        });

        if ($criticalDuplicates->isNotEmpty() && !$request->has('ignore_duplicates')) {
            foreach ($criticalDuplicates as $duplicate) {
                $duplicateErrors[] = $duplicate['reason'];
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['duplicate' => $duplicateErrors])
                ->with('warning', 'Critical duplicates detected. Please review before creating.')
                ->with('potential_duplicates', $duplicates);
        }

        // Create the customer ledger with new fields
        try {
            $customerData = [
                'ledger_name' => $validated['name'],
                'type' => $validated['ledger_type'],
                'contact_number' => $validated['phone'],
                'district' => $validated['district'],
                'sub_district' => $validated['sub_district'],
                'village' => $validated['village'],
                'landmark' => $validated['landmark'],
                'customer_source' => 'manual',
                'customer_status' => 'inactive',
                // global_customer_uuid and data_quality_score will be set automatically in model boot method
            ];

            $customer = CustomerLedger::on('mysql_common')->create($customerData);

            // Generate QR code (same as BusinessShopfront does)
            $customer->generateQrCode();

            // Create audit log
            CustomerLedgerAudit::create([
                'ledger_id' => $customer->ledger_id,
                'action' => 'created',
                'new_data' => $customerData,
                'changed_by' => Auth::user()->name ?? 'System',
                'reason' => 'New customer creation'
            ]);

            // Create data quality record
            CustomerDataQuality::create([
                'customer_id' => $customer->ledger_id,
                'quality_score' => $customer->data_quality_score,
                'quality_grade' => $customer->getDataQualityGrade(),
                'quality_factors' => $customer->getDuplicateRiskAssessment(),
                'last_calculated_at' => now()
            ]);

            return redirect()->route('super-admin.customer-ledgers.index')
                ->with('success', 'Customer ledger created successfully with QR code.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create customer ledger. Please try again.'])
                ->with('error', 'Database error occurred: ' . $e->getMessage());
        }
    }



    /**
     * Generate QR code for customer (following BusinessShopfront pattern)
     */
    public function generateQrCode(Request $request, CustomerLedger $customerLedger)
    {
        $customerLedger->setConnection('mysql_common');

        // Generate QR code (same pattern as BusinessShopfront)
        $qrCode = $customerLedger->generateQrCode();

        return response()->json([
            'success' => true,
            'message' => 'QR code generated successfully',
            'qr_code' => $qrCode
        ]);
    }





    /**
     * Get sub-districts by district (AJAX)
     */
    public function getSubDistricts(Request $request)
    {
        $district = $request->get('district');
        $subDistricts = LocationData::getSubDistricts($district);

        return response()->json($subDistricts->map(function ($item) {
            return ['id' => $item, 'text' => $item];
        }));
    }

    /**
     * Get villages by district and sub-district (AJAX)
     */
    public function getVillages(Request $request)
    {
        $district = $request->get('district');
        $subDistrict = $request->get('sub_district');
        $villages = LocationData::getVillages($district, $subDistrict);

        return response()->json($villages->map(function ($item) {
            return ['id' => $item, 'text' => $item];
        }));
    }

    /**
     * Check for duplicate customers (AJAX)
     */
    public function checkDuplicates(Request $request)
    {
        $name = $request->get('name');
        $district = $request->get('district');
        $subDistrict = $request->get('sub_district');
        $village = $request->get('village');
        $phone = $request->get('phone');

        $duplicates = [];

        if ($name && strlen($name) >= 3) {
            // Name-based duplicates (fuzzy matching) - REMOVE LEVENSHTEIN FROM SQL
            $nameMatches = CustomerLedger::on('mysql_common')
                ->where('is_merged', false)
                ->where(function ($query) use ($name) {
                    $query->where('ledger_name', 'like', "%{$name}%")
                        ->orWhereRaw('SOUNDEX(ledger_name) = SOUNDEX(?)', [$name]);
                })
                ->get();

            foreach ($nameMatches as $match) {
                $similarity = $this->calculateSimilarity($name, $match->ledger_name);
                if ($similarity >= 40) {
                    $duplicates[] = [
                        'type' => 'name',
                        'similarity' => $similarity,
                        'customer' => $match,
                        'reason' => "Name similarity: {$similarity}%"
                    ];
                }
            }
        }

        // Phone-based duplicates (EXACT match - phone must be unique)
        if ($phone && strlen($phone) >= 10) {
            $phoneMatches = CustomerLedger::on('mysql_common')
                ->where('is_merged', false)
                ->where('contact_number', $phone)
                ->get();

            foreach ($phoneMatches as $match) {
                $duplicates[] = [
                    'type' => 'phone',
                    'similarity' => 100,
                    'customer' => $match,
                    'reason' => 'Exact phone number match - Phone numbers must be unique'
                ];
            }
        }

        // Location-based duplicates (similar location combination)
        if ($district && $subDistrict && $village) {
            // Exact location match
            $exactLocationMatches = CustomerLedger::on('mysql_common')
                ->where('is_merged', false)
                ->where('district', $district)
                ->where('sub_district', $subDistrict)
                ->where('village', $village)
                ->get();

            foreach ($exactLocationMatches as $match) {
                $duplicates[] = [
                    'type' => 'location',
                    'similarity' => 100,
                    'customer' => $match,
                    'reason' => 'Exact location match'
                ];
            }

            // Similar location matches (fuzzy matching for location names)
            $similarLocationMatches = CustomerLedger::on('mysql_common')
                ->where('is_merged', false)
                ->where('district', $district)
                ->where('sub_district', $subDistrict)
                ->where('village', '!=', $village)
                ->get();

            foreach ($similarLocationMatches as $match) {
                $locationSimilarity = $this->calculateLocationSimilarity(
                    [$district, $subDistrict, $village],
                    [$match->district, $match->sub_district, $match->village]
                );

                if ($locationSimilarity >= 70) {
                    $duplicates[] = [
                        'type' => 'location',
                        'similarity' => $locationSimilarity,
                        'customer' => $match,
                        'reason' => "Similar location: {$locationSimilarity}%"
                    ];
                }
            }

            // Also check for similar villages in same sub-district (broader search)
            $broaderLocationMatches = CustomerLedger::on('mysql_common')
                ->where('is_merged', false)
                ->where('district', $district)
                ->where('sub_district', $subDistrict)
                ->where('village', '!=', $village)
                ->where(function ($query) use ($village) {
                    $query->where('village', 'like', "%{$village}%")
                        ->orWhereRaw('SOUNDEX(village) = SOUNDEX(?)', [$village]);
                })
                ->get();

            foreach ($broaderLocationMatches as $match) {
                $villageSimilarity = $this->calculateSimilarity($village, $match->village);

                if ($villageSimilarity >= 60) {
                    $duplicates[] = [
                        'type' => 'location',
                        'similarity' => $villageSimilarity,
                        'customer' => $match,
                        'reason' => "Similar village name: {$villageSimilarity}%"
                    ];
                }
            }
        }

        // Remove duplicates and sort by similarity
        $uniqueDuplicates = collect($duplicates)
            ->unique(function ($item) {
                return $item['customer']->ledger_id;
            })
            ->sortByDesc('similarity')
            ->take(5)
            ->values();

        return response()->json($uniqueDuplicates);
    }

    /**
     * Calculate location similarity percentage (updated for 3-level hierarchy)
     */
    private function calculateLocationSimilarity($location1, $location2)
    {
        $totalSimilarity = 0;
        $validComparisons = 0;

        // Compare each level of the location hierarchy
        for ($i = 0; $i < min(count($location1), count($location2)); $i++) {
            if (!empty($location1[$i]) && !empty($location2[$i])) {
                $similarity = $this->calculateSimilarity($location1[$i], $location2[$i]);

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
     * Calculate string similarity percentage using PHP functions
     */
    private function calculateSimilarity($str1, $str2)
    {
        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        if ($str1 === $str2) {
            return 100;
        }

        // Use multiple similarity algorithms and take the best result
        $similarities = [];

        // 1. Levenshtein distance (PHP function)
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen > 0 && $maxLen <= 255) { // Levenshtein has a 255 character limit
            $distance = levenshtein($str1, $str2);
            $similarities[] = (1 - $distance / $maxLen) * 100;
        }

        // 2. Similar text percentage
        similar_text($str1, $str2, $percent);
        $similarities[] = $percent;

        // 3. Soundex comparison
        if (soundex($str1) === soundex($str2)) {
            $similarities[] = 85; // High similarity for soundex match
        }

        // 4. Jaccard similarity (for longer strings)
        if (strlen($str1) > 3 && strlen($str2) > 3) {
            $similarities[] = $this->jaccardSimilarity($str1, $str2) * 100;
        }

        return count($similarities) > 0 ? round(max($similarities), 2) : 0;
    }

    /**
     * Calculate Jaccard similarity for strings
     */
    private function jaccardSimilarity($str1, $str2)
    {
        // Convert strings to character sets
        $set1 = array_unique(str_split($str1));
        $set2 = array_unique(str_split($str2));

        // Calculate intersection and union
        $intersection = array_intersect($set1, $set2);
        $union = array_unique(array_merge($set1, $set2));

        if (count($union) === 0) {
            return 0;
        }

        return count($intersection) / count($union);
    }



    /**
     * Display the specified resource.
     */
    public function show(CustomerLedger $customerLedger)
    {
        // Ensure we're using the correct connection when retrieving the model
        $customerLedger->setConnection('mysql_common');

        return view('super-admin.customer-ledgers.show', compact('customerLedger'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CustomerLedger $customerLedger)
    {
        // Ensure we're using the correct connection when retrieving the model
        $customerLedger->setConnection('mysql_common');

        return view('super-admin.customer-ledgers.edit', compact('customerLedger'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CustomerLedger $customerLedger)
    {
        // Ensure we're using the correct connection
        $customerLedger->setConnection('mysql_common');

        $validated = $request->validate([
            'ledger_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'type' => 'nullable|string|max:50',
            'district' => 'nullable|string|max:100',
            'sub_district' => 'nullable|string|max:100',
            'upazila' => 'nullable|string|max:100',
            'gram' => 'nullable|string|max:100',
            'landmark' => 'nullable|string|max:255',
        ]);

        $customerLedger->update($validated);

        return redirect()->route('super-admin.customer-ledgers.index')
            ->with('success', 'Customer ledger updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CustomerLedger $customerLedger)
    {
        // Ensure we're using the correct connection
        $customerLedger->setConnection('mysql_common');

        $customerLedger->delete();

        return redirect()->route('super-admin.customer-ledgers.index')
            ->with('success', 'Customer ledger deleted successfully.');
    }


    public function mergeDuplicates(Request $request)
    {
        $validated = $request->validate([
            'existing_customer_id' => 'required|exists:tbl_customer_ledgers,ledger_id',
            'new_customer_data' => 'required|array',
            'merge_strategy' => 'required|in:keep_new,keep_existing,merge_data'
        ]);

        DB::beginTransaction();

        try {
            $existingCustomer = CustomerLedger::on('mysql_common')
                ->where('ledger_id', $validated['existing_customer_id'])
                ->where('is_merged', false)
                ->firstOrFail();

            $newCustomerData = $validated['new_customer_data'];
            $strategy = $validated['merge_strategy'];

            // Store pre-merge data for audit
            $preMergeExistingData = $existingCustomer->toArray();

            switch ($strategy) {
                case 'keep_new':
                    $result = $this->mergeKeepNew($existingCustomer, $newCustomerData);
                    break;

                case 'keep_existing':
                    $result = $this->mergeKeepExisting($existingCustomer, $newCustomerData);
                    break;

                case 'merge_data':
                    $result = $this->mergeData($existingCustomer, $newCustomerData);
                    break;
            }

            // Create merge history record
            CustomerMergeHistory::create([
                'primary_customer_id' => $result['primary_customer_id'],
                'merged_customer_id' => $existingCustomer->ledger_id,
                'merge_strategy' => $strategy,
                'merge_confidence' => 95, // You can calculate this based on similarity
                'pre_merge_primary_data' => $preMergeExistingData,
                'pre_merge_merged_data' => $newCustomerData,
                'post_merge_data' => CustomerLedger::find($result['primary_customer_id'])->toArray(),
                'merge_criteria' => [
                    'duplicate_type' => 'manual_merge',
                    'confidence_threshold' => 95
                ],
                'merge_source' => 'manual',
                'merged_by' => Auth::user()->name ?? 'System',
                'merged_at' => now()
            ]);

            // Create audit log
            CustomerLedgerAudit::create([
                'ledger_id' => $result['primary_customer_id'],
                'action' => 'merged',
                'old_data' => $preMergeExistingData,
                'new_data' => CustomerLedger::find($result['primary_customer_id'])->toArray(),
                'changed_by' => Auth::user()->name ?? 'System',
                'reason' => "Merged using strategy: {$strategy}"
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'primary_customer_id' => $result['primary_customer_id'],
                'message' => 'Customers merged successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Merge failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get customer data for AJAX requests
     */
    public function getCustomerData(CustomerLedger $customerLedger)
    {
        $customerLedger->setConnection('mysql_common');

        return response()->json([
            'ledger_id' => $customerLedger->ledger_id,
            'ledger_name' => $customerLedger->ledger_name,
            'contact_number' => $customerLedger->contact_number,
            'district' => $customerLedger->district,
            'sub_district' => $customerLedger->sub_district,
            'village' => $customerLedger->village,
            'landmark' => $customerLedger->landmark,
            'created_at' => $customerLedger->created_at,
            'updated_at' => $customerLedger->updated_at,
        ]);
    }



    /**
     * Strategy 1: Keep new customer as primary, mark existing as merged
     */
    private function mergeKeepNew($existingCustomer, $newCustomerData)
    {
        // Create new customer
        $newCustomer = CustomerLedger::on('mysql_common')->create([
            'ledger_name' => $newCustomerData['name'],
            'type' => $newCustomerData['ledger_type'],
            'contact_number' => $newCustomerData['phone'],
            'district' => $newCustomerData['district'],
            'sub_district' => $newCustomerData['sub_district'],
            'village' => $newCustomerData['village'],
            'landmark' => $newCustomerData['landmark'],
            'is_merged' => false,
            'duplicate_flags' => [
                'merged_from' => $existingCustomer->ledger_id,
                'merge_date' => now(),
                'merge_reason' => 'Duplicate detected during creation'
            ]
        ]);

        // Mark existing customer as merged
        $existingCustomer->update([
            'is_merged' => true,
            'merged_into' => $newCustomer->ledger_id,
            'duplicate_flags' => [
                'merged_into' => $newCustomer->ledger_id,
                'merge_date' => now(),
                'merge_reason' => 'Duplicate detected, merged into newer record'
            ]
        ]);

        return ['primary_customer_id' => $newCustomer->ledger_id];
    }

    /**
     * Strategy 2: Keep existing customer, don't create new
     */
    private function mergeKeepExisting($existingCustomer, $newCustomerData)
    {
        // Update existing customer with any missing/better data
        $updateData = [];

        if (empty($existingCustomer->contact_number) && !empty($newCustomerData['phone'])) {
            $updateData['contact_number'] = $newCustomerData['phone'];
        }

        if (empty($existingCustomer->landmark) && !empty($newCustomerData['landmark'])) {
            $updateData['landmark'] = $newCustomerData['landmark'];
        }

        // Add merge history
        $duplicateFlags = $existingCustomer->duplicate_flags ?? [];
        $duplicateFlags[] = [
            'attempted_duplicate' => $newCustomerData,
            'merge_date' => now(),
            'action' => 'Prevented duplicate creation'
        ];
        $updateData['duplicate_flags'] = $duplicateFlags;

        if (!empty($updateData)) {
            $existingCustomer->update($updateData);
        }

        return ['primary_customer_id' => $existingCustomer->ledger_id];
    }

    /**
     * Strategy 3: Merge data from both records
     */
    private function mergeData($existingCustomer, $newCustomerData)
    {
        // Create merged customer with best data from both
        $mergedData = [
            'ledger_name' => $this->chooseBestValue(
                $existingCustomer->ledger_name,
                $newCustomerData['name']
            ),
            'type' => $newCustomerData['ledger_type'], // Use new type
            'contact_number' => $this->chooseBestValue(
                $existingCustomer->contact_number,
                $newCustomerData['phone']
            ),
            'district' => $this->chooseBestValue(
                $existingCustomer->district,
                $newCustomerData['district']
            ),
            'sub_district' => $this->chooseBestValue(
                $existingCustomer->sub_district,
                $newCustomerData['sub_district']
            ),
            'village' => $this->chooseBestValue(
                $existingCustomer->village,
                $newCustomerData['village']
            ),
            'landmark' => $this->chooseBestValue(
                $existingCustomer->landmark,
                $newCustomerData['landmark']
            ),
            'is_merged' => false,
            'duplicate_flags' => [
                'merged_from_existing' => $existingCustomer->ledger_id,
                'merged_from_new' => $newCustomerData,
                'merge_date' => now(),
                'merge_strategy' => 'data_merge'
            ]
        ];

        // Create new merged customer
        $mergedCustomer = CustomerLedger::on('mysql_common')->create($mergedData);

        // Mark existing as merged
        $existingCustomer->update([
            'is_merged' => true,
            'merged_into' => $mergedCustomer->ledger_id
        ]);

        return ['primary_customer_id' => $mergedCustomer->ledger_id];
    }

    private function chooseBestValue($existing, $new)
    {
        // Choose the longer/more complete value
        if (empty($existing)) return $new;
        if (empty($new)) return $existing;

        return strlen($new) > strlen($existing) ? $new : $existing;
    }


    /**
     * Show data quality report
     */
    public function dataQualityReport(Request $request)
    {
        $query = CustomerLedger::on('mysql_common')->active();

        // Get quality statistics
        $qualityStats = [
            'total' => $query->count(),
            'grade_a' => $query->whereRaw('data_quality_score >= 90')->count(),
            'grade_b' => $query->whereRaw('data_quality_score >= 80 AND data_quality_score < 90')->count(),
            'grade_c' => $query->whereRaw('data_quality_score >= 70 AND data_quality_score < 80')->count(),
            'grade_d' => $query->whereRaw('data_quality_score >= 60 AND data_quality_score < 70')->count(),
            'grade_f' => $query->whereRaw('data_quality_score < 60')->count(),
            'no_phone' => $query->whereNull('contact_number')->orWhere('contact_number', '')->count(),
            'no_location' => $query->where(function ($q) {
                $q->whereNull('district')->orWhere('district', '')
                    ->orWhereNull('sub_district')->orWhere('sub_district', '')
                    ->orWhereNull('village')->orWhere('village', '');
            })->count(),
            'average_score' => $query->avg('data_quality_score') ?? 0
        ];

        // Get customers with low quality scores
        $lowQualityCustomers = CustomerLedger::on('mysql_common')
            ->active()
            ->where('data_quality_score', '<', 70)
            ->orderBy('data_quality_score', 'asc')
            ->limit(20)
            ->get();

        return view('super-admin.customer-ledgers.data-quality-report', compact(
            'qualityStats',
            'lowQualityCustomers'
        ));
    }

    /**
     * Show merge history
     */
    public function mergeHistory(Request $request)
    {
        $mergedCustomers = CustomerLedger::on('mysql_common')
            ->where('is_merged', true)
            ->with(['mergedInto'])
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return view('super-admin.customer-ledgers.merge-history', compact('mergedCustomers'));
    }

    /**
     * Bulk update quality scores
     */
    public function bulkUpdateQuality(Request $request)
    {
        try {
            $updated = 0;

            CustomerLedger::on('mysql_common')
                ->active()
                ->chunk(100, function ($customers) use (&$updated) {
                    foreach ($customers as $customer) {
                        $customer->updateDataQualityScore();
                        $updated++;
                    }
                });

            return response()->json([
                'success' => true,
                'message' => "Updated quality scores for {$updated} customers"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating quality scores: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export customers to CSV
     */
    public function export(Request $request)
    {
        $query = CustomerLedger::on('mysql_common')->active();

        // Apply filters if provided
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        if ($request->has('ledger_type') && $request->ledger_type) {
            $query->where('type', $request->ledger_type);
        }

        if ($request->has('district') && $request->district) {
            $query->where('district', $request->district);
        }

        $customers = $query->orderBy('ledger_name')->get();

        $filename = 'customer_ledgers_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($customers) {
            $file = fopen('php://output', 'w');

            // Add CSV header
            fputcsv($file, [
                'ID',
                'Name',
                'Type',
                'Phone',
                'District',
                'Sub District',
                'Village',
                'Landmark',
                'Data Quality Score',
                'Data Quality Grade',
                'Global UUID',
                'Created At',
                'Updated At'
            ]);

            // Add data rows
            foreach ($customers as $customer) {
                fputcsv($file, [
                    $customer->ledger_id,
                    $customer->ledger_name,
                    $customer->type,
                    $customer->contact_number,
                    $customer->district,
                    $customer->sub_district,
                    $customer->village,
                    $customer->landmark,
                    $customer->data_quality_score,
                    $customer->getDataQualityGrade(),
                    $customer->global_customer_uuid,
                    $customer->created_at,
                    $customer->updated_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
