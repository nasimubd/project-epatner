<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryHead;
use App\Models\User;
use App\Models\Ledger;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use App\Models\BusinessAdmin;

class SalaryHeadController extends Controller
{
    /**
     * Display a listing of salary heads
     */
    public function index(Request $request)
    {
        try {
            $currentAdmin = Auth::user();

            // Check if user is authenticated
            if (!$currentAdmin) {
                Log::error('Unauthenticated user trying to access salary heads index');
                throw new \Exception('Authentication required. Please login to access this page.');
            }

            // Get the authenticated user's business ID through staff or admin relationship
            $businessId = null;
            if (Auth::user()->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', Auth::id())->first();
                $businessId = $staff ? $staff->business_id : null;
            } elseif (Auth::user()->roles->contains('name', 'admin')) {
                $admin = BusinessAdmin::where('user_id', Auth::id())->first();
                $businessId = $admin ? $admin->business_id : null;
            }

            // Check if we found a business ID
            if (!$businessId) {
                Log::error('User without business_id trying to access salary heads', [
                    'user_id' => $currentAdmin->id,
                    'user_email' => $currentAdmin->email,
                    'user_roles' => $currentAdmin->roles->pluck('name')->toArray()
                ]);
                throw new \Exception('No business associated with your account. User ID: ' . $currentAdmin->id . ' | Roles: ' . $currentAdmin->roles->pluck('name')->implode(', '));
            }

            // Check if SalaryHead model exists
            if (!class_exists('App\Models\SalaryHead')) {
                Log::error('SalaryHead model not found');
                throw new \Exception('SalaryHead model not found. Please ensure App\Models\SalaryHead exists.');
            }

            // Check if salary_heads table exists
            try {
                DB::connection()->getPdo();
                DB::table('salary_heads')->limit(1)->get();
            } catch (\Exception $dbException) {
                Log::error('Database error in salary heads index', [
                    'error' => $dbException->getMessage(),
                    'user_id' => $currentAdmin->id
                ]);
                throw new \Exception('Database error: salary_heads table not found. Please run migrations. Original error: ' . $dbException->getMessage());
            }

            $query = SalaryHead::with(['user.roles', 'salaryAccountLedger', 'createdBy', 'approvedBy'])
                ->where('business_id', $businessId);

            // Filter by status if provided
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            // Filter by role if provided
            if ($request->has('role_name') && $request->role_name !== '') {
                $query->whereHas('user.roles', function ($q) use ($request) {
                    $q->where('name', $request->role_name);
                });
            }

            // Search by user name or staff ID
            if ($request->has('search') && $request->search !== '') {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('staff_id', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            }

            $salaryHeads = $query->orderBy('created_at', 'desc')->paginate(15);

            // Get all available roles for filter dropdown
            try {
                $availableRoles = Role::all();
            } catch (\Exception $roleException) {
                Log::error('Error fetching roles for salary heads filter', [
                    'error' => $roleException->getMessage(),
                    'user_id' => $currentAdmin->id
                ]);
                throw new \Exception('Error fetching roles: ' . $roleException->getMessage());
            }

            // Log successful access
            Log::info('Salary heads index accessed successfully', [
                'user_id' => $currentAdmin->id,
                'business_id' => $businessId,
                'total_salary_heads' => $salaryHeads->total(),
                'filters' => $request->only(['status', 'role_name', 'search'])
            ]);

            return view('admin.salary-heads.index', compact('salaryHeads', 'availableRoles'));
        } catch (\Illuminate\Database\QueryException $dbException) {
            Log::error('Database query error in salary heads index', [
                'error' => $dbException->getMessage(),
                'sql' => $dbException->getSql() ?? 'N/A',
                'bindings' => $dbException->getBindings() ?? [],
                'user_id' => Auth::id(),
                'business_id' => $businessId ?? null
            ]);

            throw new \Exception('Database query error: ' . $dbException->getMessage() . ' | SQL: ' . ($dbException->getSql() ?? 'N/A'));
        } catch (\Exception $e) {
            Log::error('Error in salary heads index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'business_id' => $businessId ?? null,
                'request_data' => $request->all()
            ]);

            // Re-throw the exception to show the actual error
            throw $e;
        }
    }



    /**
     * Show the form for creating a new salary head
     */
    public function create()
    {
        try {
            $currentAdmin = Auth::user();

            // Check if user is authenticated
            if (!$currentAdmin) {
                Log::error('Unauthenticated user trying to access salary head creation form');
                throw new \Exception('Authentication required. Please login to access this page.');
            }

            // Get the authenticated user's business ID through staff or admin relationship
            $businessId = null;
            if (Auth::user()->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', Auth::id())->first();
                $businessId = $staff ? $staff->business_id : null;
            } elseif (Auth::user()->roles->contains('name', 'admin')) {
                $admin = BusinessAdmin::where('user_id', Auth::id())->first();
                $businessId = $admin ? $admin->business_id : null;
            }

            // Check if we found a business ID
            if (!$businessId) {
                Log::error('User without business_id trying to access salary head creation form', [
                    'user_id' => $currentAdmin->id,
                    'user_email' => $currentAdmin->email,
                    'user_roles' => $currentAdmin->roles->pluck('name')->toArray()
                ]);
                throw new \Exception('No business associated with your account. User ID: ' . $currentAdmin->id . ' | Roles: ' . $currentAdmin->roles->pluck('name')->implode(', '));
            }

            // Get user IDs from Staff table for this business
            $staffUserIds = Staff::where('business_id', $businessId)
                ->pluck('user_id')
                ->toArray();

            // Get user IDs from BusinessAdmin table for this business
            $adminUserIds = BusinessAdmin::where('business_id', $businessId)
                ->pluck('user_id')
                ->toArray();

            // Combine both arrays to get all users associated with this business
            $businessUserIds = array_merge($staffUserIds, $adminUserIds);

            // Get users who don't have salary heads yet from both staff and business admin tables
            $availableUsers = User::whereIn('id', $businessUserIds)
                ->whereDoesntHave('salaryHead', function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })
                ->where('id', '!=', Auth::id()) // Exclude current admin
                ->with('roles') // Load user roles
                ->get();

            // Get only DSR, Staff, and Admin roles
            $roles = Role::whereIn('name', ['dsr', 'staff', 'admin'])->get();

            // Get Salary Payable ledgers
            $salaryLedgers = Ledger::where('business_id', $businessId)
                ->where('ledger_type', 'Salary Payable')
                ->where('status', 'active')
                ->get();

            // Log successful access
            Log::info('Salary head creation form accessed successfully', [
                'user_id' => $currentAdmin->id,
                'business_id' => $businessId,
                'staff_user_ids_count' => count($staffUserIds),
                'admin_user_ids_count' => count($adminUserIds),
                'total_business_users' => count($businessUserIds),
                'available_users_count' => $availableUsers->count(),
                'available_roles_count' => $roles->count(),
                'salary_ledgers_count' => $salaryLedgers->count()
            ]);

            return view('admin.salary-heads.create', compact('availableUsers', 'roles', 'salaryLedgers'));
        } catch (\Exception $e) {
            Log::error('Error loading salary head creation form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'business_id' => $businessId ?? null
            ]);

            // Re-throw the exception to show the actual error instead of redirecting
            throw $e;
        }
    }


    /**
     * Store a newly created salary head
     */
    public function store(Request $request)
    {
        try {
            $currentAdmin = Auth::user();

            // Check if user is authenticated
            if (!$currentAdmin) {
                Log::error('Unauthenticated user trying to store salary head');
                throw new \Exception('Authentication required. Please login to access this page.');
            }

            // Get the authenticated user's business ID through staff or admin relationship
            $businessId = null;
            if (Auth::user()->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', Auth::id())->first();
                $businessId = $staff ? $staff->business_id : null;
            } elseif (Auth::user()->roles->contains('name', 'admin')) {
                $admin = BusinessAdmin::where('user_id', Auth::id())->first();
                $businessId = $admin ? $admin->business_id : null;
            }

            // Check if we found a business ID
            if (!$businessId) {
                Log::error('User without business_id trying to store salary head', [
                    'user_id' => $currentAdmin->id,
                    'user_email' => $currentAdmin->email,
                    'user_roles' => $currentAdmin->roles->pluck('name')->toArray()
                ]);
                throw new \Exception('No business associated with your account. User ID: ' . $currentAdmin->id . ' | Roles: ' . $currentAdmin->roles->pluck('name')->implode(', '));
            }

            // Validation rules
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'role_name' => 'required|string|exists:roles,name',
                'salary_account_ledger_id' => 'required|exists:ledgers,id',
                'salary_amount' => 'required|numeric|min:0|max:999999.99',
            ]);

            if ($validator->fails()) {
                $errors = $validator->errors()->all();
                Log::error('Validation failed for salary head creation', [
                    'errors' => $errors,
                    'request_data' => $request->all(),
                    'user_id' => Auth::id()
                ]);
                throw new \Exception('Validation failed: ' . implode(', ', $errors));
            }

            $validated = $validator->validated();

            // Check if user already has a salary head in this business
            $existingSalaryHead = SalaryHead::where('user_id', $validated['user_id'])
                ->where('business_id', $businessId)
                ->first();

            if ($existingSalaryHead) {
                Log::error('User already has salary head', [
                    'user_id' => $validated['user_id'],
                    'business_id' => $businessId,
                    'existing_salary_head_id' => $existingSalaryHead->id
                ]);
                throw new \Exception('This user already has a salary head in your business. Existing Salary Head ID: ' . $existingSalaryHead->id);
            }

            // Verify the selected ledger is Salary Payable type
            $salaryLedger = Ledger::where('id', $validated['salary_account_ledger_id'])
                ->where('business_id', $businessId)
                ->where('ledger_type', 'Salary Payable')
                ->first();

            if (!$salaryLedger) {
                Log::error('Invalid salary ledger selected', [
                    'ledger_id' => $validated['salary_account_ledger_id'],
                    'business_id' => $businessId
                ]);
                throw new \Exception('Invalid salary account ledger selected. Ledger ID: ' . $validated['salary_account_ledger_id'] . ' not found or not Salary Payable type for business: ' . $businessId);
            }

            // Get the role
            $role = Role::where('name', $validated['role_name'])->first();
            if (!$role) {
                Log::error('Invalid role selected', [
                    'role_name' => $validated['role_name']
                ]);
                throw new \Exception('Invalid role selected: ' . $validated['role_name']);
            }

            // Verify role is one of the allowed roles
            if (!in_array($validated['role_name'], ['dsr', 'staff', 'admin'])) {
                Log::error('Role not allowed for salary head', [
                    'role_name' => $validated['role_name']
                ]);
                throw new \Exception('Role not allowed for salary head: ' . $validated['role_name'] . '. Only DSR, Staff, and Admin roles are allowed.');
            }

            DB::beginTransaction();

            try {
                // Generate unique staff ID
                $staffId = $this->generateStaffId($businessId);

                // Create salary head - Include role_id field
                $salaryHead = SalaryHead::create([
                    'user_id' => $validated['user_id'],
                    'staff_id' => $staffId,
                    'role_id' => $role->id, // Store role ID (required field)
                    'salary_account_ledger_id' => $validated['salary_account_ledger_id'],
                    'salary_amount' => $validated['salary_amount'],
                    'status' => 'pending',
                    'created_by' => Auth::id(),
                    'business_id' => $businessId,
                ]);

                if (!$salaryHead || !$salaryHead->id) {
                    throw new \Exception('Failed to create salary head record in database');
                }

                // Get user basic info
                $user = User::find($validated['user_id']);
                if (!$user) {
                    throw new \Exception('User not found with ID: ' . $validated['user_id']);
                }

                $basicInfo = [
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?? null,
                    'role_name' => $validated['role_name'],
                    'salary_amount' => $validated['salary_amount'],
                    'created_date' => now()->format('Y-m-d H:i:s')
                ];

                // Create or update staff record
                $staff = Staff::updateOrCreate(
                    ['user_id' => $validated['user_id'], 'business_id' => $businessId],
                    [
                        'salary_head_id' => $salaryHead->id,
                        'staff_id' => $staffId,
                        'role_id' => $role->id, // Store role ID in staff table too
                        'basic_info' => $basicInfo,
                        'status' => 'inactive', // Will be activated when salary head is approved
                    ]
                );

                if (!$staff) {
                    throw new \Exception('Failed to create or update staff record');
                }

                // Assign role to user if not already assigned
                if (!$user->hasRole($validated['role_name'])) {
                    $user->assignRole($validated['role_name']);
                    Log::info('Role assigned to user', [
                        'user_id' => $user->id,
                        'role_name' => $validated['role_name']
                    ]);
                }

                DB::commit();

                Log::info('Salary head created successfully', [
                    'salary_head_id' => $salaryHead->id,
                    'staff_id' => $staffId,
                    'user_id' => $validated['user_id'],
                    'role_id' => $role->id,
                    'role_name' => $validated['role_name'],
                    'created_by' => Auth::id(),
                    'business_id' => $businessId
                ]);

                // Redirect to index with success message
                return redirect()->route('admin.salary-heads.index')
                    ->with('success', "Salary head created successfully for {$user->name}. Staff ID: {$staffId}. Status: Pending approval.");
            } catch (\Exception $dbException) {
                DB::rollBack();
                Log::error('Database transaction failed during salary head creation', [
                    'error' => $dbException->getMessage(),
                    'trace' => $dbException->getTraceAsString(),
                    'request_data' => $request->all(),
                    'user_id' => Auth::id(),
                    'business_id' => $businessId
                ]);
                throw new \Exception('Database transaction failed: ' . $dbException->getMessage());
            }
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Error creating salary head', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
                'user_id' => Auth::id(),
                'business_id' => $businessId ?? null
            ]);

            // Re-throw the exception to show the actual error
            throw $e;
        }
    }




    /**
     * Display the specified salary head
     */
    public function show(SalaryHead $salaryHead)
    {
        try {
            $currentAdmin = Auth::user();

            // Check if salary head belongs to current business
            if ($salaryHead->business_id !== $currentAdmin->business_id) {
                return redirect()->route('admin.salary-heads.index')
                    ->with('error', 'Salary head not found.');
            }

            $salaryHead->load(['user.roles', 'salaryAccountLedger', 'createdBy', 'approvedBy', 'staff']);

            return view('admin.salary-heads.show', compact('salaryHead'));
        } catch (\Exception $e) {
            Log::error('Error showing salary head', [
                'salary_head_id' => $salaryHead->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->route('admin.salary-heads.index')
                ->with('error', 'Failed to load salary head details.');
        }
    }

    /**
     * Show the form for editing the specified salary head
     */
    public function edit(SalaryHead $salaryHead)
    {
        try {
            $currentAdmin = Auth::user();

            // Check if salary head belongs to current business
            if ($salaryHead->business_id !== $currentAdmin->business_id) {
                return redirect()->route('admin.salary-heads.index')
                    ->with('error', 'Salary head not found.');
            }

            // Only allow editing if status is pending
            if ($salaryHead->status !== 'pending') {
                return redirect()->route('admin.salary-heads.show', $salaryHead)
                    ->with('error', 'Only pending salary heads can be edited.');
            }

            $businessId = $currentAdmin->business_id;

            // Get all available roles
            $availableRoles = Role::all();

            // Get Salary Payable ledgers
            $salaryLedgers = Ledger::where('business_id', $businessId)
                ->where('ledger_type', 'Salary Payable')
                ->where('status', 'active')
                ->get();

            $salaryHead->load(['user.roles', 'salaryAccountLedger']);

            return view('admin.salary-heads.edit', compact('salaryHead', 'availableRoles', 'salaryLedgers'));
        } catch (\Exception $e) {
            Log::error('Error loading salary head edit form', [
                'salary_head_id' => $salaryHead->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->route('admin.salary-heads.index')
                ->with('error', 'Failed to load edit form.');
        }
    }

    /**
     * Update the specified salary head
     */
    public function update(Request $request, SalaryHead $salaryHead)
    {
        try {
            $currentAdmin = Auth::user();

            // Check if salary head belongs to current business
            if ($salaryHead->business_id !== $currentAdmin->business_id) {
                return redirect()->route('admin.salary-heads.index')
                    ->with('error', 'Salary head not found.');
            }

            // Only allow updating if status is pending
            if ($salaryHead->status !== 'pending') {
                return redirect()->route('admin.salary-heads.show', $salaryHead)
                    ->with('error', 'Only pending salary heads can be updated.');
            }

            // Validation rules
            $validator = Validator::make($request->all(), [
                'role_name' => 'required|string|exists:roles,name',
                'salary_account_ledger_id' => 'required|exists:ledgers,id',
                'salary_amount' => 'required|numeric|min:0|max:999999.99',
            ]);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $validated = $validator->validated();

            // Verify the selected ledger is Salary Payable type
            $salaryLedger = Ledger::where('id', $validated['salary_account_ledger_id'])
                ->where('business_id', $currentAdmin->business_id)
                ->where('ledger_type', 'Salary Payable')
                ->first();

            if (!$salaryLedger) {
                return redirect()->back()
                    ->with('error', 'Invalid salary account ledger selected.')
                    ->withInput();
            }

            // Verify role exists
            $role = Role::where('name', $validated['role_name'])->first();
            if (!$role) {
                return redirect()->back()
                    ->with('error', 'Invalid role selected.')
                    ->withInput();
            }

            DB::beginTransaction();

            // Update salary head
            $salaryHead->update([
                'role_name' => $validated['role_name'],
                'salary_account_ledger_id' => $validated['salary_account_ledger_id'],
                'salary_amount' => $validated['salary_amount'],
            ]);

            // Update staff record if exists
            if ($salaryHead->staff) {
                $basicInfo = $salaryHead->staff->basic_info;
                $basicInfo['role_name'] = $validated['role_name'];
                $basicInfo['salary_amount'] = $validated['salary_amount'];
                $basicInfo['updated_date'] = now()->format('Y-m-d H:i:s');

                $salaryHead->staff->update([
                    'role_name' => $validated['role_name'],
                    'basic_info' => $basicInfo,
                ]);
            }

            // Update user role if changed
            $user = $salaryHead->user;
            if (!$user->hasRole($validated['role_name'])) {
                // Remove old role if it was assigned through salary head
                // and assign new role
                $user->syncRoles([$validated['role_name']]);
            }

            DB::commit();

            Log::info('Salary head updated successfully', [
                'salary_head_id' => $salaryHead->id,
                'updated_by' => Auth::id(),
                'changes' => $validated
            ]);

            return redirect()->route('admin.salary-heads.show', $salaryHead)
                ->with('success', 'Salary head updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error updating salary head', [
                'salary_head_id' => $salaryHead->id,
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to update salary head. Please try again.')
                ->withInput();
        }
    }

    /**
     * Remove the specified salary head
     */
    public function destroy(SalaryHead $salaryHead)
    {
        try {
            $currentAdmin = Auth::user();

            // Check if salary head belongs to current business
            if ($salaryHead->business_id !== $currentAdmin->business_id) {
                return redirect()->route('admin.salary-heads.index')
                    ->with('error', 'Salary head not found.');
            }

            // Only allow deletion if status is pending or rejected
            if (!in_array($salaryHead->status, ['pending', 'rejected'])) {
                return redirect()->route('admin.salary-heads.show', $salaryHead)
                    ->with('error', 'Only pending or rejected salary heads can be deleted.');
            }

            DB::beginTransaction();

            $staffId = $salaryHead->staff_id;
            $userName = $salaryHead->user->name;

            // Delete associated staff record if exists
            if ($salaryHead->staff) {
                $salaryHead->staff->delete();
            }

            // Delete salary head
            $salaryHead->delete();

            DB::commit();

            Log::info('Salary head deleted successfully', [
                'salary_head_id' => $salaryHead->id,
                'staff_id' => $staffId,
                'deleted_by' => Auth::id(),
                'user_name' => $userName
            ]);

            return redirect()->route('admin.salary-heads.index')
                ->with('success', "Salary head for {$userName} (Staff ID: {$staffId}) has been deleted successfully.");
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error deleting salary head', [
                'salary_head_id' => $salaryHead->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return redirect()->back()
                ->with('error', 'Failed to delete salary head. Please try again.');
        }
    }

    /**
     * Approve a salary head
     */
    public function approve(SalaryHead $salaryHead)
    {
        try {
            $currentAdmin = Auth::user();

            // Check if salary head belongs to current business
            if ($salaryHead->business_id !== $currentAdmin->business_id) {
                return response()->json(['error' => 'Salary head not found.'], 404);
            }

            // Only allow approval if status is pending
            if ($salaryHead->status !== 'pending') {
                return response()->json(['error' => 'Only pending salary heads can be approved.'], 400);
            }

            DB::beginTransaction();

            // Update salary head status
            $salaryHead->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approval_date' => now(),
                'rejection_reason' => null, // Clear any previous rejection reason
            ]);

            // Activate associated staff record
            if ($salaryHead->staff) {
                $salaryHead->staff->update([
                    'status' => 'active'
                ]);
            }

            DB::commit();

            Log::info('Salary head approved successfully', [
                'salary_head_id' => $salaryHead->id,
                'staff_id' => $salaryHead->staff_id,
                'approved_by' => Auth::id(),
                'user_name' => $salaryHead->user->name
            ]);

            return response()->json([
                'success' => true,
                'message' => "Salary head for {$salaryHead->user->name} has been approved successfully.",
                'status' => 'approved'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error approving salary head', [
                'salary_head_id' => $salaryHead->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json(['error' => 'Failed to approve salary head. Please try again.'], 500);
        }
    }

    /**
     * Reject a salary head
     */
    public function reject(Request $request, SalaryHead $salaryHead)
    {
        try {
            $currentAdmin = Auth::user();

            // Check if salary head belongs to current business
            if ($salaryHead->business_id !== $currentAdmin->business_id) {
                return response()->json(['error' => 'Salary head not found.'], 404);
            }

            // Only allow rejection if status is pending
            if ($salaryHead->status !== 'pending') {
                return response()->json(['error' => 'Only pending salary heads can be rejected.'], 400);
            }

            // Validate rejection reason
            $validator = Validator::make($request->all(), [
                'rejection_reason' => 'required|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Rejection reason is required.'], 400);
            }

            DB::beginTransaction();

            // Update salary head status
            $salaryHead->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approval_date' => now(),
                'rejection_reason' => $request->rejection_reason,
            ]);

            // Deactivate associated staff record
            if ($salaryHead->staff) {
                $salaryHead->staff->update([
                    'status' => 'inactive'
                ]);
            }

            DB::commit();

            Log::info('Salary head rejected successfully', [
                'salary_head_id' => $salaryHead->id,
                'staff_id' => $salaryHead->staff_id,
                'rejected_by' => Auth::id(),
                'rejection_reason' => $request->rejection_reason,
                'user_name' => $salaryHead->user->name
            ]);

            return response()->json([
                'success' => true,
                'message' => "Salary head for {$salaryHead->user->name} has been rejected.",
                'status' => 'rejected'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error rejecting salary head', [
                'salary_head_id' => $salaryHead->id,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json(['error' => 'Failed to reject salary head. Please try again.'], 500);
        }
    }

    /**
     * Bulk approve salary heads
     */
    public function bulkApprove(Request $request)
    {
        try {
            $currentAdmin = Auth::user();
            $businessId = $currentAdmin->business_id;

            $validator = Validator::make($request->all(), [
                'salary_head_ids' => 'required|array',
                'salary_head_ids.*' => 'exists:salary_heads,id',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => 'Invalid salary head selection.'], 400);
            }

            DB::beginTransaction();

            $approvedCount = 0;
            $errors = [];

            foreach ($request->salary_head_ids as $salaryHeadId) {
                try {
                    $salaryHead = SalaryHead::where('id', $salaryHeadId)
                        ->where('business_id', $businessId)
                        ->where('status', 'pending')
                        ->first();

                    if ($salaryHead) {
                        $salaryHead->update([
                            'status' => 'approved',
                            'approved_by' => Auth::id(),
                            'approval_date' => now(),
                            'rejection_reason' => null,
                        ]);

                        // Activate associated staff record
                        if ($salaryHead->staff) {
                            $salaryHead->staff->update(['status' => 'active']);
                        }

                        $approvedCount++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Failed to approve salary head ID: {$salaryHeadId}";
                }
            }

            DB::commit();

            Log::info('Bulk salary head approval completed', [
                'approved_count' => $approvedCount,
                'total_requested' => count($request->salary_head_ids),
                'errors' => $errors,
                'approved_by' => Auth::id()
            ]);

            $message = "{$approvedCount} salary head(s) approved successfully.";
            if (!empty($errors)) {
                $message .= " Some approvals failed.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'approved_count' => $approvedCount,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in bulk salary head approval', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json(['error' => 'Failed to process bulk approval. Please try again.'], 500);
        }
    }

    /**
     * Get pending salary heads count
     */
    public function getPendingCount()
    {
        try {
            $currentAdmin = Auth::user();
            $businessId = $currentAdmin->business_id;

            $pendingCount = SalaryHead::where('business_id', $businessId)
                ->where('status', 'pending')
                ->count();

            return response()->json(['pending_count' => $pendingCount]);
        } catch (\Exception $e) {
            Log::error('Error getting pending salary heads count', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json(['error' => 'Failed to get pending count.'], 500);
        }
    }

    /**
     * Generate unique staff ID
     */
    private function generateStaffId($businessId)
    {
        $prefix = 'STAFF';
        $year = date('Y');

        // Get the last staff ID for this business and year
        $lastStaffId = SalaryHead::where('business_id', $businessId)
            ->where('staff_id', 'like', "{$prefix}-{$year}-%")
            ->orderBy('staff_id', 'desc')
            ->value('staff_id');

        if ($lastStaffId) {
            // Extract the number part and increment
            $lastNumber = (int) substr($lastStaffId, strrpos($lastStaffId, '-') + 1);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        // Format: STAFF-2024-001
        return sprintf('%s-%s-%03d', $prefix, $year, $newNumber);
    }

    /**
     * Check if staff ID is unique
     */
    private function isStaffIdUnique($staffId)
    {
        return !SalaryHead::where('staff_id', $staffId)->exists();
    }

    /**
     * Get salary head statistics
     */
    public function getStatistics()
    {
        try {
            $currentAdmin = Auth::user();
            $businessId = $currentAdmin->business_id;

            $statistics = [
                'total' => SalaryHead::where('business_id', $businessId)->count(),
                'pending' => SalaryHead::where('business_id', $businessId)->where('status', 'pending')->count(),
                'approved' => SalaryHead::where('business_id', $businessId)->where('status', 'approved')->count(),
                'rejected' => SalaryHead::where('business_id', $businessId)->where('status', 'rejected')->count(),
                'total_salary_amount' => SalaryHead::where('business_id', $businessId)
                    ->where('status', 'approved')
                    ->sum('salary_amount'),
            ];

            return response()->json($statistics);
        } catch (\Exception $e) {
            Log::error('Error getting salary head statistics', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            return response()->json(['error' => 'Failed to get statistics.'], 500);
        }
    }
}
