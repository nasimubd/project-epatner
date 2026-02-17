<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\CommonUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommonUnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $units = CommonUnit::on('mysql_common')->orderBy('unit_name')->paginate(10);
        return view('super-admin.common-units.index', compact('units'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('super-admin.common-units.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit_name' => 'required|string|max:255|unique:mysql_common.tbl_common_unit,unit_name',
        ]);

        CommonUnit::on('mysql_common')->create([
            'unit_name' => $validated['unit_name'],
        ]);

        return redirect()->route('super-admin.common-units.index')
            ->with('success', 'Unit created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CommonUnit $commonUnit)
    {
        // Ensure we're using the correct connection when retrieving the model
        $commonUnit->setConnection('mysql_common');

        return view('super-admin.common-units.show', compact('commonUnit'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CommonUnit $commonUnit)
    {
        // Ensure we're using the correct connection when retrieving the model
        $commonUnit->setConnection('mysql_common');

        return view('super-admin.common-units.edit', compact('commonUnit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CommonUnit $commonUnit)
    {
        // Ensure we're using the correct connection
        $commonUnit->setConnection('mysql_common');

        $validated = $request->validate([
            'unit_name' => 'required|string|max:255|unique:mysql_common.tbl_common_unit,unit_name,' . $commonUnit->unit_id . ',unit_id',
        ]);

        $commonUnit->update([
            'unit_name' => $validated['unit_name'],
        ]);

        return redirect()->route('super-admin.common-units.index')
            ->with('success', 'Unit updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CommonUnit $commonUnit)
    {
        // Ensure we're using the correct connection
        $commonUnit->setConnection('mysql_common');

        // Check if unit has products
        if ($commonUnit->products()->count() > 0) {
            return redirect()->route('super-admin.common-units.index')
                ->with('error', 'Cannot delete unit with associated products.');
        }

        $commonUnit->delete();

        return redirect()->route('super-admin.common-units.index')
            ->with('success', 'Unit deleted successfully.');
    }
}
