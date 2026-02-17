<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Ledger;
use App\Models\CustomerLedger;
use App\Models\LocationData;
use App\Models\Business;

class CustomerImportController extends Controller
{
    /**
     * Get the current authenticated user
     */
    private function getCurrentUser()
    {
        return Auth::user();
    }

    /**
     * Get the business ID for the current user
     */
    private function getBusinessId()
    {
        $user = $this->getCurrentUser();

        if (!$user) {
            throw new \Exception('User not authenticated.');
        }

        // Get business_id from users table
        $businessId = $user->business_id;

        if (!$businessId) {
            throw new \Exception('Business ID not found in user profile. Please contact administrator.');
        }

        return $businessId;
    }

    /**
     * Display the customer import interface
     */
    public function index(Request $request)
    {
        try {
            // Simplified authentication check
            $user = Auth::user();
            if (!$user) {
                return redirect()->route('login')->with('error', 'Please login to access this page.');
            }

            // Get business ID from users table
            $businessId = $user->business_id;

            if (!$businessId) {
                // If no business_id, show error but still load the page
                $business = null;
                $importStats = [
                    'total_imported' => 0,
                    'pending_conflicts' => 0,
                    'last_import' => null
                ];
            } else {
                $business = Business::find($businessId);

                // Get import statistics
                $importStats = [
                    'total_imported' => Ledger::where('business_id', $businessId)
                        ->where('is_imported', true)
                        ->count(),
                    'pending_conflicts' => 0,
                    'last_import' => null
                ];
            }

            // Get districts from tbl_customer_ledgers
            $districts = CustomerLedger::on('mysql_common')
                ->whereNotNull('district')
                ->where('district', '!=', '')
                ->distinct()
                ->pluck('district')
                ->sort()
                ->values();

            // Debug: Check if we're getting districts
            if ($districts->isEmpty()) {
                Log::info('No districts found in tbl_customer_ledgers');
            } else {
                Log::info('Found districts: ' . $districts->count());
            }

            // Get recent import history (empty for now)
            $recentImports = collect([]);

            return view('admin.customer-import.index', compact(
                'business',
                'districts',
                'recentImports',
                'importStats'
            ));
        } catch (\Exception $e) {
            Log::error('Customer Import Index Error: ' . $e->getMessage());

            // Return view with empty data and error message
            $business = null;
            $districts = collect([]);
            $recentImports = collect([]);
            $importStats = [
                'total_imported' => 0,
                'pending_conflicts' => 0,
                'last_import' => null
            ];

            return view('admin.customer-import.index', compact(
                'business',
                'districts',
                'recentImports',
                'importStats'
            ))->with('error', 'Error loading page: ' . $e->getMessage());
        }
    }

    /**
     * Get sub-districts by district from tbl_customer_ledgers (AJAX)
     */
    public function getSubDistricts(Request $request)
    {
        try {
            $district = $request->get('district');

            $subDistricts = CustomerLedger::on('mysql_common')
                ->where('district', $district)
                ->whereNotNull('sub_district')
                ->where('sub_district', '!=', '')
                ->distinct()
                ->pluck('sub_district')
                ->sort()
                ->values();

            return response()->json($subDistricts->map(function ($item) {
                return ['id' => $item, 'text' => $item];
            }));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Preview customers from location filters (AJAX)
     */
    public function preview(Request $request)
    {
        $request->validate([
            'district' => 'required|string',
            'sub_district' => 'nullable|string',
            'village' => 'nullable|string',
        ]);

        try {
            $district = $request->district;
            $subDistrict = $request->sub_district;
            $village = $request->village;

            // Get customers from common database based on location
            $query = CustomerLedger::on('mysql_common')
                ->where('customer_status', 'active')
                ->where('district', $district)
                ->where('type', 'Sundry Debtors (Customer)'); // Only show Sundry Debtors (Customer) types

            if ($subDistrict) {
                $query->where('sub_district', $subDistrict);
            }

            if ($village) {
                $query->where('village', $village);
            }

            // Get current user's business ID for exclusion check
            $currentUser = Auth::user();
            $businessId = null;

            // Check user roles and get business_id accordingly (same as import method)
            if ($currentUser->roles->contains('name', 'staff')) {
                $staff = \App\Models\Staff::where('user_id', Auth::id())->first();
                $businessId = $staff ? $staff->business_id : null;
            } elseif ($currentUser->roles->contains('name', 'admin')) {
                $admin = \App\Models\BusinessAdmin::where('user_id', Auth::id())->first();
                $businessId = $admin ? $admin->business_id : null;
            }

            // Only exclude already imported customers if we have a business ID
            if ($businessId) {
                $existingCustomerIds = Ledger::where('business_id', $businessId)
                    ->whereNotNull('common_customer_id')
                    ->pluck('common_customer_id')
                    ->toArray();

                if (!empty($existingCustomerIds)) {
                    $query->whereNotIn('ledger_id', $existingCustomerIds);
                }
            }

            $customers = $query->orderBy('ledger_name')->get();

            Log::info('Preview customers filtered:', [
                'district' => $district,
                'sub_district' => $subDistrict,
                'village' => $village,
                'total_found' => $customers->count(),
                'business_id' => $businessId,
                'filter_type' => 'Sundry Debtors (Customer)'
            ]);

            $customersData = $customers->map(function ($customer) {
                return [
                    'id' => $customer->ledger_id,
                    'name' => $customer->ledger_name,
                    'phone' => $customer->contact_number,
                    'type' => $customer->type, // This will be 'Sundry Debtors (Customer)'
                    'district' => $customer->district,
                    'sub_district' => $customer->sub_district,
                    'village' => $customer->village,
                    'landmark' => $customer->landmark,
                    'full_location' => $this->buildFullLocation($customer),
                    'data_quality_score' => $customer->data_quality_score ?? 0,
                    'created_at' => $customer->created_at->format('M d, Y'),
                    'quality_grade' => $this->getQualityGrade($customer->data_quality_score ?? 0),
                    'avatar_initials' => $this->getInitials($customer->ledger_name),
                    'avatar_color' => $this->getAvatarColor($customer->ledger_name)
                ];
            });

            return response()->json([
                'success' => true,
                'customers' => $customersData,
                'total' => $customers->count(),
                'location_summary' => [
                    'district' => $district,
                    'sub_district' => $subDistrict,
                    'village' => $village
                ],
                'filter_info' => [
                    'type_filter' => 'Sundry Debtors (Customer)',
                    'excluded_already_imported' => $businessId ? true : false
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Customer preview error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error loading customers: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Import selected customers
     */
    public function import(Request $request)
    {
        $request->validate([
            'customer_ids' => 'required|string', // JSON string from frontend
            'district' => 'nullable|string',
            'sub_district' => 'nullable|string',
            'import_notes' => 'nullable|string|max:500'
        ]);

        try {
            // Get the authenticated user's business ID through staff or admin relationship
            $businessId = null;
            $currentUser = Auth::user();

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated. Please login again.'
                ], 401);
            }

            // Check user roles and get business_id accordingly
            if ($currentUser->roles->contains('name', 'staff')) {
                $staff = \App\Models\Staff::where('user_id', Auth::id())->first();
                $businessId = $staff ? $staff->business_id : null;
            } elseif ($currentUser->roles->contains('name', 'admin')) {
                $admin = \App\Models\BusinessAdmin::where('user_id', Auth::id())->first();
                $businessId = $admin ? $admin->business_id : null;
            }

            Log::info('Import attempt by user:', [
                'user_id' => $currentUser->id,
                'user_name' => $currentUser->name,
                'user_roles' => $currentUser->roles->pluck('name')->toArray(),
                'business_id' => $businessId
            ]);

            if (!$businessId) {
                return response()->json([
                    'success' => false,
                    'message' => 'No business associated with your account. Please contact administrator.'
                ], 400);
            }

            // Verify business exists
            $business = Business::find($businessId);
            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Business not found. Please contact administrator.'
                ], 400);
            }

            // Decode customer IDs from JSON
            $customerIds = json_decode($request->customer_ids, true);

            if (!is_array($customerIds) || empty($customerIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid customer IDs provided'
                ], 400);
            }

            Log::info('Starting import process:', [
                'business_id' => $businessId,
                'customer_count' => count($customerIds),
                'customer_ids' => $customerIds
            ]);

            // Generate import batch ID
            $importBatchId = 'BATCH_' . time() . '_' . \Illuminate\Support\Str::random(8);

            DB::beginTransaction();

            $importResults = [
                'success' => [],
                'failed' => [],
                'conflicts' => []
            ];

            foreach ($customerIds as $customerId) {
                try {
                    // Get customer from common database
                    $commonCustomer = CustomerLedger::on('mysql_common')
                        ->where('ledger_id', $customerId)
                        ->first();

                    if (!$commonCustomer) {
                        $importResults['failed'][] = [
                            'customer_id' => $customerId,
                            'error' => 'Customer not found in common database'
                        ];
                        continue;
                    }

                    // Check if already imported (simple check)
                    $existingLedger = Ledger::where('business_id', $businessId)
                        ->where('common_customer_id', $customerId)
                        ->first();

                    if ($existingLedger) {
                        $importResults['conflicts'][] = [
                            'customer_id' => $customerId,
                            'customer_name' => $commonCustomer->ledger_name,
                            'message' => 'Customer already imported to this business'
                        ];
                        continue;
                    }

                    // Import customer (simplified data)
                    $ledgerData = [
                        'business_id' => $businessId,
                        'name' => $commonCustomer->ledger_name,
                        'ledger_type' => 'Sundry Debtors (Customer)',
                        'contact_number' => $commonCustomer->contact_number,
                        'location' => $this->buildFullLocation($commonCustomer),
                        'common_customer_id' => $commonCustomer->ledger_id,
                        'is_imported' => true,
                        'import_batch_id' => $importBatchId,
                        'imported_at' => now(),
                        'imported_by' => $currentUser->name ?? 'Unknown'
                    ];

                    Log::info('Creating ledger with data:', $ledgerData);

                    $ledger = Ledger::create($ledgerData);

                    $importResults['success'][] = [
                        'common_customer_id' => $customerId,
                        'ledger_id' => $ledger->id,
                        'customer_name' => $commonCustomer->ledger_name
                    ];

                    Log::info('Successfully imported customer:', [
                        'common_customer_id' => $customerId,
                        'ledger_id' => $ledger->id,
                        'customer_name' => $commonCustomer->ledger_name
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error importing customer:', [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage(),
                        'line' => $e->getLine(),
                        'file' => $e->getFile()
                    ]);

                    $importResults['failed'][] = [
                        'customer_id' => $customerId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            Log::info('Import completed:', [
                'batch_id' => $importBatchId,
                'results' => [
                    'imported' => count($importResults['success']),
                    'failed' => count($importResults['failed']),
                    'conflicts' => count($importResults['conflicts'])
                ]
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Import completed successfully',
                'results' => [
                    'imported' => count($importResults['success']),
                    'failed' => count($importResults['failed']),
                    'conflicts' => count($importResults['conflicts']),
                    'batch_id' => $importBatchId
                ],
                'details' => $importResults
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Customer import error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'business_id' => $businessId ?? null,
                'customer_ids' => $customerIds ?? [],
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Check for conflicts when importing a customer
     */
    private function checkCustomerConflicts($businessId, $commonCustomer)
    {
        $conflicts = [];

        try {
            // Check for duplicate phone number
            if ($commonCustomer->contact_number) {
                $existingByPhone = Ledger::where('business_id', $businessId)
                    ->where('contact_number', $commonCustomer->contact_number)
                    ->first();

                if ($existingByPhone) {
                    $conflicts[] = [
                        'type' => 'duplicate_phone',
                        'message' => "Phone number '{$commonCustomer->contact_number}' already exists",
                        'existing_customer' => $existingByPhone->name
                    ];
                }
            }

            // Check for similar names (simplified to avoid complex queries)
            if ($commonCustomer->ledger_name) {
                $existingByName = Ledger::where('business_id', $businessId)
                    ->where('name', $commonCustomer->ledger_name)
                    ->first();

                if ($existingByName) {
                    $conflicts[] = [
                        'type' => 'duplicate_name',
                        'message' => "Customer with exact same name already exists",
                        'existing_customer' => $existingByName->name
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('Error checking customer conflicts:', [
                'business_id' => $businessId,
                'customer_name' => $commonCustomer->ledger_name ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            // Return empty conflicts array if there's an error checking
            // This allows the import to continue
            return [];
        }

        return $conflicts;
    }


    /**
     * Calculate name similarity percentage
     */
    private function calculateNameSimilarity($name1, $name2)
    {
        $name1 = strtolower(trim($name1));
        $name2 = strtolower(trim($name2));

        if ($name1 === $name2) {
            return 100;
        }

        similar_text($name1, $name2, $percent);
        return round($percent, 2);
    }

    /**
     * Map common customer type to ledger type
     */
    private function mapLedgerType($commonType)
    {
        // Always map to Sundry Debtors (Customer) for customer imports
        // We're only importing customers, not suppliers
        return 'Sundry Debtors (Customer)';
    }


    /**
     * Build full location string from customer data
     */
    private function buildFullLocation($customer)
    {
        $locationParts = array_filter([
            $customer->village,
            $customer->sub_district,
            $customer->district
        ]);

        $location = implode(', ', $locationParts);

        if ($customer->landmark) {
            $location .= ' (Near: ' . $customer->landmark . ')';
        }

        return $location ?: 'Location not specified';
    }

    /**
     * Get quality grade based on score
     */
    private function getQualityGrade($score)
    {
        if ($score >= 90) return 'A';
        if ($score >= 80) return 'B';
        if ($score >= 70) return 'C';
        if ($score >= 60) return 'D';
        return 'F';
    }

    /**
     * Helper methods for UI
     */
    private function getInitials($name)
    {
        return collect(explode(' ', $name))
            ->map(fn($word) => strtoupper(substr($word, 0, 1)))
            ->take(2)
            ->implode('');
    }

    private function getAvatarColor($name)
    {
        $colors = [
            'bg-red-500',
            'bg-blue-500',
            'bg-green-500',
            'bg-yellow-500',
            'bg-purple-500',
            'bg-pink-500',
            'bg-indigo-500',
            'bg-teal-500'
        ];

        $index = ord(strtoupper($name[0])) % count($colors);
        return $colors[$index];
    }

    /**
     * Placeholder methods for future implementation
     */
    public function history(Request $request, $batchId = null)
    {
        // Placeholder for import history
        return view('admin.customer-import.history');
    }

    public function showBatch($batchId)
    {
        // Placeholder for showing specific batch details
        return view('admin.customer-import.batch-detail');
    }

    public function retryImport(Request $request)
    {
        // Placeholder for retry import functionality
        return response()->json(['success' => true, 'message' => 'Retry functionality not implemented yet']);
    }

    public function deleteBatch($batchId)
    {
        // Placeholder for delete batch functionality
        return response()->json(['success' => true, 'message' => 'Delete batch functionality not implemented yet']);
    }

    public function exportBatch($batchId)
    {
        // Placeholder for export batch functionality
        return response()->json(['success' => true, 'message' => 'Export batch functionality not implemented yet']);
    }

    public function conflicts(Request $request)
    {
        // Placeholder for conflicts management
        return view('admin.customer-import.conflicts');
    }

    public function showConflict($conflictId)
    {
        // Placeholder for showing specific conflict details
        return view('admin.customer-import.conflict-detail');
    }

    public function resolveConflict(Request $request)
    {
        // Placeholder for resolve conflict functionality
        return response()->json(['success' => true, 'message' => 'Resolve conflict functionality not implemented yet']);
    }

    public function resolveAllConflicts(Request $request)
    {
        // Placeholder for resolve all conflicts functionality
        return response()->json(['success' => true, 'message' => 'Resolve all conflicts functionality not implemented yet']);
    }
}
