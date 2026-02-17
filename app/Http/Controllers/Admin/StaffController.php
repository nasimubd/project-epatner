<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\ProductCategory;
use App\Models\Ledger;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessAdmin;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class StaffController extends Controller
{
    public function index()
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();
        $staff = Staff::where('business_id', $currentAdmin->business_id)
            ->with(['user.roles', 'productCategories', 'ledgers', 'assignedStaffMembers', 'assignedDsrs'])
            ->paginate(10);

        // Calculate stats for the view
        $totalStaff = Staff::where('business_id', $currentAdmin->business_id)->count();
        $activeStaff = Staff::where('business_id', $currentAdmin->business_id)->where('is_active', true)->count();
        $inactiveStaff = Staff::where('business_id', $currentAdmin->business_id)->where('is_active', false)->count();

        // Get unique categories count across all staff
        $uniqueCategoriesCount = Staff::where('business_id', $currentAdmin->business_id)
            ->with('productCategories')
            ->get()
            ->flatMap->productCategories
            ->unique('id')
            ->count();

        return view('admin.staff.index', compact('staff', 'totalStaff', 'activeStaff', 'inactiveStaff', 'uniqueCategoriesCount'));
    }

    public function create()
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();
        $categories = ProductCategory::where('business_id', $currentAdmin->business_id)
            ->where('status', true)
            ->get();
        $ledgers = Ledger::where('business_id', $currentAdmin->business_id)->get();

        // Get all roles except admin and super-admin
        $roles = \Spatie\Permission\Models\Role::whereNotIn('name', ['admin', 'super-admin'])->get();

        // Get existing staff members for DSR assignment (exclude DSR role users)
        $existingStaff = Staff::where('business_id', $currentAdmin->business_id)
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->whereDoesntHave('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'dsr');
                });
            })
            ->get();

        return view('admin.staff.create', compact('categories', 'ledgers', 'roles', 'existingStaff'));
    }

    public function store(Request $request)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'product_categories' => 'required|array|min:1',
            'product_categories.*' => 'exists:product_categories,id',
            'ledgers' => 'required|array|min:1',
            'ledgers.*' => 'exists:ledgers,id',
            'role' => 'required|exists:roles,name',
            'phone' => 'nullable|string|max:20',
            'disable_underprice' => 'nullable|boolean'
        ];

        // Add DSR staff assignment validation if role is DSR
        if ($request->role === 'dsr') {
            $validationRules['assigned_staff'] = 'required|array|min:1';
            $validationRules['assigned_staff.*'] = 'exists:staff,id';

            // Validate that assigned staff are from the same business
            $request->validate([
                'assigned_staff' => [
                    'required',
                    'array',
                    'min:1',
                    function ($attribute, $value, $fail) use ($currentAdmin) {
                        $staffIds = Staff::where('business_id', $currentAdmin->business_id)
                            ->whereIn('id', $value)
                            ->pluck('id')
                            ->toArray();

                        if (count($staffIds) !== count($value)) {
                            $fail('Some selected staff members do not belong to your business.');
                        }
                    }
                ]
            ]);
        }

        $validated = $request->validate($validationRules);

        return DB::transaction(function () use ($request, $validated, $currentAdmin) {
            // Create user first
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'])
            ]);

            // Assign selected role
            $user->assignRole($validated['role']);

            // Create staff record
            $staff = Staff::create([
                'user_id' => $user->id,
                'business_id' => $currentAdmin->business_id,
                'phone' => $request->phone,
                'disable_underprice' => $request->has('disable_underprice')
            ]);

            $staff->productCategories()->attach($validated['product_categories']);
            $staff->ledgers()->attach($validated['ledgers']);

            // Handle DSR staff assignments
            if ($request->role === 'dsr' && $request->has('assigned_staff')) {
                $staff->assignedStaffMembers()->attach($validated['assigned_staff']);
            }

            return redirect()->route('admin.staff.index')
                ->with('success', 'Staff created successfully');
        });
    }

    public function edit(Staff $staff)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        if ($staff->business_id !== $currentAdmin->business_id) {
            abort(403);
        }

        $categories = ProductCategory::where('business_id', $currentAdmin->business_id)
            ->where('status', true)
            ->get();
        $ledgers = Ledger::where('business_id', $currentAdmin->business_id)->get();

        // Get all roles except admin and super-admin
        $roles = \Spatie\Permission\Models\Role::whereNotIn('name', ['admin', 'super-admin'])->get();

        // Get the current role of the staff
        $currentRole = $staff->user->roles->first()->name ?? 'staff';

        // Get existing staff members for DSR assignment (exclude current staff and other DSRs)
        $existingStaff = Staff::where('business_id', $currentAdmin->business_id)
            ->where('id', '!=', $staff->id)
            ->with('user')
            ->whereHas('user', function ($query) {
                $query->whereDoesntHave('roles', function ($roleQuery) {
                    $roleQuery->where('name', 'dsr');
                });
            })
            ->get();

        // Load the correct relationships
        $staff->load(['productCategories', 'ledgers', 'assignedStaffMembers']);

        return view('admin.staff.edit', compact('staff', 'categories', 'ledgers', 'roles', 'currentRole', 'existingStaff'));
    }

    public function update(Request $request, Staff $staff)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();
        if ($staff->business_id !== $currentAdmin->business_id) {
            abort(403);
        }

        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $staff->user_id,
            'product_categories' => 'required|array',
            'product_categories.*' => 'exists:product_categories,id',
            'ledgers' => 'required|array',
            'ledgers.*' => 'exists:ledgers,id',
            'role' => 'required|exists:roles,name',
            'phone' => 'nullable|string|max:20',
            'disable_underprice' => 'nullable|boolean'
        ];

        // Add DSR staff assignment validation if role is DSR
        if ($request->role === 'dsr') {
            $validationRules['assigned_staff'] = 'required|array|min:1';
            $validationRules['assigned_staff.*'] = 'exists:staff,id';

            // Validate that assigned staff are from the same business and not the current staff
            $request->validate([
                'assigned_staff' => [
                    'required',
                    'array',
                    'min:1',
                    function ($attribute, $value, $fail) use ($currentAdmin, $staff) {
                        // Check if trying to assign to self
                        if (in_array($staff->id, $value)) {
                            $fail('A DSR cannot be assigned to themselves.');
                        }

                        // Check if all staff belong to the same business
                        $staffIds = Staff::where('business_id', $currentAdmin->business_id)
                            ->whereIn('id', $value)
                            ->pluck('id')
                            ->toArray();

                        if (count($staffIds) !== count($value)) {
                            $fail('Some selected staff members do not belong to your business.');
                        }
                    }
                ]
            ]);
        }

        $validated = $request->validate($validationRules);

        return DB::transaction(function () use ($request, $validated, $staff) {
            $staff->user->update([
                'name' => $validated['name'],
                'email' => $validated['email']
            ]);

            // Update password if provided
            if ($request->filled('password')) {
                $staff->user->update([
                    'password' => Hash::make($request->password)
                ]);
            }

            // Update the user's role
            $user = $staff->user;
            $user->syncRoles([$validated['role']]);

            // Update staff record
            $staff->update([
                'phone' => $request->phone,
                'disable_underprice' => $request->has('disable_underprice')
            ]);

            $staff->productCategories()->sync($validated['product_categories']);
            $staff->ledgers()->sync($validated['ledgers']);

            // Handle DSR staff assignments
            if ($request->role === 'dsr' && $request->has('assigned_staff')) {
                $staff->assignedStaffMembers()->sync($validated['assigned_staff']);
            } else {
                // If role is not DSR, remove all assignments
                $staff->assignedStaffMembers()->detach();
            }

            return redirect()->route('admin.staff.index')
                ->with('success', 'Staff updated successfully');
        });
    }

    public function destroy(Staff $staff)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        if ($staff->business_id !== $currentAdmin->business_id) {
            abort(403);
        }

        $staff->delete();
        return redirect()->route('admin.staff.index')
            ->with('success', 'Staff deleted successfully');
    }
}
