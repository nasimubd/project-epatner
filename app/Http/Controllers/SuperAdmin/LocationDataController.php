<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\LocationData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LocationDataController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = LocationData::on('mysql_common');

        // Search functionality
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->search($search);
        }

        // Filter by district
        if ($request->has('district') && $request->district) {
            $query->byDistrict($request->district);
        }

        // Filter by sub-district
        if ($request->has('sub_district') && $request->sub_district) {
            $query->bySubDistrict($request->sub_district);
        }

        $locations = $query->orderBy('district')
            ->orderBy('sub_district')
            ->orderBy('village')
            ->paginate(15);

        // Get filter options
        $districts = LocationData::getDistricts();
        $subDistricts = LocationData::getSubDistricts($request->district);

        return view('super-admin.location-data.index', compact(
            'locations',
            'districts',
            'subDistricts'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $districts = LocationData::getDistricts();
        return view('super-admin.location-data.create', compact('districts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'district' => 'required|string|max:100',
            'sub_district' => 'required|string|max:100',
            'village' => 'required|string|max:100',
        ]);

        // Check if location combination already exists
        if (LocationData::locationExists(
            $validated['district'],
            $validated['sub_district'],
            $validated['village']
        )) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['village' => 'This location combination already exists.']);
        }

        try {
            LocationData::on('mysql_common')->create($validated);

            return redirect()->route('super-admin.location-data.index')
                ->with('success', 'Location data created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create location data. Please try again.']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(LocationData $location_datum)
    {
        $location_datum->setConnection('mysql_common');

        // Get related customer ledgers count
        $customerCount = DB::connection('mysql_common')
            ->table('tbl_customer_ledgers')
            ->where('district', $location_datum->district)
            ->where('sub_district', $location_datum->sub_district)
            ->where('village', $location_datum->village)
            ->count();

        return view('super-admin.location-data.show', compact('location_datum', 'customerCount'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LocationData $location_datum)
    {
        $location_datum->setConnection('mysql_common');
        $districts = LocationData::getDistricts();

        return view('super-admin.location-data.edit', compact('location_datum', 'districts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LocationData $location_datum)
    {
        $location_datum->setConnection('mysql_common');

        $validated = $request->validate([
            'district' => 'required|string|max:100',
            'sub_district' => 'required|string|max:100',
            'village' => 'required|string|max:100',
        ]);

        // Check if location combination already exists (excluding current record)
        if (LocationData::locationExists(
            $validated['district'],
            $validated['sub_district'],
            $validated['village'],
            $location_datum->id
        )) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['village' => 'This location combination already exists.']);
        }

        try {
            $location_datum->update($validated);

            return redirect()->route('super-admin.location-data.index')
                ->with('success', 'Location data updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update location data. Please try again.']);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LocationData $location_datum)
    {
        $location_datum->setConnection('mysql_common');

        try {
            // Check if location is being used by any customer ledgers
            $customerCount = DB::connection('mysql_common')
                ->table('tbl_customer_ledgers')
                ->where('district', $location_datum->district)
                ->where('sub_district', $location_datum->sub_district)
                ->where('village', $location_datum->village)
                ->count();

            if ($customerCount > 0) {
                return redirect()->route('super-admin.location-data.index')
                    ->withErrors(['error' => "Cannot delete this location. It is being used by {$customerCount} customer(s)."]);
            }

            $location_datum->delete();

            return redirect()->route('super-admin.location-data.index')
                ->with('success', 'Location data deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('super-admin.location-data.index')
                ->withErrors(['error' => 'Failed to delete location data. Please try again.']);
        }
    }

    /**
     * Get sub-districts by district (AJAX)
     */
    public function getSubDistricts(Request $request)
    {
        $district = $request->get('district');
        $subDistricts = LocationData::getSubDistricts($district);

        return response()->json($subDistricts);
    }

    /**
     * Get villages by district and sub-district (AJAX)
     */
    public function getVillages(Request $request)
    {
        $district = $request->get('district');
        $subDistrict = $request->get('sub_district');
        $villages = LocationData::getVillages($district, $subDistrict);

        return response()->json($villages);
    }

    /**
     * Download import template
     */
    public function downloadTemplate()
    {
        $filename = 'location_data_import_template.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');

            // Add CSV header
            fputcsv($file, ['District', 'Sub District', 'Village']);

            // Add sample data rows
            $sampleData = [
                ['Dhaka', 'Dhanmondi', 'Dhanmondi-1'],
                ['Dhaka', 'Dhanmondi', 'Dhanmondi-2'],
                ['Dhaka', 'Gulshan', 'Gulshan-1'],
                ['Chittagong', 'Agrabad', 'Agrabad Commercial Area'],
                ['Sylhet', 'Zindabazar', 'Zindabazar Ward-1'],
            ];

            foreach ($sampleData as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Bulk import locations from CSV/Excel
     */
    public function bulkImport(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,xlsx,xls|max:2048'
        ]);

        try {
            $file = $request->file('import_file');
            $path = $file->getRealPath();

            // Read CSV file
            if ($file->getClientOriginalExtension() === 'csv') {
                $data = array_map('str_getcsv', file($path));
                $header = array_shift($data); // Remove header row

                $imported = 0;
                $skipped = 0;
                $errors = [];

                foreach ($data as $rowIndex => $row) {
                    $lineNumber = $rowIndex + 2; // +2 because we removed header and arrays are 0-indexed

                    if (count($row) < 3) {
                        $errors[] = "Line {$lineNumber}: Insufficient data (expected 3 columns, got " . count($row) . ")";
                        continue;
                    }

                    $locationData = [
                        'district' => trim($row[0]),
                        'sub_district' => trim($row[1]),
                        'village' => trim($row[2])
                    ];

                    // Validate data
                    if (empty($locationData['district']) || empty($locationData['sub_district']) || empty($locationData['village'])) {
                        $errors[] = "Line {$lineNumber}: Missing required data (District, Sub District, or Village is empty)";
                        continue;
                    }

                    // Check if location already exists
                    if (!LocationData::locationExists(
                        $locationData['district'],
                        $locationData['sub_district'],
                        $locationData['village']
                    )) {
                        try {
                            LocationData::on('mysql_common')->create($locationData);
                            $imported++;
                        } catch (\Exception $e) {
                            $errors[] = "Line {$lineNumber}: Failed to save - " . $e->getMessage();
                        }
                    } else {
                        $skipped++;
                    }
                }

                $message = "Import completed. {$imported} locations imported, {$skipped} skipped (duplicates).";

                if (!empty($errors)) {
                    $message .= " " . count($errors) . " errors occurred.";
                    return redirect()->route('super-admin.location-data.index')
                        ->with('warning', $message)
                        ->with('import_errors', $errors);
                }

                return redirect()->route('super-admin.location-data.index')
                    ->with('success', $message);
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['import_file' => 'Failed to import file. Please check the format and try again. Error: ' . $e->getMessage()]);
        }
    }

    /**
     * Export locations to CSV
     */
    public function export(Request $request)
    {
        $query = LocationData::on('mysql_common');

        // Apply same filters as index
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        if ($request->has('district') && $request->district) {
            $query->byDistrict($request->district);
        }

        if ($request->has('sub_district') && $request->sub_district) {
            $query->bySubDistrict($request->sub_district);
        }

        $locations = $query->orderBy('district')
            ->orderBy('sub_district')
            ->orderBy('village')
            ->get();

        $filename = 'location_data_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($locations) {
            $file = fopen('php://output', 'w');

            // Add CSV header
            fputcsv($file, ['District', 'Sub District', 'Village', 'Created At', 'Updated At']);

            // Add data rows
            foreach ($locations as $location) {
                fputcsv($file, [
                    $location->district,
                    $location->sub_district,
                    $location->village,
                    $location->created_at,
                    $location->updated_at
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
