<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\DefaultLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DefaultLedgerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = DefaultLedger::on('mysql_common');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ledger_name', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%");
            });
        }

        // Type filter
        if ($request->has('ledger_type') && $request->ledger_type) {
            $query->where('type', $request->ledger_type);
        }

        $defaultLedgers = $query->orderBy('ledger_name')->paginate(10);

        return view('super-admin.default-ledgers.index', compact('defaultLedgers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('super-admin.default-ledgers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ledger_type' => 'required|string|max:50',
            'contact_number' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
        ]);

        DefaultLedger::on('mysql_common')->create([
            'ledger_name' => $validated['name'],
            'type' => $validated['ledger_type'],
            'contact_number' => $validated['contact_number'],
            'location' => $validated['location'],
        ]);

        return redirect()->route('super-admin.default-ledgers.index')
            ->with('success', 'Default ledger created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(DefaultLedger $defaultLedger)
    {
        // Ensure we're using the correct connection when retrieving the model
        $defaultLedger->setConnection('mysql_common');

        return view('super-admin.default-ledgers.show', compact('defaultLedger'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DefaultLedger $defaultLedger)
    {
        // Ensure we're using the correct connection when retrieving the model
        $defaultLedger->setConnection('mysql_common');

        return view('super-admin.default-ledgers.edit', compact('defaultLedger'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DefaultLedger $defaultLedger)
    {
        // Ensure we're using the correct connection
        $defaultLedger->setConnection('mysql_common');

        $validated = $request->validate([
            'ledger_name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'type' => 'nullable|string|max:50',
        ]);

        $defaultLedger->update($validated);

        return redirect()->route('super-admin.default-ledgers.index')
            ->with('success', 'Default ledger updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DefaultLedger $defaultLedger)
    {
        // Ensure we're using the correct connection
        $defaultLedger->setConnection('mysql_common');

        $defaultLedger->delete();

        return redirect()->route('super-admin.default-ledgers.index')
            ->with('success', 'Default ledger deleted successfully.');
    }
}
