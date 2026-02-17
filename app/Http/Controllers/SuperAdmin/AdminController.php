<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Business;
use App\Models\BusinessAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function create()
    {
        $businesses = Business::all();
        return view('super-admin.admins.create', compact('businesses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        BusinessAdmin::create([
            'business_id' => $validated['business_id'],
            'user_id' => $user->id,
        ]);

        // Assign admin role instead of business-admin
        $user->assignRole('admin');

        return redirect()->route('super-admin.admins.index')
            ->with('success', 'Business Admin created successfully');
    }

    public function index()
    {
        $admins = BusinessAdmin::with(['user', 'business'])
            ->latest()
            ->paginate(10);

        return view('super-admin.admins.index', compact('admins'));
    }

    public function edit(BusinessAdmin $admin)
    {
        $businesses = Business::all();
        return view('super-admin.admins.edit', compact('admin', 'businesses'));
    }

    public function update(Request $request, BusinessAdmin $admin)
    {
        $validated = $request->validate([
            'business_id' => 'required|exists:businesses,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $admin->user_id,
        ]);

        $admin->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $admin->update([
            'business_id' => $validated['business_id'],
        ]);

        return redirect()->route('super-admin.admins.index')
            ->with('success', 'Admin updated successfully');
    }

    public function destroy(BusinessAdmin $admin)
    {
        $admin->user->delete(); // This will cascade delete the business_admin record

        return redirect()->route('super-admin.admins.index')
            ->with('success', 'Admin removed successfully');
    }
}
