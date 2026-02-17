<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use Illuminate\Http\Request;

class BusinessController extends Controller
{
    public function index()
    {
        $businesses = Business::latest()->paginate(10);
        return view('super-admin.businesses.index', compact('businesses'));
    }

    public function create()
    {
        return view('super-admin.businesses.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'contact_number' => 'required|string',
            'email' => 'required|email',
            'district' => 'required|string',
        ]);

        Business::create($validated);

        return redirect()->route('super-admin.businesses.index')
            ->with('success', 'Business created successfully');
    }

    public function search(Request $request)
    {
        $search = $request->get('search');

        $businesses = Business::where('name', 'like', "%{$search}%")
            ->take(10)
            ->get()
            ->map(function ($business) {
                return [
                    'id' => $business->id,
                    'text' => $business->name
                ];
            });

        return response()->json($businesses);
    }

    public function edit(Business $business)
    {
        return view('super-admin.businesses.edit', compact('business'));
    }

    public function update(Request $request, Business $business)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'contact_number' => 'required|string',
            'email' => 'required|email',
            'district' => 'required|string',
        ]);

        $business->update($validated);

        return redirect()->route('super-admin.businesses.index')
            ->with('success', 'Business updated successfully');
    }

    public function destroy(Business $business)
    {
        $business->delete();

        return redirect()->route('super-admin.businesses.index')
            ->with('success', 'Business deleted successfully');
    }
}
