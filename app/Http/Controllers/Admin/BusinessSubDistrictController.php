<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\BusinessAdmin;
use App\Models\BusinessSubDistrict;

class BusinessSubDistrictController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $currentUser = Auth::user();
        $currentAdmin = BusinessAdmin::where('user_id', $currentUser->id)->first();

        if (!$currentAdmin) {
            return back()->withErrors('No business associated with this user.');
        }

        $businessId = $currentAdmin->business_id;

        // Get imported sub-districts for this business
        $importedSubDistricts = BusinessSubDistrict::where('business_id', $businessId)
            ->orderBy('district')
            ->orderBy('sub_district')
            ->paginate(15);

        // Get available districts from common database
        $availableDistricts = collect();

        try {
            // Test connection first
            $connectionTest = DB::connection('mysql_common')->getPdo();
            Log::info('Common database connection successful');

            // Check all tables to find the right one
            $allTables = DB::connection('mysql_common')->select('SHOW TABLES');
            $tableNames = array_map('current', $allTables);
            Log::info('Available tables in common database:', $tableNames);

            // Look for customer-related tables
            $customerTables = array_filter($tableNames, function ($table) {
                return stripos($table, 'customer') !== false ||
                    stripos($table, 'ledger') !== false ||
                    stripos($table, 'location') !== false;
            });
            Log::info('Customer/Ledger related tables found:', $customerTables);

            // Try to find the correct table and get districts
            $foundDistricts = false;
            $tablesToTry = ['tbl_customer_ledgers', 'customer_ledgers', 'customers', 'tbl_customers'];

            foreach ($tablesToTry as $tableName) {
                if (in_array($tableName, $tableNames)) {
                    try {
                        Log::info("Trying table: {$tableName}");

                        // Get table structure
                        $structure = DB::connection('mysql_common')->select("DESCRIBE {$tableName}");
                        $columns = array_column($structure, 'Field');
                        Log::info("Columns in {$tableName}:", $columns);

                        // Check record count
                        $count = DB::connection('mysql_common')->table($tableName)->count();
                        Log::info("Record count in {$tableName}: {$count}");

                        if ($count > 0) {
                            // Try different possible district field names
                            $districtFields = ['district', 'District', 'DISTRICT', 'district_name', 'area', 'region'];

                            foreach ($districtFields as $field) {
                                if (in_array($field, $columns)) {
                                    try {
                                        $districts = DB::connection('mysql_common')
                                            ->table($tableName)
                                            ->whereNotNull($field)
                                            ->where($field, '!=', '')
                                            ->where($field, '!=', 'NULL')
                                            ->distinct()
                                            ->orderBy($field)
                                            ->pluck($field)
                                            ->filter()
                                            ->values();

                                        if ($districts->isNotEmpty()) {
                                            $availableDistricts = $districts;
                                            $foundDistricts = true;
                                            Log::info("Successfully found districts using table '{$tableName}' and field '{$field}':", $districts->toArray());
                                            break 2; // Break both loops
                                        }
                                    } catch (\Exception $e) {
                                        Log::error("Error querying field '{$field}' in table '{$tableName}': " . $e->getMessage());
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Error examining table '{$tableName}': " . $e->getMessage());
                    }
                }
            }

            if (!$foundDistricts) {
                Log::warning('No districts found in any table');
                // For testing, let's add some dummy data
                $availableDistricts = collect(['Dhaka', 'Chittagong', 'Sylhet', 'Rajshahi', 'Khulna']);
                Log::info('Using dummy districts for testing:', $availableDistricts->toArray());
            }
        } catch (\Exception $e) {
            Log::error('Error fetching districts from common database: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Check database configuration
            $config = config('database.connections.mysql_common');
            Log::info('Common database config:', [
                'host' => $config['host'] ?? 'not set',
                'database' => $config['database'] ?? 'not set',
                'username' => $config['username'] ?? 'not set',
                'port' => $config['port'] ?? 'not set'
            ]);
        }

        return view('admin.business.sub-districts.index', compact(
            'importedSubDistricts',
            'availableDistricts',
            'businessId'
        ));
    }

    /**
     * Get sub-districts for a district from the common database
     */
    public function getSubDistricts(Request $request)
    {
        $district = $request->input('district');

        Log::info('getSubDistricts called with district: ' . $district);

        if (!$district) {
            Log::warning('No district provided');
            return response()->json([]);
        }

        try {
            // Get all tables to find the right one
            $allTables = DB::connection('mysql_common')->select('SHOW TABLES');
            $tableNames = array_map('current', $allTables);

            $subDistricts = collect();
            $tablesToTry = ['tbl_customer_ledgers', 'customer_ledgers', 'customers', 'tbl_customers'];

            foreach ($tablesToTry as $tableName) {
                if (in_array($tableName, $tableNames)) {
                    try {
                        // Get table structure
                        $structure = DB::connection('mysql_common')->select("DESCRIBE {$tableName}");
                        $columns = array_column($structure, 'Field');

                        // Check record count
                        $count = DB::connection('mysql_common')->table($tableName)->count();

                        if ($count > 0) {
                            // Try different field combinations
                            $districtFields = ['district', 'District', 'DISTRICT'];
                            $subDistrictFields = ['sub_district', 'Sub_District', 'SUB_DISTRICT', 'subdistrict', 'upazila', 'thana'];

                            foreach ($districtFields as $districtField) {
                                if (in_array($districtField, $columns)) {
                                    foreach ($subDistrictFields as $subDistrictField) {
                                        if (in_array($subDistrictField, $columns)) {
                                            try {
                                                $results = DB::connection('mysql_common')
                                                    ->table($tableName)
                                                    ->where($districtField, $district)
                                                    ->whereNotNull($subDistrictField)
                                                    ->where($subDistrictField, '!=', '')
                                                    ->where($subDistrictField, '!=', 'NULL')
                                                    ->distinct()
                                                    ->orderBy($subDistrictField)
                                                    ->pluck($subDistrictField)
                                                    ->filter()
                                                    ->values();

                                                if ($results->isNotEmpty()) {
                                                    $subDistricts = $results->map(function ($subDistrict) {
                                                        return [
                                                            'id' => $subDistrict,
                                                            'text' => $subDistrict
                                                        ];
                                                    });

                                                    Log::info("Found sub-districts using table '{$tableName}', district field '{$districtField}', sub-district field '{$subDistrictField}':", $subDistricts->toArray());
                                                    break 3; // Break all loops
                                                }
                                            } catch (\Exception $e) {
                                                Log::error("Error querying sub-districts: " . $e->getMessage());
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error("Error examining table '{$tableName}' for sub-districts: " . $e->getMessage());
                    }
                }
            }

            // If no sub-districts found, return dummy data for testing
            if ($subDistricts->isEmpty()) {
                Log::warning("No sub-districts found for district: {$district}");
                // Return some dummy sub-districts for testing
                $dummySubDistricts = [
                    'Dhaka' => ['Dhanmondi', 'Gulshan', 'Uttara', 'Wari'],
                    'Chittagong' => ['Panchlaish', 'Kotwali', 'Chandgaon'],
                    'Sylhet' => ['Sylhet Sadar', 'Companiganj', 'Golapganj'],
                ];

                if (isset($dummySubDistricts[$district])) {
                    $subDistricts = collect($dummySubDistricts[$district])->map(function ($subDistrict) {
                        return [
                            'id' => $subDistrict,
                            'text' => $subDistrict
                        ];
                    });
                    Log::info("Using dummy sub-districts for testing:", $subDistricts->toArray());
                }
            }

            return response()->json($subDistricts);
        } catch (\Exception $e) {
            Log::error('Error fetching sub-districts: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'Failed to fetch sub-districts',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'district' => 'required|string',
            'sub_district' => 'required|string',
        ]);

        $currentUser = Auth::user();
        $currentAdmin = BusinessAdmin::where('user_id', $currentUser->id)->first();

        if (!$currentAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'No business associated with this user.'
            ], 403);
        }

        $businessId = $currentAdmin->business_id;

        try {
            // Check if already exists
            $exists = BusinessSubDistrict::where('business_id', $businessId)
                ->where('district', $request->district)
                ->where('sub_district', $request->sub_district)
                ->exists();

            if ($exists) {
                return response()->json([
                    'success' => false,
                    'message' => 'This sub-district is already imported.'
                ]);
            }

            // Create new sub-district
            BusinessSubDistrict::create([
                'business_id' => $businessId,
                'district' => $request->district,
                'sub_district' => $request->sub_district,
                'status' => 'active'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sub-district imported successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error importing sub-district: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error importing sub-district.'
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, BusinessSubDistrict $subDistrict)
    {
        $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        try {
            $subDistrict->update([
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sub-district status updated successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating sub-district: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating sub-district.'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BusinessSubDistrict $subDistrict)
    {
        try {
            $subDistrict->delete();

            return response()->json([
                'success' => true,
                'message' => 'Sub-district removed successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting sub-district: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error removing sub-district.'
            ], 500);
        }
    }
}
