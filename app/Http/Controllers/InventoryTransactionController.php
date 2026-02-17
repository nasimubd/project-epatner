<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionLine;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessAdmin;
use App\Models\Ledger;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\Log;
use App\Models\Staff;
use App\Models\TransactionLine;
use App\Models\Transaction;
use App\Models\ReturnedProduct;
use App\Models\DamageTransactionLine;
use App\Models\Business;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Schema;
use App\Models\CommonProduct;

class InventoryTransactionController extends Controller
{
    protected $customerService;

    public function __construct(\App\Services\CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function index(Request $request)
    {
        // Get the current authenticated user
        $user = Auth::user();
        $userId = $user->id;
        $cacheKey = "inventory_transaction_filters_{$userId}";

        // Determine business ID and user type first
        $businessId = null;
        $userType = null;

        if ($user->roles->pluck('name')->contains('staff')) {
            $staff = Staff::where('user_id', $user->id)->first();
            if (!$staff) {
                dd('No staff record found for user', $user);
            }
            $businessId = $staff->business_id;
            $userType = 'staff';
        } elseif ($user->roles->pluck('name')->contains('dsr')) {
            $staff = Staff::where('user_id', $user->id)->first();
            if (!$staff) {
                dd('No staff record found for user', $user);
            }
            $businessId = $staff->business_id;
            $userType = 'dsr';
        } elseif ($user->roles->pluck('name')->contains('admin')) {
            $admin = BusinessAdmin::where('user_id', $user->id)->first();
            if (!$admin) {
                dd('No business admin record found for user', $user);
            }
            $businessId = $admin->business_id;
            $userType = 'admin';
        }

        if (!$businessId) {
            dd('No business ID found for user', $user);
        }

        // Handle filters based on user type
        $filters = [];
        if ($request->has('reset_filters')) {
            Cache::forget($cacheKey);
            return redirect()->route('admin.inventory.inventory_transactions.index');
        }

        if ($userType === 'staff') {
            // For staff, use direct request filters without caching
            $filters = [
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'search' => $request->input('search'),
                'type' => $request->input('type'),
                'due_filter' => $request->input('due_filter')
            ];
        } else {
            // For admin and DSR, use cached filters
            $hasNewFilters = $request->has('start_date') || $request->has('end_date') ||
                $request->has('search') || $request->has('type') ||
                $request->has('due_filter');
            $isFromRedirect = $request->has('from_cache');

            if ($hasNewFilters && !$isFromRedirect) {
                // User is applying new filters
                $filters = [
                    'start_date' => $request->input('start_date'),
                    'end_date' => $request->input('end_date'),
                    'search' => $request->input('search'),
                    'type' => $request->input('type'),
                    'due_filter' => $request->input('due_filter')
                ];
                Cache::put($cacheKey, $filters, now()->addHours(2));
            } else {
                // Check for cached filters
                $cachedFilters = Cache::get($cacheKey);
                if ($cachedFilters && !$request->has('page') && !$isFromRedirect && !$hasNewFilters) {
                    // Only redirect if we have cached filters and this isn't a pagination request
                    // or a redirect we already made
                    $redirectParams = array_merge($cachedFilters, ['from_cache' => '1']);
                    return redirect()->route('admin.inventory.inventory_transactions.index', $redirectParams);
                }

                // Use cached filters if available, otherwise empty array
                $filters = $cachedFilters ?: [];

                // If this is from a redirect, use the request parameters
                if ($isFromRedirect) {
                    $filters = [
                        'start_date' => $request->input('start_date'),
                        'end_date' => $request->input('end_date'),
                        'search' => $request->input('search'),
                        'type' => $request->input('type'),
                        'due_filter' => $request->input('due_filter')
                    ];
                }
            }
        }

        // Query transactions
        $query = InventoryTransaction::with(['ledger', 'creators.user'])
            ->where('business_id', $businessId);

        // Apply role-based filtering
        if ($userType === 'staff') {
            $currentStaff = Staff::where('user_id', $user->id)->first();

            // Staff can ONLY see their own created SALES transactions
            $query->where('entry_type', 'sale')
                ->whereHas('creators', function ($creatorQ) use ($currentStaff) {
                    $creatorQ->where('staff_id', $currentStaff->id);
                });
        } elseif ($userType === 'dsr') {
            $currentDsr = Staff::where('user_id', $user->id)->first();

            // Get staff IDs assigned to this DSR
            $assignedStaffIds = $currentDsr->assignedStaffMembers()->pluck('staff.id')->toArray();

            if (!empty($assignedStaffIds)) {
                // Show only sales transactions created by assigned staff members
                $query->where('entry_type', 'sale')
                    ->whereHas('creators', function ($creatorQ) use ($assignedStaffIds) {
                        $creatorQ->whereIn('staff_id', $assignedStaffIds);
                    });
            } else {
                // If no staff assigned, show no transactions
                $query->where('id', -1); // This will return no results
            }
        }
        // Admin sees all transactions (no additional filtering needed)

        // Apply date filters
        if (!empty($filters['start_date'])) {
            $query->whereDate('transaction_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->whereDate('transaction_date', '<=', $filters['end_date']);
        }

        // Enhanced search functionality
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('invoice_id', 'like', "%{$search}%")
                    ->orWhereHas('ledger', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (!empty($filters['type'])) {
            $query->where('entry_type', $filters['type']);
        }

        // Apply due filter
        if (!empty($filters['due_filter'])) {
            if ($filters['due_filter'] === 'due_only') {
                $query->where('payment_method', 'credit')
                    ->where('grand_total', '>', 0);
            } elseif ($filters['due_filter'] === 'paid_only') {
                $query->where(function ($q) {
                    $q->where('payment_method', 'cash')
                        ->orWhere('grand_total', '<=', 0);
                });
            }
        }

        // Get all staff members for filter dropdown
        $staffMembers = Staff::with('user')
            ->where('business_id', $businessId)
            ->get();

        $transactions = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        // Get all staff IDs from the transactions
        $staffIds = [];
        foreach ($transactions as $transaction) {
            if ($transaction->creators->isNotEmpty()) {
                $staffIds[] = $transaction->creators->first()->id;
            }
        }

        // For each staff, track customers and assign memo numbers
        $staffMemoNumbers = [];
        $staffLastCustomers = [];

        if (!empty($staffIds)) {
            foreach (array_unique($staffIds) as $staffId) {
                // Get all transactions for this staff ordered by creation date
                $staffTransactions = InventoryTransaction::whereHas('creators', function ($q) use ($staffId) {
                    $q->where('staff_id', $staffId);
                })
                    ->with('ledger')
                    ->orderBy('created_at', 'asc')
                    ->get();

                $memoNumberMap = [];
                $lastCustomerId = null;
                $memoCounter = 1;

                foreach ($staffTransactions as $staffTrx) {
                    $currentCustomerId = $staffTrx->ledger_id;

                    if ($currentCustomerId != $lastCustomerId) {
                        $memoCounter++;
                    }

                    $memoNumberMap[$staffTrx->id] = $memoCounter;
                    $lastCustomerId = $currentCustomerId;
                }

                $staffMemoNumbers[$staffId] = $memoNumberMap;
            }
        }

        // Attach memo numbers and contributors to transactions
        foreach ($transactions as $transaction) {
            if ($transaction->creators->isNotEmpty()) {
                $creator = $transaction->creators->first();
                if ($creator) {
                    $transaction->memo_number = $staffMemoNumbers[$creator->id][$transaction->id] ?? 1;
                    $transaction->contributors = $transaction->creators
                        ->filter(function ($staff) use ($creator) {
                            return $staff->id != $creator->id;
                        })
                        ->unique('id');
                }
            }
        }

        // Fetch products
        $products = Product::where('business_id', $businessId)->get();

        if ($products->isEmpty()) {
            Log::warning('No products found for business', [
                'business_id' => $businessId,
                'user_type' => $userType,
                'user_id' => $user->id,
                'total_products' => Product::count()
            ]);
        }

        // For DSR, get their ledger information
        $dsrLedger = null;
        $pendingCollections = null;

        if ($userType === 'dsr') {
            $dsrLedger = Staff::where('user_id', $user->id)
                ->first()
                ->ledgers()
                ->first();

            if ($dsrLedger) {
                $pendingCollections = $transactions->where('grand_total', '>', 0)->count();
            }
        }

        return view('admin.inventory.inventory_transactions.index', compact(
            'transactions',
            'staffMembers',
            'products',
            'userType',
            'dsrLedger',
            'pendingCollections',
            'filters'
        ));
    }



    // public function create(Request $request)
    // {
    //     $currentUser = Auth::user();

    //     // Check if user is an admin or staff
    //     $currentAdmin = BusinessAdmin::where('user_id', $currentUser->id)->first();
    //     $currentStaff = Staff::where('user_id', $currentUser->id)->first();

    //     // Determine business_id and user type
    //     if ($currentAdmin) {
    //         $businessId = $currentAdmin->business_id;
    //         $userType = 'admin';
    //         $disableUnderprice = false; // Admins can always underprice
    //     } elseif ($currentStaff) {
    //         $businessId = $currentStaff->business_id;
    //         $userType = 'staff';
    //         $disableUnderprice = (bool)$currentStaff->disable_underprice;

    //         // Check if staff has assigned categories
    //         $staffCategories = $currentStaff->productCategories;
    //         if ($staffCategories->isEmpty()) {
    //             return back()->withErrors('No product categories have been assigned to you. Please contact an administrator.');
    //         }
    //     } else {
    //         return back()->withErrors('No business associated with this user.');
    //     }

    //     $transactionType = $request->input('type', 'purchase', 'sale');

    //     // Get all categories for this business, including common_category_id
    //     $categories = ProductCategory::where('business_id', $businessId)
    //         ->select('id', 'name', 'common_category_id')
    //         ->orderBy('name')
    //         ->get();

    //     // Create a mapping from common_category_id to business category
    //     $categoryMapping = [];
    //     foreach ($categories as $category) {
    //         if (!is_null($category->common_category_id)) {
    //             $categoryMapping[$category->common_category_id] = $category;
    //         }
    //     }

    //     // Fetch regular products based on user type
    //     if ($userType === 'staff') {
    //         $staffCategoryIds = $currentStaff->productCategories->pluck('id');
    //         $regularProducts = Product::with(['category', 'unit', 'batches'])
    //             ->where('business_id', $businessId)
    //             ->whereIn('category_id', $staffCategoryIds)
    //             ->select('id', 'name', 'category_id', 'trade_price', 'current_stock', 'barcode', 'image', 'unit_id')
    //             ->orderBy('category_id')
    //             ->orderBy('name')
    //             ->get();
    //     } else {
    //         // Admin can see all products
    //         $regularProducts = Product::with(['category' => function ($query) {
    //             $query->select('id', 'name');
    //         }, 'unit', 'batches'])
    //             ->where('business_id', $businessId)
    //             ->select('id', 'name', 'category_id', 'trade_price', 'current_stock', 'barcode', 'image', 'unit_id')
    //             ->orderBy('category_id')
    //             ->orderBy('name')
    //             ->get();
    //     }

    //     // Transform the image data for regular products
    //     $regularProducts->each(function ($product) {
    //         if ($product->image) {
    //             $product->image = base64_encode($product->image);
    //         }
    //         $product->is_common = false; // Mark as regular product
    //     });

    //     // Initialize all products collection with regular products
    //     $allProducts = collect($regularProducts);

    //     // Get common products if we have category mappings
    //     if (!empty($categoryMapping)) {
    //         try {
    //             $importedCommonCategoryIds = array_keys($categoryMapping);

    //             $commonProducts = DB::connection('mysql_common')
    //                 ->table('tbl_common_product')
    //                 ->whereIn('category_id', $importedCommonCategoryIds)
    //                 ->select('product_id as id', 'product_name as name', 'category_id', 'image')
    //                 ->get();

    //             // Transform common products to match Product model structure
    //             foreach ($commonProducts as $product) {
    //                 $matchingCategory = $categoryMapping[$product->category_id];

    //                 // For staff users, only include products from their assigned categories
    //                 if ($userType === 'staff' && !in_array($matchingCategory->id, $staffCategoryIds->toArray())) {
    //                     continue;
    //                 }

    //                 $newProduct = new \stdClass();
    //                 $newProduct->id = 'common_' . $product->id; // Prefix to avoid ID conflicts
    //                 $newProduct->name = $product->name;
    //                 $newProduct->category_id = $matchingCategory->id;
    //                 $newProduct->trade_price = 0;
    //                 $newProduct->current_stock = 0;
    //                 $newProduct->barcode = null;
    //                 $newProduct->image = $product->image ? base64_encode($product->image) : null;
    //                 $newProduct->unit_id = null;
    //                 $newProduct->is_common = true; // Mark as common product

    //                 // Create category relation
    //                 $newProduct->category = $matchingCategory;

    //                 // Create empty batches collection
    //                 $newProduct->batches = collect();

    //                 $allProducts->push($newProduct);
    //             }
    //         } catch (\Exception $e) {
    //             Log::error('Error fetching common products: ' . $e->getMessage());
    //             // Continue without common products
    //         }
    //     }

    //     // Fetch supplier ledgers (unchanged)
    //     $supplierLedgers = Ledger::where('business_id', $businessId)
    //         ->where('ledger_type', 'Sundry Creditors (Supplier)')
    //         ->get();

    //     // **UPDATED: Get customers from common database based on business imported locations**
    //     try {
    //         // Get business imported locations from business_sub_districts table
    //         $businessLocations = DB::table('business_sub_districts')
    //             ->where('business_id', $businessId)
    //             ->where('status', 'active')
    //             ->select('district', 'sub_district')
    //             ->get();

    //         if ($businessLocations->isEmpty()) {
    //             Log::warning('No imported locations found for business: ' . $businessId);
    //             $customerLedgers = collect([]);
    //             $uniqueVillages = collect([]);
    //         } else {
    //             // Get customers from common database matching business imported locations
    //             $commonCustomersQuery = DB::connection('mysql_common')
    //                 ->table('tbl_customer_ledgers');

    //             // Add location filters using OR conditions for each imported location
    //             $commonCustomersQuery->where(function ($query) use ($businessLocations) {
    //                 foreach ($businessLocations as $location) {
    //                     $query->orWhere(function ($subQuery) use ($location) {
    //                         $subQuery->where('district', $location->district)
    //                             ->where('sub_district', $location->sub_district);
    //                     });
    //                 }
    //             });

    //             // Optional: Only exclude customers that are explicitly marked as merged into another customer
    //             // Remove this filter if you want to see all customers regardless of merge status
    //             // $commonCustomersQuery->whereNull('merged_into');

    //             $commonCustomers = $commonCustomersQuery
    //                 ->select([
    //                     'ledger_id',
    //                     'ledger_name',
    //                     'contact_number',
    //                     'district',
    //                     'sub_district',
    //                     'village',
    //                     'type',
    //                     'location',
    //                     'landmark',
    //                     'data_quality_score',
    //                     'is_merged',
    //                     'duplicate_flags'
    //                 ])
    //                 ->orderBy('ledger_name')
    //                 ->get();

    //             Log::info('Common customers found after location filter only', [
    //                 'count' => $commonCustomers->count(),
    //                 'customers' => $commonCustomers->map(function ($c) {
    //                     return [
    //                         'id' => $c->ledger_id,
    //                         'name' => $c->ledger_name,
    //                         'is_merged' => $c->is_merged,
    //                         'duplicate_flags' => $c->duplicate_flags
    //                     ];
    //                 })->toArray()
    //             ]);

    //             // Get existing local customers to avoid duplicates
    //             $commonCustomerIds = $commonCustomers->pluck('ledger_id')->toArray();
    //             $existingLocalCustomers = Ledger::where('business_id', $businessId)
    //                 ->whereIn('common_customer_id', $commonCustomerIds)
    //                 ->pluck('common_customer_id', 'id')
    //                 ->toArray();

    //             // Get unique villages for location filter
    //             $uniqueVillages = $commonCustomers->pluck('village')
    //                 ->filter()
    //                 ->unique()
    //                 ->sort()
    //                 ->values();

    //             // Convert common customers to the format expected by the view
    //             // In your create method, replace the customer mapping section:

    //             // Convert common customers to the format expected by the view
    //             $customerLedgers = $commonCustomers->map(function ($customer) use ($existingLocalCustomers) {
    //                 // Check if this common customer already has a local copy
    //                 $localCustomerId = array_search($customer->ledger_id, $existingLocalCustomers);

    //                 // Build location string
    //                 $locationString = $customer->location;
    //                 if (empty($locationString)) {
    //                     $locationParts = array_filter([
    //                         $customer->village,
    //                         $customer->sub_district,
    //                         $customer->district
    //                     ]);
    //                     $locationString = implode(', ', $locationParts);
    //                 }

    //                 // Add landmark if available
    //                 if (!empty($customer->landmark)) {
    //                     $locationString .= ' (Near ' . $customer->landmark . ')';
    //                 }

    //                 return (object) [
    //                     // ALWAYS use the common customer ID for the dropdown value
    //                     'id' => $customer->ledger_id,
    //                     'name' => $customer->ledger_name,
    //                     'location' => $locationString,
    //                     'village' => $customer->village,
    //                     'contact' => $customer->contact_number ?? '',
    //                     'district' => $customer->district,
    //                     'sub_district' => $customer->sub_district,
    //                     'ledger_type' => $customer->type ?? 'Customer',
    //                     'landmark' => $customer->landmark,
    //                     'data_quality_score' => $customer->data_quality_score ?? 0,
    //                     'is_common' => true, // Always true since these are from common DB
    //                     'common_customer_id' => $customer->ledger_id,
    //                     'exists_locally' => (bool)$localCustomerId,
    //                     'local_customer_id' => $localCustomerId // Store local ID separately
    //                 ];
    //             });


    //             Log::info('Loaded customers from common database', [
    //                 'business_id' => $businessId,
    //                 'total_customers' => $customerLedgers->count(),
    //                 'unique_villages' => $uniqueVillages->count(),
    //                 'business_locations' => $businessLocations->count(),
    //                 'existing_local_customers' => count($existingLocalCustomers)
    //             ]);
    //         }
    //     } catch (\Exception $e) {
    //         Log::error('Error loading customers from common database: ' . $e->getMessage(), [
    //             'business_id' => $businessId,
    //             'trace' => $e->getTraceAsString()
    //         ]);
    //         // Fallback to empty collections
    //         $customerLedgers = collect([]);
    //         $uniqueVillages = collect([]);
    //     }

    //     return view('admin.inventory.inventory_transactions.create', [
    //         'products' => $allProducts, // Pass all products (regular + common)
    //         'categories' => $categories,
    //         'supplierLedgers' => $supplierLedgers,
    //         'customerLedgers' => $customerLedgers, // Now contains common customers from imported locations
    //         'uniqueVillages' => $uniqueVillages, // Pass villages for location filter
    //         'transactionType' => $transactionType,
    //         'userType' => $userType,
    //         'businessId' => $businessId,
    //         'disableUnderprice' => $disableUnderprice
    //     ]);
    // }

    public function create(Request $request)
    {
        // TEMPORARY MEMORY FIX
        ini_set('memory_limit', '512M');
        ini_set('max_execution_time', 300);
        gc_enable();

        $currentUser = Auth::user();

        // Check if user is an admin or staff
        $currentAdmin = BusinessAdmin::where('user_id', $currentUser->id)->first();
        $currentStaff = Staff::where('user_id', $currentUser->id)->first();

        // Determine business_id and user type
        if ($currentAdmin) {
            $businessId = $currentAdmin->business_id;
            $userType = 'admin';
            $disableUnderprice = false; // Admins can always underprice
        } elseif ($currentStaff) {
            $businessId = $currentStaff->business_id;
            $userType = 'staff';
            $disableUnderprice = (bool)$currentStaff->disable_underprice;

            // Check if staff has assigned categories
            $staffCategories = $currentStaff->productCategories;
            if ($staffCategories->isEmpty()) {
                return back()->withErrors('No product categories have been assigned to you. Please contact an administrator.');
            }
        } else {
            return back()->withErrors('No business associated with this user.');
        }

        $transactionType = $request->input('type', 'purchase', 'sale');

        // Get all categories for this business, including common_category_id
        $categories = ProductCategory::where('business_id', $businessId)
            ->select('id', 'name', 'common_category_id')
            ->orderBy('name')
            ->get();

        // Create a mapping from common_category_id to business category
        $categoryMapping = [];
        foreach ($categories as $category) {
            if (!is_null($category->common_category_id)) {
                $categoryMapping[$category->common_category_id] = $category;
            }
        }

        // OPTIMIZED: Fetch regular products based on user type
        if ($userType === 'staff') {
            $staffCategoryIds = $currentStaff->productCategories->pluck('id');
            $regularProducts = Product::with(['category', 'unit', 'batches'])
                ->where('business_id', $businessId)
                ->whereIn('category_id', $staffCategoryIds)
                ->select('id', 'name', 'category_id', 'trade_price', 'current_stock', 'barcode', 'unit_id')
                ->orderBy('category_id')
                ->orderBy('name')
                ->get();
        } else {
            // Admin can see all products
            $regularProducts = Product::with(['category' => function ($query) {
                $query->select('id', 'name');
            }, 'unit', 'batches'])
                ->where('business_id', $businessId)
                ->select('id', 'name', 'category_id', 'trade_price', 'current_stock', 'barcode', 'unit_id')
                ->orderBy('category_id')
                ->orderBy('name')
                ->get();
        }

        // OPTIMIZED: Load regular product images in chunks of 50
        $this->loadRegularProductImagesInChunks($regularProducts);

        // Initialize all products collection with regular products
        $allProducts = collect($regularProducts);

        // OPTIMIZED: Get common products based on user type and transaction type
        if ($userType === 'staff') {
            // STAFF: No common products loaded at all
            // Skip common products loading entirely
        } elseif ($userType === 'admin') {
            if ($transactionType === 'purchase') {
                // ADMIN PURCHASE: Load common products
                if (!empty($categoryMapping)) {
                    $commonProducts = $this->loadCommonProductsForAdmin($categoryMapping, $currentStaff ?? null, $userType);
                    // Add common products to all products
                    foreach ($commonProducts as $commonProduct) {
                        $allProducts->push($commonProduct);
                    }
                }
            }
            // ADMIN SALE: No common products loaded
        }

        // Fetch supplier ledgers (unchanged)
        $supplierLedgers = Ledger::where('business_id', $businessId)
            ->where('ledger_type', 'Sundry Creditors (Supplier)')
            ->get();

        // **UNCHANGED: Get customers from common database based on business imported locations**
        try {
            // Get business imported locations from business_sub_districts table
            $businessLocations = DB::table('business_sub_districts')
                ->where('business_id', $businessId)
                ->where('status', 'active')
                ->select('district', 'sub_district')
                ->get();

            if ($businessLocations->isEmpty()) {
                Log::warning('No imported locations found for business: ' . $businessId);
                $customerLedgers = collect([]);
                $uniqueVillages = collect([]);
            } else {
                // Get customers from common database matching business imported locations
                $commonCustomersQuery = DB::connection('mysql_common')
                    ->table('tbl_customer_ledgers');

                // Add location filters using OR conditions for each imported location
                $commonCustomersQuery->where(function ($query) use ($businessLocations) {
                    foreach ($businessLocations as $location) {
                        $query->orWhere(function ($subQuery) use ($location) {
                            $subQuery->where('district', $location->district)
                                ->where('sub_district', $location->sub_district);
                        });
                    }
                });

                $commonCustomers = $commonCustomersQuery
                    ->select([
                        'ledger_id',
                        'ledger_name',
                        'contact_number',
                        'district',
                        'sub_district',
                        'village',
                        'type',
                        'location',
                        'landmark',
                        'data_quality_score',
                        'is_merged',
                        'duplicate_flags'
                    ])
                    ->orderBy('ledger_name')
                    ->limit(1000) // Add reasonable limit
                    ->get();

                Log::info('Common customers found after location filter only', [
                    'count' => $commonCustomers->count(),
                    'customers' => $commonCustomers->map(function ($c) {
                        return [
                            'id' => $c->ledger_id,
                            'name' => $c->ledger_name,
                            'is_merged' => $c->is_merged,
                            'duplicate_flags' => $c->duplicate_flags
                        ];
                    })->toArray()
                ]);

                // Get existing local customers to avoid duplicates
                $commonCustomerIds = $commonCustomers->pluck('ledger_id')->toArray();
                $existingLocalCustomers = Ledger::where('business_id', $businessId)
                    ->whereIn('common_customer_id', $commonCustomerIds)
                    ->pluck('common_customer_id', 'id')
                    ->toArray();

                // Get unique villages for location filter
                $uniqueVillages = $commonCustomers->pluck('village')
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values();

                // Convert common customers to the format expected by the view
                $customerLedgers = $commonCustomers->map(function ($customer) use ($existingLocalCustomers) {
                    // Check if this common customer already has a local copy
                    $localCustomerId = array_search($customer->ledger_id, $existingLocalCustomers);

                    // Build location string
                    $locationString = $customer->location;
                    if (empty($locationString)) {
                        $locationParts = array_filter([
                            $customer->village,
                            $customer->sub_district,
                            $customer->district
                        ]);
                        $locationString = implode(', ', $locationParts);
                    }

                    // Add landmark if available
                    if (!empty($customer->landmark)) {
                        $locationString .= ' (Near ' . $customer->landmark . ')';
                    }

                    return (object) [
                        // ALWAYS use the common customer ID for the dropdown value
                        'id' => $customer->ledger_id,
                        'name' => $customer->ledger_name,
                        'location' => $locationString,
                        'village' => $customer->village,
                        'contact' => $customer->contact_number ?? '',
                        'district' => $customer->district,
                        'sub_district' => $customer->sub_district,
                        'ledger_type' => $customer->type ?? 'Customer',
                        'landmark' => $customer->landmark,
                        'data_quality_score' => $customer->data_quality_score ?? 0,
                        'is_common' => true, // Always true since these are from common DB
                        'common_customer_id' => $customer->ledger_id,
                        'exists_locally' => (bool)$localCustomerId,
                        'local_customer_id' => $localCustomerId // Store local ID separately
                    ];
                });

                Log::info('Loaded customers from common database', [
                    'business_id' => $businessId,
                    'total_customers' => $customerLedgers->count(),
                    'unique_villages' => $uniqueVillages->count(),
                    'business_locations' => $businessLocations->count(),
                    'existing_local_customers' => count($existingLocalCustomers)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error loading customers from common database: ' . $e->getMessage(), [
                'business_id' => $businessId,
                'trace' => $e->getTraceAsString()
            ]);
            // Fallback to empty collections
            $customerLedgers = collect([]);
            $uniqueVillages = collect([]);
        }

        // Memory cleanup
        gc_collect_cycles();

        return view('admin.inventory.inventory_transactions.create', [
            'products' => $allProducts, // Pass all products (regular + common based on rules)
            'categories' => $categories,
            'supplierLedgers' => $supplierLedgers,
            'customerLedgers' => $customerLedgers, // Now contains common customers from imported locations
            'uniqueVillages' => $uniqueVillages, // Pass villages for location filter
            'transactionType' => $transactionType,
            'userType' => $userType,
            'businessId' => $businessId,
            'disableUnderprice' => $disableUnderprice
        ]);
    }

    // HELPER METHOD: Load regular product images in chunks
    private function loadRegularProductImagesInChunks($regularProducts)
    {
        $productIds = $regularProducts->pluck('id')->toArray();
        $productImages = [];

        if (!empty($productIds)) {
            // Load images in chunks to manage memory
            $imageChunks = array_chunk($productIds, 50); // Process 50 at a time

            foreach ($imageChunks as $chunk) {
                $images = Product::whereIn('id', $chunk)
                    ->select('id', 'image')
                    ->get();

                foreach ($images as $imageRecord) {
                    if ($imageRecord->image) {
                        // Optimize image before base64 encoding
                        $optimizedImage = $this->optimizeImage($imageRecord->image);
                        $productImages[$imageRecord->id] = $optimizedImage;
                    }
                }

                // Clear memory after each chunk
                unset($images);
                gc_collect_cycles();
            }
        }

        // Transform the image data for regular products
        $regularProducts->each(function ($product) use ($productImages) {
            $product->image = $productImages[$product->id] ?? null;
            $product->is_common = false; // Mark as regular product
        });

        // Clear the images array to free memory
        unset($productImages);
        gc_collect_cycles();
    }

    // HELPER METHOD: Load common products for admin (only for purchase)
    private function loadCommonProductsForAdmin($categoryMapping, $currentStaff, $userType)
    {
        $commonProducts = [];

        try {
            $importedCommonCategoryIds = array_keys($categoryMapping);

            // Load common products in chunks (without images first)
            $chunkSize = 100;
            $offset = 0;

            do {
                $commonProductsChunk = DB::connection('mysql_common')
                    ->table('tbl_common_product')
                    ->whereIn('category_id', $importedCommonCategoryIds)
                    ->select('product_id as id', 'product_name as name', 'category_id')
                    ->offset($offset)
                    ->limit($chunkSize)
                    ->get();

                if ($commonProductsChunk->isNotEmpty()) {
                    // Transform common products to match Product model structure
                    foreach ($commonProductsChunk as $product) {
                        $matchingCategory = $categoryMapping[$product->category_id];

                        $newProduct = new \stdClass();
                        $newProduct->id = 'common_' . $product->id; // Prefix to avoid ID conflicts
                        $newProduct->name = $product->name;
                        $newProduct->category_id = $matchingCategory->id;
                        $newProduct->trade_price = 0;
                        $newProduct->current_stock = 0;
                        $newProduct->barcode = null;
                        $newProduct->image = null; // Will be loaded separately
                        $newProduct->unit_id = null;
                        $newProduct->is_common = true; // Mark as common product
                        $newProduct->original_common_id = $product->id; // Store original ID for image loading

                        // Create category relation
                        $newProduct->category = $matchingCategory;

                        // Create empty batches collection
                        $newProduct->batches = collect();

                        $commonProducts[] = $newProduct;
                    }
                }

                $offset += $chunkSize;
                gc_collect_cycles();
            } while ($commonProductsChunk->count() === $chunkSize);

            // Now load images for common products in chunks of 50
            if (!empty($commonProducts)) {
                $this->loadCommonProductImagesInChunks($commonProducts);
            }
        } catch (\Exception $e) {
            Log::error('Error fetching common products: ' . $e->getMessage());
            // Continue without common products
        }

        return $commonProducts;
    }

    // HELPER METHOD: Load common product images in chunks (with debugging)
    private function loadCommonProductImagesInChunks($commonProducts)
    {
        // Get all original common IDs
        $commonIds = [];
        foreach ($commonProducts as $product) {
            $commonIds[] = $product->original_common_id;
        }

        Log::info('Loading common product images', [
            'total_products' => count($commonProducts),
            'common_ids' => $commonIds
        ]);

        $productImages = [];

        if (!empty($commonIds)) {
            // Load images in chunks to manage memory
            $imageChunks = array_chunk($commonIds, 50);

            foreach ($imageChunks as $chunkIndex => $chunk) {
                try {
                    $images = DB::connection('mysql_common')
                        ->table('tbl_common_product')
                        ->whereIn('product_id', $chunk)
                        ->select('product_id', 'image')
                        ->get();

                    Log::info("Processing image chunk {$chunkIndex}", [
                        'chunk_size' => count($chunk),
                        'images_found' => $images->count()
                    ]);

                    foreach ($images as $imageRecord) {
                        if ($imageRecord->image) {
                            // Debug the image data
                            $imageData = $imageRecord->image;
                            $imageSize = strlen($imageData);

                            Log::info('Processing common product image', [
                                'product_id' => $imageRecord->product_id,
                                'image_size' => $imageSize,
                                'first_10_bytes' => bin2hex(substr($imageData, 0, 10)),
                                'is_base64' => $this->isBase64($imageData)
                            ]);

                            // Try to optimize image with better error handling
                            $optimizedImage = $this->optimizeImageWithFallback($imageData, true);
                            if ($optimizedImage) {
                                $productImages[$imageRecord->product_id] = $optimizedImage;
                            } else {
                                Log::warning('Failed to optimize common product image', [
                                    'product_id' => $imageRecord->product_id
                                ]);
                            }
                        }
                    }

                    // Clear memory after each chunk
                    unset($images);
                    gc_collect_cycles();
                } catch (\Exception $e) {
                    Log::error('Error loading common product images chunk: ' . $e->getMessage(), [
                        'chunk_index' => $chunkIndex,
                        'chunk' => $chunk
                    ]);
                }
            }
        }

        Log::info('Common product images loaded', [
            'total_images_processed' => count($productImages)
        ]);

        // Assign images to products
        foreach ($commonProducts as $product) {
            $product->image = $productImages[$product->original_common_id] ?? null;
            // Clean up the temporary property
            unset($product->original_common_id);
        }

        // Clear the images array to free memory
        unset($productImages);
        gc_collect_cycles();
    }

    // HELPER METHOD: Check if string is base64
    private function isBase64($data)
    {
        return base64_encode(base64_decode($data, true)) === $data;
    }

    // IMPROVED: Image optimization with better error handling and fallback
    private function optimizeImageWithFallback($imageData, $isAlreadyBase64 = false)
    {
        try {
            // Handle different image data formats
            $rawImageData = $imageData;

            if ($isAlreadyBase64) {
                // Check if it's actually base64
                if ($this->isBase64($imageData)) {
                    $rawImageData = base64_decode($imageData);
                    Log::info('Decoded base64 image data', [
                        'original_size' => strlen($imageData),
                        'decoded_size' => strlen($rawImageData)
                    ]);
                } else {
                    // It might already be raw binary data
                    Log::info('Image data is not base64, treating as raw binary');
                    $rawImageData = $imageData;
                }
            }

            // Check if the data looks like an image
            $imageInfo = @getimagesizefromstring($rawImageData);
            if (!$imageInfo) {
                Log::warning('Image data does not contain valid image information');
                return null;
            }

            Log::info('Image info detected', [
                'width' => $imageInfo[0],
                'height' => $imageInfo[1],
                'mime' => $imageInfo['mime']
            ]);

            // Create image resource
            $image = @imagecreatefromstring($rawImageData);
            if (!$image) {
                Log::error('Failed to create image resource from string');
                return null;
            }

            // Get original dimensions
            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            Log::info('Image resource created successfully', [
                'width' => $originalWidth,
                'height' => $originalHeight
            ]);

            // Calculate new dimensions (max 200px width/height)
            $maxSize = 200;
            if ($originalWidth > $maxSize || $originalHeight > $maxSize) {
                $ratio = min($maxSize / $originalWidth, $maxSize / $originalHeight);
                $newWidth = (int)($originalWidth * $ratio);
                $newHeight = (int)($originalHeight * $ratio);

                // Create resized image
                $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

                // Handle transparency for PNG/GIF
                if ($imageInfo['mime'] === 'image/png' || $imageInfo['mime'] === 'image/gif') {
                    imagealphablending($resizedImage, false);
                    imagesavealpha($resizedImage, true);
                    $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                    imagefill($resizedImage, 0, 0, $transparent);
                }

                imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

                // Clean up original
                imagedestroy($image);
                $image = $resizedImage;

                Log::info('Image resized', [
                    'new_width' => $newWidth,
                    'new_height' => $newHeight
                ]);
            }

            // Output as JPEG with compression
            ob_start();
            imagejpeg($image, null, 70); // 70% quality
            $optimizedImageData = ob_get_contents();
            ob_end_clean();

            // Clean up
            imagedestroy($image);

            $base64Result = base64_encode($optimizedImageData);

            Log::info('Image optimization completed', [
                'final_size' => strlen($base64Result)
            ]);

            return $base64Result;
        } catch (\Exception $e) {
            Log::error('Image optimization failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    // ALSO UPDATE: Your existing optimizeImage method to use the new fallback
    private function optimizeImage($imageData, $isBase64 = false)
    {
        return $this->optimizeImageWithFallback($imageData, $isBase64);
    }


    public function show(InventoryTransaction $inventoryTransaction)
    {
        $inventoryTransaction->load('lines.product', 'lines.batch');

        // Get current user and business info
        $currentUser = Auth::user();
        $staffName = null;
        $businessId = null;

        // Check if user is business admin
        $businessAdmin = BusinessAdmin::with('user')
            ->where('user_id', $currentUser->id)
            ->first();

        if ($businessAdmin) {
            $businessId = $businessAdmin->business_id;
            $staffName = $businessAdmin->user->name . ' (Admin)';
        } else {
            // Check if user is staff
            $staff = Staff::with('user')
                ->where('user_id', $currentUser->id)
                ->first();

            if ($staff) {
                $businessId = $staff->business_id;
                $staffName = $staff->user->name;
            }
        }

        // Fetch business details
        $business = Business::find($businessId);

        // Get customer info
        $customer = Ledger::select('name', 'contact', 'location')
            ->find($inventoryTransaction->ledger_id);

        // Get damaged products
        $damagedProducts = DamageTransactionLine::whereIn(
            'damage_transaction_id',
            $inventoryTransaction->damageTransactions()->pluck('id')
        )->with('product')->get();

        $damagedTotal = $damagedProducts->sum('total_value');

        // Get returned products from new table
        $returnedProducts = ReturnedProduct::where('inventory_transaction_id', $inventoryTransaction->id)
            ->with('product')
            ->get();

        $returnedTotal = $returnedProducts->sum('total_amount');

        return view('admin.inventory.inventory_transactions.show', compact(
            'inventoryTransaction',
            'business',
            'staffName',
            'customer',
            'damagedProducts',
            'returnedProducts',
            'damagedTotal',
            'returnedTotal'
        ));
    }

    private function formatQuantity($quantity, $unitType)
    {
        return $unitType === 'unit' ? (int)$quantity : number_format($quantity, 3);
    }

    public function edit(InventoryTransaction $inventoryTransaction)
    {
        $inventoryTransaction->load('lines.product', 'lines.batch');

        // Get all necessary data for edit form
        $currentUser = Auth::user();
        $businessId = $currentUser->businessAdmin?->business_id ?? $currentUser->staff?->business_id;

        $products = Product::with(['category', 'unit'])
            ->where('business_id', $businessId)
            ->get();

        $supplierLedgers = Ledger::where('business_id', $businessId)
            ->where('ledger_type', 'Sundry Creditors (Supplier)')
            ->get();

        $customerLedgers = Ledger::where('business_id', $businessId)
            ->where('ledger_type', 'Sundry Debtors (Customer)')
            ->get();

        return view('admin.inventory.inventory_transactions.edit', compact(
            'inventoryTransaction',
            'products',
            'supplierLedgers',
            'customerLedgers'
        ));
    }



    public function destroy(InventoryTransaction $inventoryTransaction)
    {
        // Check authorization - only admin of the associated business can delete
        $user = Auth::user();
        $isAuthorized = false;

        if ($user->roles->pluck('name')->contains('admin')) {
            $businessAdmin = BusinessAdmin::where('user_id', $user->id)->first();
            if ($businessAdmin && $businessAdmin->business_id === $inventoryTransaction->business_id) {
                $isAuthorized = true;
            }
        }

        if (!$isAuthorized) {
            return response()->json([
                'success' => false,
                'message' => 'Only business administrators can delete transactions for their business'
            ], 403);
        }


        // Start a database transaction to ensure atomicity
        DB::beginTransaction();
        try {
            Log::info('Starting deletion of inventory transaction', [
                'transaction_id' => $inventoryTransaction->id,
                'entry_type' => $inventoryTransaction->entry_type,
                'amount' => $inventoryTransaction->grand_total
            ]);

            // 1. Find associated accounting transactions
            $relatedTransactions = Transaction::where('narration', 'like', "%ID {$inventoryTransaction->id}%")
                ->orWhere('narration', 'like', "%Invoice #{$inventoryTransaction->id}%")
                ->orWhere('narration', 'like', "%Invoice #" . $inventoryTransaction->id . "%")
                ->get();

            Log::info('Found related accounting transactions', [
                'count' => $relatedTransactions->count(),
                'transaction_ids' => $relatedTransactions->pluck('id')->toArray()
            ]);

            // Track affected ledgers for balance recalculation
            $affectedLedgerIds = [];

            // 2. Delete accounting transaction lines and collect affected ledgers
            foreach ($relatedTransactions as $transaction) {
                // Collect ledger IDs before deleting
                $ledgerIds = TransactionLine::where('transaction_id', $transaction->id)
                    ->pluck('ledger_id')
                    ->unique()
                    ->toArray();

                $affectedLedgerIds = array_merge($affectedLedgerIds, $ledgerIds);

                // Delete transaction lines
                TransactionLine::where('transaction_id', $transaction->id)->delete();

                Log::info('Deleted transaction lines', [
                    'transaction_id' => $transaction->id,
                    'affected_ledgers' => $ledgerIds
                ]);
            }

            // 3. Delete accounting transactions
            foreach ($relatedTransactions as $transaction) {
                $transaction->delete();

                Log::info('Deleted accounting transaction', [
                    'transaction_id' => $transaction->id
                ]);
            }

            // 4. Reverse stock adjustments
            foreach ($inventoryTransaction->lines as $line) {
                $product = $line->product;
                if (!$product) {
                    Log::warning('Product not found for line', [
                        'line_id' => $line->id,
                        'product_id' => $line->product_id
                    ]);
                    continue;
                }

                // Adjust product stock based on transaction type
                if ($inventoryTransaction->entry_type === 'sale') {
                    // For sales, add the quantity back to stock
                    $product->current_stock += $line->quantity;
                    Log::info('Restored product stock for sale', [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $line->quantity,
                        'new_stock' => $product->current_stock
                    ]);
                } else {
                    // For purchases, subtract the quantity from stock
                    $product->current_stock -= $line->quantity;
                    Log::info('Reduced product stock for purchase', [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $line->quantity,
                        'new_stock' => $product->current_stock
                    ]);
                }
                $product->save();

                // Update batch quantities if applicable
                if ($line->batch_id) {
                    $batch = ProductBatch::find($line->batch_id);
                    if ($batch) {
                        if ($inventoryTransaction->entry_type === 'sale') {
                            // For sales, add the quantity back to the batch
                            $batch->remaining_quantity += $line->quantity;
                        } else {
                            // For purchases, if this is the batch created by this transaction, 
                            // we'll delete it later with cascade. Otherwise, reduce its quantity.
                            $isBatchFromThisTransaction = $batch->batch_date == $inventoryTransaction->transaction_date;

                            if (!$isBatchFromThisTransaction) {
                                $batch->remaining_quantity -= $line->quantity;
                            }
                        }
                        $batch->save();

                        Log::info('Updated batch quantity', [
                            'batch_id' => $batch->id,
                            'product_id' => $product->id,
                            'new_quantity' => $batch->remaining_quantity
                        ]);
                    }
                }
            }

            // 5. Delete transaction contributors (staff)
            $inventoryTransaction->creators()->detach();
            Log::info('Removed transaction contributors', [
                'transaction_id' => $inventoryTransaction->id
            ]);

            // Check for any returned products
            $returnedProducts = ReturnedProduct::where('inventory_transaction_id', $inventoryTransaction->id)->get();
            if ($returnedProducts->isNotEmpty()) {
                Log::info('Found returned products to delete', [
                    'count' => $returnedProducts->count()
                ]);

                // Reverse stock adjustments for returned products
                foreach ($returnedProducts as $returnedProduct) {
                    $product = Product::find($returnedProduct->product_id);
                    if ($product) {
                        // Remove the returned quantity from stock
                        $product->current_stock -= $returnedProduct->quantity;
                        $product->save();

                        // Update batch if applicable
                        if ($returnedProduct->batch_id) {
                            $batch = ProductBatch::find($returnedProduct->batch_id);
                            if ($batch) {
                                $batch->remaining_quantity -= $returnedProduct->quantity;
                                $batch->save();
                            }
                        }

                        Log::info('Reversed returned product stock', [
                            'product_id' => $product->id,
                            'quantity' => $returnedProduct->quantity,
                            'new_stock' => $product->current_stock
                        ]);
                    }
                }

                // Delete returned products
                ReturnedProduct::where('inventory_transaction_id', $inventoryTransaction->id)->delete();
                Log::info('Deleted returned products', [
                    'transaction_id' => $inventoryTransaction->id
                ]);
            }

            // Check for any damage transactions
            $damageTransactions = $inventoryTransaction->damageTransactions;
            if ($damageTransactions->isNotEmpty()) {
                Log::info('Found damage transactions to delete', [
                    'count' => $damageTransactions->count()
                ]);

                foreach ($damageTransactions as $damageTransaction) {
                    // Delete damage transaction lines
                    DamageTransactionLine::where('damage_transaction_id', $damageTransaction->id)->delete();

                    // Delete the damage transaction
                    $damageTransaction->delete();
                }

                Log::info('Deleted damage transactions', [
                    'transaction_id' => $inventoryTransaction->id
                ]);
            }

            // 6. Delete inventory transaction lines
            $inventoryTransaction->lines()->delete();
            Log::info('Deleted inventory transaction lines', [
                'transaction_id' => $inventoryTransaction->id
            ]);

            // 7. Delete the inventory transaction
            $inventoryTransaction->delete();
            Log::info('Deleted inventory transaction', [
                'transaction_id' => $inventoryTransaction->id
            ]);

            // 8. Recalculate affected ledger balances
            $affectedLedgerIds = array_unique($affectedLedgerIds);
            foreach ($affectedLedgerIds as $ledgerId) {
                $ledger = Ledger::find($ledgerId);
                if ($ledger) {
                    $this->recalcLedgerBalance($ledger);
                    Log::info('Recalculated ledger balance', [
                        'ledger_id' => $ledger->id,
                        'ledger_name' => $ledger->name,
                        'new_balance' => $ledger->current_balance
                    ]);
                }
            }

            // Commit the transaction
            DB::commit();

            // Update products cache
            Cache::put('products_last_updated', now()->timestamp);

            return response()->json([
                'success' => true,
                'message' => 'Transaction and all related records deleted successfully'
            ]);
        } catch (\Exception $e) {
            // Roll back the transaction in case of error
            DB::rollBack();

            Log::error('Failed to delete transaction', [
                'transaction_id' => $inventoryTransaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the product stock and manage batches based on the inventory entry.
     *
     * Entry types:
     * - purchase & sales_return: increase stock.
     * - sale & purchase_return: decrease stock.
     *
     * For batch management:
     * - For a purchase, a new batch is created.
     * - For a sale, the sale quantity is deducted from one or more batches in FIFO order.
     * - (For sales_return, we create a new batch to record the returned items.)
     *
     * @param  \App\Models\Product  $product
     * @param  float                $quantity
     * @param  string               $entryType    ('purchase', 'sale', 'purchase_return', 'sales_return')
     * @param  \App\Models\InventoryTransactionLine  $line
     * @return void
     */
    private function updateProductStock(Product $product, float $quantity, string $entryType, $line)
    {
        // Update the overall product current stock
        if ($entryType === 'purchase' || $entryType === 'sales_return') {
            $product->current_stock += $quantity;
        } elseif ($entryType === 'sale' || $entryType === 'purchase_return') {
            $product->current_stock -= $quantity;
        }
        $product->save();

        // Handle batch-specific updates
        if ($entryType === 'purchase') {
            // Update product stock
            $product->current_stock += $quantity;
            $product->save();

            // Create new batch
            $batch = ProductBatch::create([
                'product_id' => $product->id,
                'batch_number' => 'B-' . time(),
                'dealer_price' => $line->dealer_price,
                'trade_price' => $line->unit_price,
                'remaining_quantity' => $quantity,
                'batch_date' => now()->toDateString(),
                'expiry_date' => null,
                'is_opening_batch' => false
            ]);

            // Create transaction line
            InventoryTransactionLine::create([
                'inventory_transaction_id' => $line->inventory_transaction_id,
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'quantity' => $quantity,
                'unit_price' => $line->unit_price,
                'dealer_price' => $line->dealer_price,
                'line_discount' => $line->line_discount,
                'line_total' => $line->line_total
            ]);
        } elseif ($entryType === 'sale') {
            $remainingQtyToDeduct = $quantity;

            // Get available batches in FIFO order
            $batches = ProductBatch::where('product_id', $product->id)
                ->where('remaining_quantity', '>', 0)
                ->orderBy('batch_date', 'asc')
                ->get();

            foreach ($batches as $batch) {
                if ($remainingQtyToDeduct <= 0) break;

                $qtyToDeduct = min($batch->remaining_quantity, $remainingQtyToDeduct);
                $batch->remaining_quantity -= $qtyToDeduct;
                $batch->save();

                // Create batch transaction record
                InventoryTransactionLine::create([
                    'inventory_transaction_id' => $line->inventory_transaction_id,
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'quantity' => $qtyToDeduct,
                    'unit_price' => $line->unit_price,
                    'dealer_price' => $batch->dealer_price,
                    'line_total' => $qtyToDeduct * $line->unit_price
                ]);

                $remainingQtyToDeduct -= $qtyToDeduct;
            }

            if ($remainingQtyToDeduct > 0) {
                throw new \Exception("Insufficient stock in batches for product: {$product->name}");
            }
        } elseif ($entryType === 'sales_return') {
            // Create new batch for returned items
            $batch = ProductBatch::create([
                'product_id' => $product->id,
                'batch_number' => 'SR-' . time(),
                'dealer_price' => $line->unit_price,
                'trade_price' => $line->unit_price,
                'remaining_quantity' => $quantity,
                'batch_date' => now()->toDateString(),
                'expiry_date' => null,
                'is_opening_batch' => false,
            ]);

            $line->batch_id = $batch->id;
            $line->save();
        }
    }


    // ------------------------------------Collection Starts here-----------------------------------
    public function collectPayment(Request $request, InventoryTransaction $inventoryTransaction)
    {
        // Check if transaction is credit type
        if ($inventoryTransaction->payment_method !== 'credit') {
            return response()->json([
                'success' => false,
                'message' => 'This transaction is not a credit transaction.'
            ], 400);
        }

        // If this is a GET request, return category totals for the modal
        if ($request->isMethod('get')) {
            return $this->getCategoryTotals($inventoryTransaction);
        }

        // For POST requests, validate the collection data
        try {
            $validated = $request->validate([
                'categories' => 'required|array',
                'categories.*.category_id' => 'required|integer|exists:product_categories,id',
                'categories.*.amount' => [
                    'required',
                    'numeric',
                    'min:0.01',
                ],
                'total_amount' => [
                    'required',
                    'numeric',
                    'min:0.01',
                    'max:' . $inventoryTransaction->grand_total
                ]
            ], [
                'categories.required' => 'Please select at least one category.',
                'total_amount.required' => 'Please enter a collection amount.',
                'total_amount.numeric' => 'The collection amount must be a number.',
                'total_amount.min' => 'The collection amount must be at least 0.01.',
                'total_amount.max' => 'The collection amount cannot exceed the remaining balance of ৳' . number_format($inventoryTransaction->grand_total, 2)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        }

        // ============= PRE-FLIGHT VALIDATION - CRITICAL STEP =============
        try {
            $this->validateCollectionRequirements($request, $inventoryTransaction);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }

        // ============= ATOMIC TRANSACTION PROCESSING =============
        DB::beginTransaction();
        try {
            // Step 1: Lock the transaction row for update
            try {
                $lockedTransaction = InventoryTransaction::where('id', $inventoryTransaction->id)
                    ->lockForUpdate()
                    ->first();

                if (!$lockedTransaction) {
                    throw new \Exception("Transaction record not found or locked by another process");
                }

                // Re-validate transaction state after lock (prevent race conditions)
                if ($lockedTransaction->payment_method !== 'credit') {
                    throw new \Exception("Transaction payment method changed during processing");
                }

                if ($lockedTransaction->grand_total <= 0) {
                    throw new \Exception("Transaction balance changed during processing - no amount to collect");
                }

                if ($request->total_amount > $lockedTransaction->grand_total) {
                    throw new \Exception("Collection amount exceeds current balance after lock");
                }
            } catch (\Exception $e) {
                throw new \Exception("Failed to lock transaction: " . $e->getMessage());
            }

            // Step 2: Get and validate collector staff and ledger
            try {
                $collectorStaff = Staff::where('user_id', Auth::id())->first();
                if (!$collectorStaff) {
                    throw new \Exception('Collector staff record not found');
                }

                $collectorLedger = DB::table('staff_ledgers')
                    ->where('staff_id', $collectorStaff->id)
                    ->first();

                if (!$collectorLedger) {
                    throw new \Exception('Collector staff ledger not found');
                }

                // Verify collector ledger still exists
                $collectorLedgerModel = Ledger::find($collectorLedger->ledger_id);
                if (!$collectorLedgerModel) {
                    throw new \Exception('Collector ledger account no longer exists');
                }
            } catch (\Exception $e) {
                throw new \Exception("Collector validation failed: " . $e->getMessage());
            }

            // Step 3: NEW - Build contributor-based execution plan
            $contributorExecutionPlan = [];
            $totalCategoryAmounts = 0;

            try {
                foreach ($request->categories as $index => $categoryData) {
                    $categoryId = $categoryData['category_id'];
                    $categoryCollectionAmount = $categoryData['amount'];

                    // Skip zero amounts
                    if ($categoryCollectionAmount <= 0) {
                        continue;
                    }

                    // Get category details
                    $category = DB::table('product_categories')->where('id', $categoryId)->first();
                    if (!$category) {
                        throw new \Exception("Category ID {$categoryId} not found");
                    }

                    // NEW: Get all contributors for this category in this specific transaction
                    $contributors = DB::table('inventory_transaction_contributors as itc')
                        ->join('products as p', 'itc.product_id', '=', 'p.id')
                        ->join('staff as s', 'itc.staff_id', '=', 's.id')
                        ->join('staff_ledgers as sl', 's.id', '=', 'sl.staff_id')
                        ->join('ledgers as l', 'sl.ledger_id', '=', 'l.id')
                        ->join('users as u', 's.user_id', '=', 'u.id')
                        ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
                        ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                        ->where('itc.transaction_id', $lockedTransaction->id)
                        ->where('p.category_id', $categoryId)
                        ->where('r.name', 'staff') // Ensure staff has 'staff' role
                        ->where('mhr.model_type', 'App\\Models\\User') // Specify model type
                        ->select(
                            'itc.staff_id',
                            'u.name as staff_name', // Changed from s.name to u.name
                            's.user_id as staff_user_id',
                            'sl.ledger_id',
                            'l.name as ledger_name',
                            DB::raw('SUM(itc.contributed_amount) as total_contribution')
                        )
                        ->groupBy('itc.staff_id', 'u.name', 's.user_id', 'sl.ledger_id', 'l.name') // Changed s.name to u.name
                        ->get();

                    if ($contributors->isEmpty()) {
                        throw new \Exception("No contributors found for category '{$category->name}' in this transaction");
                    }

                    // Calculate total contributions for this category
                    $categoryTotalContribution = $contributors->sum('total_contribution');
                    if ($categoryTotalContribution <= 0) {
                        throw new \Exception("Invalid total contribution amount for category '{$category->name}'");
                    }

                    // Calculate proportional amounts for each contributor
                    foreach ($contributors as $contributor) {
                        // CRITICAL: Ensure contributor is NOT the same as collector
                        if ($contributor->staff_id === $collectorStaff->id) {
                            throw new \Exception("Contributor staff '{$contributor->staff_name}' cannot be the same person as the collector for category '{$category->name}'. Please assign a different staff member to this category.");
                        }

                        // CRITICAL: Ensure contributor ledger is NOT the same as collector ledger
                        if ($contributor->ledger_id === $collectorLedger->ledger_id) {
                            throw new \Exception("Contributor staff '{$contributor->staff_name}' has the same ledger as collector. This will cause accounting errors.");
                        }

                        // Calculate proportional collection amount
                        $proportionalAmount = ($contributor->total_contribution / $categoryTotalContribution) * $categoryCollectionAmount;

                        // Add to execution plan
                        $contributorExecutionPlan[] = [
                            'category_id' => $categoryId,
                            'category_name' => $category->name,
                            'staff_id' => $contributor->staff_id,
                            'staff_name' => $contributor->staff_name,
                            'staff_user_id' => $contributor->staff_user_id,
                            'ledger_id' => $contributor->ledger_id,
                            'ledger_name' => $contributor->ledger_name,
                            'actual_contribution' => $contributor->total_contribution,
                            'category_collection_amount' => $categoryCollectionAmount,
                            'category_total_contribution' => $categoryTotalContribution,
                            'proportional_amount' => $proportionalAmount
                        ];
                    }

                    $totalCategoryAmounts += $categoryCollectionAmount;
                }

                // Validation checks remain the same
                if ($totalCategoryAmounts > $request->total_amount) {
                    throw new \Exception("Category amounts total (৳{$totalCategoryAmounts}) cannot exceed collection amount (৳{$request->total_amount})");
                }

                if ($totalCategoryAmounts <= 0) {
                    throw new \Exception("Total category amounts must be greater than 0");
                }

                if (empty($contributorExecutionPlan)) {
                    throw new \Exception("No valid contributors found for collection");
                }
            } catch (\Exception $e) {
                throw new \Exception("Contributor validation failed: " . $e->getMessage());
            }

            // Step 4: Log execution plan for debugging
            Log::info('Collection Execution Plan - Contributor Based', [
                'transaction_id' => $lockedTransaction->id,
                'collector' => [
                    'staff_id' => $collectorStaff->id,
                    'name' => $collectorStaff->name,
                    'user_id' => $collectorStaff->user_id,
                    'ledger_id' => $collectorLedger->ledger_id
                ],
                'contributors' => $contributorExecutionPlan,
                'total_amount' => $request->total_amount,
                'category_amounts_total' => $totalCategoryAmounts
            ]);

            // Step 5: Store original values for rollback logging
            $originalPaidAmount = $lockedTransaction->paid_amount ?? 0;
            $originalGrandTotal = $lockedTransaction->grand_total;

            // Step 6: Update inventory transaction (UNCHANGED)
            try {
                $lockedTransaction->paid_amount = $originalPaidAmount + $request->total_amount;
                $lockedTransaction->grand_total = $originalGrandTotal - $request->total_amount;

                // Handle floating point precision
                if ($lockedTransaction->grand_total <= 0.01) {
                    $lockedTransaction->grand_total = 0;
                }

                $saveResult = $lockedTransaction->save();
                if (!$saveResult) {
                    throw new \Exception("Database save operation failed");
                }
            } catch (\Exception $e) {
                throw new \Exception("Failed to update inventory transaction: " . $e->getMessage());
            }

            // Step 7: Create journal transaction (UNCHANGED)
            try {
                $journalTransaction = Transaction::create([
                    'business_id' => $lockedTransaction->business_id,
                    'transaction_type' => 'Journal',
                    'transaction_date' => now(),
                    'amount' => $request->total_amount,
                    'narration' => "Collection for Invoice #{$lockedTransaction->id} - Amount: ৳{$request->total_amount}"
                ]);

                if (!$journalTransaction || !$journalTransaction->id) {
                    throw new \Exception("Journal transaction creation returned invalid result");
                }
            } catch (\Exception $e) {
                throw new \Exception("Failed to create journal transaction: " . $e->getMessage());
            }

            // Step 8: Create ALL transaction lines atomically
            $createdTransactionLines = [];

            try {
                // 8a: NEW - Create contributor-based transaction lines (DEBIT contributors with their proportional amounts)
                foreach ($contributorExecutionPlan as $contributorPlan) {
                    try {
                        $contributorTransactionLine = TransactionLine::create([
                            'transaction_id' => $journalTransaction->id,
                            'ledger_id' => $contributorPlan['ledger_id'],
                            'debit_amount' => $contributorPlan['proportional_amount'],
                            'credit_amount' => 0,
                            'narration' => "Collection for category '{$contributorPlan['category_name']}' - Staff: {$contributorPlan['staff_name']} - Contribution: ৳{$contributorPlan['actual_contribution']} - Collected: ৳{$contributorPlan['proportional_amount']}"
                        ]);

                        if (!$contributorTransactionLine || !$contributorTransactionLine->id) {
                            throw new \Exception("Contributor transaction line creation failed");
                        }

                        $createdTransactionLines[] = [
                            'type' => 'contributor',
                            'line_id' => $contributorTransactionLine->id,
                            'category_id' => $contributorPlan['category_id'],
                            'staff_id' => $contributorPlan['staff_id'],
                            'ledger_id' => $contributorPlan['ledger_id'],
                            'amount' => $contributorPlan['proportional_amount'],
                            'actual_contribution' => $contributorPlan['actual_contribution']
                        ];
                    } catch (\Exception $e) {
                        throw new \Exception("Failed to create contributor transaction line for staff '{$contributorPlan['staff_name']}': " . $e->getMessage());
                    }
                }

                // 8b: Create collector transaction line (UNCHANGED - DEBIT collector with FULL collection amount)
                try {
                    $collectorTransactionLine = TransactionLine::create([
                        'transaction_id' => $journalTransaction->id,
                        'ledger_id' => $collectorLedger->ledger_id,
                        'debit_amount' => $request->total_amount,
                        'credit_amount' => 0,
                        'narration' => "Collection received - Total amount: ৳{$request->total_amount}"
                    ]);

                    if (!$collectorTransactionLine || !$collectorTransactionLine->id) {
                        throw new \Exception("Collector transaction line creation failed");
                    }

                    $createdTransactionLines[] = [
                        'type' => 'collector',
                        'line_id' => $collectorTransactionLine->id,
                        'staff_id' => $collectorStaff->id,
                        'ledger_id' => $collectorLedger->ledger_id,
                        'amount' => $request->total_amount
                    ];
                } catch (\Exception $e) {
                    throw new \Exception("Failed to create collector transaction line: " . $e->getMessage());
                }

                // 8c: Create customer transaction line (UNCHANGED - CREDIT customer with FULL collection amount)
                try {
                    $customerTransactionLine = TransactionLine::create([
                        'transaction_id' => $journalTransaction->id,
                        'ledger_id' => $lockedTransaction->ledger_id,
                        'debit_amount' => 0,
                        'credit_amount' => $request->total_amount,
                        'narration' => "Payment received from customer - Amount: ৳{$request->total_amount}"
                    ]);

                    if (!$customerTransactionLine || !$customerTransactionLine->id) {
                        throw new \Exception("Customer transaction line creation failed");
                    }

                    $createdTransactionLines[] = [
                        'type' => 'customer',
                        'line_id' => $customerTransactionLine->id,
                        'ledger_id' => $lockedTransaction->ledger_id,
                        'amount' => $request->total_amount
                    ];
                } catch (\Exception $e) {
                    throw new \Exception("Failed to create customer transaction line: " . $e->getMessage());
                }

                // 8d: If there's a difference between contributor amounts and collection amount,
                // create a balancing entry for the collector
                $totalContributorAmounts = collect($contributorExecutionPlan)->sum('proportional_amount');
                $difference = $request->total_amount - $totalContributorAmounts;
                if (abs($difference) > 0.01) { // Only if difference is significant
                    try {
                        $balancingTransactionLine = TransactionLine::create([
                            'transaction_id' => $journalTransaction->id,
                            'ledger_id' => $collectorLedger->ledger_id,
                            'debit_amount' => $difference > 0 ? $difference : 0,
                            'credit_amount' => $difference < 0 ? abs($difference) : 0,
                            'narration' => $difference > 0
                                ? "Additional collection amount (difference): ৳{$difference}"
                                : "Adjustment for over-allocation: ৳" . abs($difference)
                        ]);

                        if (!$balancingTransactionLine || !$balancingTransactionLine->id) {
                            throw new \Exception("Balancing transaction line creation failed");
                        }

                        $createdTransactionLines[] = [
                            'type' => 'balancing',
                            'line_id' => $balancingTransactionLine->id,
                            'staff_id' => $collectorStaff->id,
                            'ledger_id' => $collectorLedger->ledger_id,
                            'amount' => abs($difference),
                            'direction' => $difference > 0 ? 'debit' : 'credit'
                        ];
                    } catch (\Exception $e) {
                        throw new \Exception("Failed to create balancing transaction line: " . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                throw new \Exception("Transaction line creation failed: " . $e->getMessage());
            }

            // Step 9: Verify all transaction lines were created
            try {
                $expectedLinesCount = count($contributorExecutionPlan) + 2; // Contributor lines + Collector line + Customer line
                if (abs($request->total_amount - collect($contributorExecutionPlan)->sum('proportional_amount')) > 0.01) {
                    $expectedLinesCount++; // Add balancing line if needed
                }

                $actualLinesCount = DB::table('transaction_lines')
                    ->where('transaction_id', $journalTransaction->id)
                    ->count();

                if ($actualLinesCount !== $expectedLinesCount) {
                    throw new \Exception("Transaction lines count mismatch - Expected: {$expectedLinesCount}, Actual: {$actualLinesCount}");
                }

                // Verify we created the right number of lines
                if (count($createdTransactionLines) !== $expectedLinesCount) {
                    throw new \Exception("Created lines tracking mismatch - Expected: {$expectedLinesCount}, Tracked: " . count($createdTransactionLines));
                }
            } catch (\Exception $e) {
                throw new \Exception("Transaction line verification failed: " . $e->getMessage());
            }

            // Step 10: Update all ledger balances
            $updatedLedgers = [];
            try {
                // Update contributor ledger balances
                foreach ($contributorExecutionPlan as $contributorPlan) {
                    try {
                        $contributorLedger = Ledger::find($contributorPlan['ledger_id']);
                        if (!$contributorLedger) {
                            throw new \Exception("Contributor ledger {$contributorPlan['ledger_id']} not found during balance update");
                        }

                        $this->recalcLedgerBalance($contributorLedger);
                        $updatedLedgers[] = $contributorPlan['ledger_id'];
                    } catch (\Exception $e) {
                        throw new \Exception("Failed to update contributor ledger balance (ID: {$contributorPlan['ledger_id']}): " . $e->getMessage());
                    }
                }

                // Update collector ledger balance
                try {
                    $collectorLedgerModel = Ledger::find($collectorLedger->ledger_id);
                    if (!$collectorLedgerModel) {
                        throw new \Exception("Collector ledger not found during balance update");
                    }

                    $this->recalcLedgerBalance($collectorLedgerModel);
                    $updatedLedgers[] = $collectorLedger->ledger_id;
                } catch (\Exception $e) {
                    throw new \Exception("Failed to update collector ledger balance: " . $e->getMessage());
                }

                // Update customer ledger balance
                try {
                    $customerLedger = Ledger::find($lockedTransaction->ledger_id);
                    if (!$customerLedger) {
                        throw new \Exception("Customer ledger not found during balance update");
                    }

                    $this->recalcLedgerBalance($customerLedger);
                    $updatedLedgers[] = $lockedTransaction->ledger_id;
                } catch (\Exception $e) {
                    throw new \Exception("Failed to update customer ledger balance: " . $e->getMessage());
                }
            } catch (\Exception $e) {
                throw new \Exception("Ledger balance update failed: " . $e->getMessage());
            }

            // Step 11: Final verification
            try {
                // Verify inventory transaction was updated correctly
                $verifyTransaction = InventoryTransaction::find($lockedTransaction->id);
                $expectedNewTotal = $originalGrandTotal - $request->total_amount;
                if ($expectedNewTotal <= 0.01) {
                    $expectedNewTotal = 0;
                }

                if (abs($verifyTransaction->grand_total - $expectedNewTotal) > 0.01) {
                    throw new \Exception("Inventory transaction total verification failed - Expected: {$expectedNewTotal}, Actual: {$verifyTransaction->grand_total}");
                }

                $expectedPaidAmount = $originalPaidAmount + $request->total_amount;
                if (abs($verifyTransaction->paid_amount - $expectedPaidAmount) > 0.01) {
                    throw new \Exception("Inventory transaction paid amount verification failed - Expected: {$expectedPaidAmount}, Actual: {$verifyTransaction->paid_amount}");
                }

                // Verify all transaction lines still exist
                $finalLinesCount = DB::table('transaction_lines')
                    ->where('transaction_id', $journalTransaction->id)
                    ->count();

                $expectedLinesCount = count($contributorExecutionPlan) + 2; // Contributor lines + Collector + Customer
                if (abs($request->total_amount - collect($contributorExecutionPlan)->sum('proportional_amount')) > 0.01) {
                    $expectedLinesCount++; // Add balancing line if needed
                }

                if ($finalLinesCount !== $expectedLinesCount) {
                    throw new \Exception("Final transaction lines verification failed - Expected: {$expectedLinesCount}, Actual: {$finalLinesCount}");
                }
            } catch (\Exception $e) {
                throw new \Exception("Final verification failed: " . $e->getMessage());
            }

            // All operations successful - commit transaction
            DB::commit();

            Log::info('Collection Payment Completed Successfully - Contributor Based', [
                'transaction_id' => $lockedTransaction->id,
                'journal_id' => $journalTransaction->id,
                'collector' => [
                    'staff_id' => $collectorStaff->id,
                    'name' => $collectorStaff->name,
                    'user_id' => $collectorStaff->user_id,
                    'ledger_id' => $collectorLedger->ledger_id
                ],
                'collection_details' => [
                    'total_amount' => $request->total_amount,
                    'original_grand_total' => $originalGrandTotal,
                    'new_grand_total' => $lockedTransaction->grand_total,
                    'original_paid_amount' => $originalPaidAmount,
                    'new_paid_amount' => $lockedTransaction->paid_amount,
                    'is_fully_paid' => $lockedTransaction->grand_total <= 0
                ],
                'accounting_summary' => [
                    'journal_id' => $journalTransaction->id,
                    'contributor_lines_created' => count($contributorExecutionPlan),
                    'total_lines_created' => count($createdTransactionLines),
                    'ledgers_updated' => $updatedLedgers
                ],
                'transaction_lines' => $createdTransactionLines,
                'contributor_breakdown' => $contributorExecutionPlan
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Collection of ৳' . number_format($request->total_amount, 2) . ' recorded successfully',
                'data' => [
                    'new_grand_total' => $lockedTransaction->grand_total,
                    'new_paid_amount' => $lockedTransaction->paid_amount,
                    'is_fully_paid' => $lockedTransaction->grand_total <= 0,
                    'journal_id' => $journalTransaction->id,
                    'contributors_processed' => count($contributorExecutionPlan),
                    'transaction_lines_created' => count($createdTransactionLines)
                ]
            ]);
        } catch (\Exception $e) {
            // Rollback all database changes
            DB::rollBack();

            Log::error('Collection Payment Failed - Complete Rollback Executed - Contributor Based', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'transaction_id' => $inventoryTransaction->id,
                'user_id' => Auth::id(),
                'request_data' => [
                    'categories' => $request->categories,
                    'total_amount' => $request->total_amount
                ],
                'rollback_status' => 'All changes have been rolled back - transaction is atomic'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Collection failed: ' . $e->getMessage()
            ], 500);
        }
    }


    private function validateCollectionRequirements(Request $request, InventoryTransaction $inventoryTransaction)
    {
        $errors = [];

        // 1. Validate collector staff and ledger
        $collectorStaff = Staff::where('user_id', Auth::id())->first();
        if (!$collectorStaff) {
            throw new \Exception('Collector staff record not found. Please contact administrator.');
        }

        // Validate collector has 'dsr' role using Spatie Permission
        $hasCollectorRole = $collectorStaff->user->hasRole('dsr');
        if (!$hasCollectorRole) {
            throw new \Exception('You must have the "dsr" role to collect payments. Please contact administrator.');
        }

        $collectorLedger = DB::table('staff_ledgers')
            ->where('staff_id', $collectorStaff->id)
            ->first();

        if (!$collectorLedger) {
            throw new \Exception('Collector staff ledger not found. Please contact administrator to set up your ledger account.');
        }

        // Validate collector's ledger exists in ledgers table
        $collectorLedgerExists = DB::table('ledgers')
            ->where('id', $collectorLedger->ledger_id)
            ->exists();

        if (!$collectorLedgerExists) {
            throw new \Exception('Collector ledger account is invalid. Please contact administrator to fix your ledger setup.');
        }

        // Validate collector ledger is accessible
        $collectorLedgerModel = Ledger::find($collectorLedger->ledger_id);
        if (!$collectorLedgerModel) {
            throw new \Exception('Collector ledger account cannot be accessed. Please contact administrator.');
        }

        // 2-5. [Previous validation steps remain the same]
        // ... (customer ledger, transaction state, payment method, business context validation)

        // 6. NEW - Validate ALL selected categories have contributors in this transaction
        foreach ($request->categories as $index => $categoryData) {
            $categoryId = $categoryData['category_id'];
            $amount = $categoryData['amount'];

            // Skip zero amounts
            if ($amount <= 0) {
                continue;
            }

            // Check if category exists
            $category = DB::table('product_categories')->where('id', $categoryId)->first();
            if (!$category) {
                $errors[] = "Category ID {$categoryId} not found.";
                continue;
            }

            // NEW - Check if category has contributors with 'staff' role in this specific transaction
            $hasContributors = DB::table('inventory_transaction_contributors as itc')
                ->join('products as p', 'itc.product_id', '=', 'p.id')
                ->join('staff as s', 'itc.staff_id', '=', 's.id')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
                ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                ->where('itc.transaction_id', $inventoryTransaction->id)
                ->where('p.category_id', $categoryId)
                ->where('r.name', 'staff')
                ->where('mhr.model_type', 'App\\Models\\User')
                ->exists();

            if (!$hasContributors) {
                $errors[] = "Category '{$category->name}' has no contributors with 'staff' role in this transaction. Cannot process collection.";
                continue;
            }

            // NEW - Validate that contributors are not the same as collector
            $contributorStaffIds = DB::table('inventory_transaction_contributors as itc')
                ->join('products as p', 'itc.product_id', '=', 'p.id')
                ->join('staff as s', 'itc.staff_id', '=', 's.id')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
                ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                ->where('itc.transaction_id', $inventoryTransaction->id)
                ->where('p.category_id', $categoryId)
                ->where('r.name', 'staff')
                ->where('mhr.model_type', 'App\\Models\\User')
                ->pluck('itc.staff_id')
                ->unique();

            if ($contributorStaffIds->contains($collectorStaff->id)) {
                $errors[] = "Category '{$category->name}' has contributions from the same person who is collecting. This will cause accounting errors. Please assign a different staff member to contribute to this category.";
                continue;
            }

            // NEW - Validate that contributor ledgers are different from collector ledger
            $contributorLedgerIds = DB::table('inventory_transaction_contributors as itc')
                ->join('products as p', 'itc.product_id', '=', 'p.id')
                ->join('staff as s', 'itc.staff_id', '=', 's.id')
                ->join('staff_ledgers as sl', 's.id', '=', 'sl.staff_id')
                ->join('users as u', 's.user_id', '=', 'u.id')
                ->join('model_has_roles as mhr', 'u.id', '=', 'mhr.model_id')
                ->join('roles as r', 'mhr.role_id', '=', 'r.id')
                ->where('itc.transaction_id', $inventoryTransaction->id)
                ->where('p.category_id', $categoryId)
                ->where('r.name', 'staff')
                ->where('mhr.model_type', 'App\\Models\\User')
                ->pluck('sl.ledger_id')
                ->unique();

            if ($contributorLedgerIds->contains($collectorLedger->ledger_id)) {
                $errors[] = "Category '{$category->name}' has contributors with the same ledger account as the collector. This will cause accounting errors. Please contact administrator.";
                continue;
            }

            // NEW - Validate all contributor ledgers exist and are accessible
            foreach ($contributorLedgerIds as $ledgerId) {
                $ledgerExists = DB::table('ledgers')->where('id', $ledgerId)->exists();
                if (!$ledgerExists) {
                    $errors[] = "Contributor ledger account (ID: {$ledgerId}) for category '{$category->name}' is invalid. Please contact administrator.";
                    continue;
                }

                $ledgerModel = Ledger::find($ledgerId);
                if (!$ledgerModel) {
                    $errors[] = "Contributor ledger account (ID: {$ledgerId}) for category '{$category->name}' cannot be accessed. Please contact administrator.";
                    continue;
                }
            }
        }

        // 7. Validate that we have at least one valid category with amount > 0
        $validCategoriesCount = 0;
        foreach ($request->categories as $categoryData) {
            if ($categoryData['amount'] > 0) {
                $validCategoriesCount++;
            }
        }

        if ($validCategoriesCount === 0) {
            throw new \Exception('No valid categories with amounts greater than 0 found for collection.');
        }

        // If any errors found, throw exception with detailed message
        if (!empty($errors)) {
            $errorMessage = "Collection cannot proceed due to the following issues:\n\n" . implode("\n", $errors);
            throw new \Exception($errorMessage);
        }

        Log::info('Collection pre-flight validation passed - Contributor Based', [
            'transaction_id' => $inventoryTransaction->id,
            'customer_ledger_id' => $inventoryTransaction->ledger_id,
            'collector_staff_id' => $collectorStaff->id,
            'collector_ledger_id' => $collectorLedger->ledger_id,
            'categories_count' => $validCategoriesCount,
            'total_amount' => $request->total_amount,
            'validation_method' => 'contributor_based'
        ]);
    }




    /**
     * Get category totals for a transaction
     */
    private function getCategoryTotals(InventoryTransaction $inventoryTransaction)
    {
        // Add logging to track the process
        Log::info('Getting Category Totals', [
            'transaction_id' => $inventoryTransaction->id,
            'transaction_date' => $inventoryTransaction->transaction_date
        ]);

        // Use direct database query to get transaction lines with product and category data
        $transactionLines = DB::table('inventory_transaction_lines as itl')
            ->leftJoin('products as p', 'itl.product_id', '=', 'p.id')
            ->leftJoin('product_categories as pc', 'p.category_id', '=', 'pc.id')
            ->where('itl.inventory_transaction_id', $inventoryTransaction->id)
            ->select(
                'itl.id',
                'itl.product_id',
                'itl.quantity',
                'itl.unit_price',
                'itl.line_total',
                'p.name as product_name',
                'p.category_id',
                'pc.id as category_id',
                'pc.name as category_name'
            )
            ->get();

        Log::info('Transaction Lines Count', [
            'transaction_id' => $inventoryTransaction->id,
            'lines_count' => $transactionLines->count()
        ]);

        // Group products by category
        $categoryTotals = [];
        foreach ($transactionLines as $line) {
            Log::info('Processing Line', [
                'line_id' => $line->id,
                'product_id' => $line->product_id,
                'category_id' => $line->category_id,
                'product_name' => $line->product_name,
                'category_name' => $line->category_name
            ]);

            if (!$line->category_id) {
                Log::warning('Skipping line - missing category', [
                    'line_id' => $line->id,
                    'product_id' => $line->product_id
                ]);
                continue;
            }

            $categoryId = $line->category_id;
            $categoryName = $line->category_name;

            if (!isset($categoryTotals[$categoryId])) {
                $categoryTotals[$categoryId] = [
                    'id' => $categoryId,
                    'name' => $categoryName,
                    'total' => 0,
                    'damage_total' => 0,
                    'return_total' => 0,
                    'net_total' => 0,
                    'products' => []
                ];
            }

            $categoryTotals[$categoryId]['total'] += $line->line_total;
            $categoryTotals[$categoryId]['products'][] = [
                'id' => $line->product_id,
                'name' => $line->product_name,
                'quantity' => $line->quantity,
                'price' => $line->unit_price,
                'total' => $line->line_total
            ];
        }

        // Get damaged products for this transaction using direct query
        $damagedProducts = DB::table('damage_transaction_lines as dtl')
            ->join('damage_transactions as dt', 'dtl.damage_transaction_id', '=', 'dt.id')
            ->join('products as p', 'dtl.product_id', '=', 'p.id')
            ->leftJoin('product_categories as pc', 'p.category_id', '=', 'pc.id')
            ->where('dt.inventory_transaction_id', $inventoryTransaction->id)
            ->select(
                'dtl.id',
                'dtl.product_id',
                'dtl.quantity',
                'dtl.unit_price',
                'dtl.total_value',
                'p.category_id',
                'pc.id as category_id'
            )
            ->get();

        Log::info('Damaged Products Count', [
            'transaction_id' => $inventoryTransaction->id,
            'damaged_count' => $damagedProducts->count()
        ]);

        // Get returned products for this transaction using direct query
        $returnedProducts = DB::table('returned_products as rp')
            ->join('products as p', 'rp.product_id', '=', 'p.id')
            ->leftJoin('product_categories as pc', 'p.category_id', '=', 'pc.id')
            ->where('rp.inventory_transaction_id', $inventoryTransaction->id)
            ->select(
                'rp.id',
                'rp.product_id',
                'rp.quantity',
                'rp.unit_price',
                'rp.total_amount',
                'p.category_id',
                'pc.id as category_id'
            )
            ->get();

        Log::info('Returned Products Count', [
            'transaction_id' => $inventoryTransaction->id,
            'returned_count' => $returnedProducts->count()
        ]);

        // Subtract damaged products from category totals
        foreach ($damagedProducts as $damaged) {
            if (!$damaged->category_id) {
                Log::warning('Skipping damaged product - missing category', [
                    'damaged_id' => $damaged->id,
                    'product_id' => $damaged->product_id
                ]);
                continue;
            }

            $categoryId = $damaged->category_id;
            if (isset($categoryTotals[$categoryId])) {
                $categoryTotals[$categoryId]['damage_total'] += $damaged->total_value;
            }
        }

        // Subtract returned products from category totals
        foreach ($returnedProducts as $returned) {
            if (!$returned->category_id) {
                Log::warning('Skipping returned product - missing category', [
                    'returned_id' => $returned->id,
                    'product_id' => $returned->product_id
                ]);
                continue;
            }

            $categoryId = $returned->category_id;
            if (isset($categoryTotals[$categoryId])) {
                $categoryTotals[$categoryId]['return_total'] += $returned->total_amount;
            }
        }

        // Calculate net total for each category
        foreach ($categoryTotals as $categoryId => $category) {
            $categoryTotals[$categoryId]['net_total'] = max(0, $category['total'] - $category['damage_total'] - $category['return_total']);
        }

        Log::info('Category Totals Result', [
            'transaction_id' => $inventoryTransaction->id,
            'categories_count' => count($categoryTotals),
            'categories' => array_keys($categoryTotals)
        ]);

        return response()->json([
            'success' => true,
            'transaction' => [
                'id' => $inventoryTransaction->id,
                'customer_name' => $inventoryTransaction->ledger->name ?? 'Unknown',
                'grand_total' => $inventoryTransaction->grand_total,
                'transaction_date' => $inventoryTransaction->transaction_date
            ],
            'categories' => array_values($categoryTotals)
        ]);
    }


    /**
     * Delete collection for a transaction and restore the grand total
     *
     * @param InventoryTransaction $inventoryTransaction
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCollection(InventoryTransaction $inventoryTransaction)
    {
        // Check if transaction has any paid amount
        if ($inventoryTransaction->paid_amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'This transaction has no collection to delete.'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Lock the transaction row for update
            $lockedTransaction = InventoryTransaction::where('id', $inventoryTransaction->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedTransaction) {
                throw new \Exception("Transaction is currently being processed by another request");
            }

            // Store the paid amount before resetting
            $paidAmount = $inventoryTransaction->paid_amount;

            // Log the operation
            Log::info('Deleting collection for transaction', [
                'transaction_id' => $inventoryTransaction->id,
                'paid_amount' => $paidAmount,
                'current_grand_total' => $inventoryTransaction->grand_total,
                'user_id' => Auth::id()
            ]);

            // Find related collection journal transactions - using the exact narration pattern
            $relatedTransactions = Transaction::where('narration', "Category-wise Collection for Invoice #{$inventoryTransaction->id}")
                ->get();

            if ($relatedTransactions->isEmpty()) {
                Log::warning('No collection transactions found for deletion', [
                    'transaction_id' => $inventoryTransaction->id
                ]);

                // Try a more flexible search if exact match fails
                $relatedTransactions = Transaction::where('narration', 'like', "%Collection for Invoice #{$inventoryTransaction->id}%")
                    ->get();

                if ($relatedTransactions->isEmpty()) {
                    throw new \Exception("No collection transactions found to delete");
                }
            }

            Log::info('Found collection transactions to delete', [
                'transaction_id' => $inventoryTransaction->id,
                'collection_transactions' => $relatedTransactions->pluck('id')->toArray(),
                'count' => $relatedTransactions->count()
            ]);

            // Track affected ledgers for balance recalculation
            $affectedLedgerIds = [];

            // Delete transaction lines and collect affected ledgers
            foreach ($relatedTransactions as $transaction) {
                // Collect ledger IDs before deleting
                $ledgerIds = TransactionLine::where('transaction_id', $transaction->id)
                    ->pluck('ledger_id')
                    ->unique()
                    ->toArray();

                $affectedLedgerIds = array_merge($affectedLedgerIds, $ledgerIds);

                // Log transaction lines before deletion for audit purposes
                $transactionLines = TransactionLine::where('transaction_id', $transaction->id)->get();
                Log::info('Transaction lines to be deleted', [
                    'transaction_id' => $transaction->id,
                    'lines' => $transactionLines->map(function ($line) {
                        return [
                            'id' => $line->id,
                            'ledger_id' => $line->ledger_id,
                            'debit' => $line->debit_amount,
                            'credit' => $line->credit_amount,
                            'narration' => $line->narration
                        ];
                    })
                ]);

                // Delete transaction lines
                TransactionLine::where('transaction_id', $transaction->id)->delete();

                // Delete the transaction
                $transaction->delete();

                Log::info('Deleted collection transaction', [
                    'transaction_id' => $transaction->id,
                    'affected_ledgers' => $ledgerIds
                ]);
            }

            // Update the inventory transaction
            $inventoryTransaction->grand_total += $paidAmount;
            $inventoryTransaction->paid_amount = 0;
            $inventoryTransaction->save();

            Log::info('Updated inventory transaction after collection deletion', [
                'transaction_id' => $inventoryTransaction->id,
                'new_grand_total' => $inventoryTransaction->grand_total,
                'new_paid_amount' => $inventoryTransaction->paid_amount
            ]);

            // Recalculate affected ledger balances
            $affectedLedgerIds = array_unique($affectedLedgerIds);
            foreach ($affectedLedgerIds as $ledgerId) {
                $ledger = Ledger::find($ledgerId);
                if ($ledger) {
                    $this->recalcLedgerBalance($ledger);
                    Log::info('Recalculated ledger balance', [
                        'ledger_id' => $ledger->id,
                        'ledger_name' => $ledger->name,
                        'new_balance' => $ledger->current_balance
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Collection of ৳' . number_format($paidAmount, 2) . ' has been deleted and added back to the grand total.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Delete collection failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'transaction_id' => $inventoryTransaction->id,
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete collection: ' . $e->getMessage()
            ], 500);
        }
    }




    private function recalcLedgerBalance(Ledger $ledger): void
    {
        $drLedgers = [
            'Bank Accounts',
            'Cash-in-Hand',
            'Expenses',
            'Fixed Assets',
            'Stock-in-Hand',
            'Investments',
            'Loans & Advances (Asset)',
            'Purchase Accounts',
            'Sundry Debtors (Customer)'
        ];

        $currentBalance = $ledger->opening_balance ?? 0;
        $transactionLines = TransactionLine::where('ledger_id', $ledger->id)->get();

        foreach ($transactionLines as $line) {
            if (in_array($ledger->ledger_type, $drLedgers)) {
                $currentBalance += $line->debit_amount;
                $currentBalance -= $line->credit_amount;
            } else {
                $currentBalance -= $line->debit_amount;
                $currentBalance += $line->credit_amount;
            }
        }

        $ledger->current_balance = $currentBalance;
        $ledger->save();
    }


    private function calculateCategoryAmounts($transactionLines, $collectionAmount)
    {
        $totalAmount = $transactionLines->sum('line_total');
        $categoryTotals = [];

        foreach ($transactionLines as $line) {
            $categoryId = $line->product->category_id;
            if (!isset($categoryTotals[$categoryId])) {
                $categoryTotals[$categoryId] = 0;
            }
            // Calculate proportional amount for this category
            $proportion = $line->line_total / $totalAmount;
            $categoryTotals[$categoryId] += $collectionAmount * $proportion;
        }

        return $categoryTotals;
    }



    // Return transection starts here 
    public function getProducts(InventoryTransaction $inventoryTransaction)
    {
        try {
            // Check if transaction is a sale
            if ($inventoryTransaction->entry_type !== 'sale') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only sale transactions can have returns'
                ], 400);
            }

            // Since transaction lines are updated after each return,
            // we can directly use the current quantities in transaction lines
            $transactionLines = $inventoryTransaction->lines()
                ->with(['product', 'batch'])
                ->where('quantity', '>', 0) // Only get lines with remaining quantity
                ->get();

            $availableProducts = [];

            foreach ($transactionLines as $line) {
                // Since the transaction line quantity is already the remaining quantity,
                // we can directly use it
                if ($line->quantity > 0) {
                    $availableProducts[] = [
                        'id' => $line->product_id,
                        'batch_id' => $line->batch_id,
                        'batch_number' => $line->batch ? $line->batch->batch_number : null,
                        'name' => $line->product->name,
                        'quantity' => (float)$line->quantity,
                        'unit_price' => (float)$line->unit_price,
                        'line_total' => (float)$line->quantity * (float)$line->unit_price
                    ];
                }
            }

            Log::info('Return products - simplified approach', [
                'transaction_id' => $inventoryTransaction->id,
                'lines_with_quantity' => $transactionLines->count(),
                'available_products' => $availableProducts
            ]);

            return response()->json([
                'success' => true,
                'products' => array_values($availableProducts)
            ]);
        } catch (\Exception $e) {
            Log::error('Product fetch error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'transaction_id' => $inventoryTransaction->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process product returns for a transaction
     *
     * @param Request $request
     * @param InventoryTransaction $inventoryTransaction
     * @return \Illuminate\Http\JsonResponse
     */
    public function returnProducts(Request $request, InventoryTransaction $inventoryTransaction)
    {
        // Check if transaction is a sale
        if ($inventoryTransaction->entry_type !== 'sale') {
            return response()->json([
                'success' => false,
                'message' => 'Only sale transactions can have returns'
            ], 400);
        }

        // Validate request
        try {
            $validated = $request->validate([
                'products' => 'required|array|min:1',
                'products.*.product_id' => 'required|integer|exists:products,id',
                'products.*.batch_id' => 'required|integer|exists:product_batches,id',
                'products.*.quantity' => 'required|numeric|min:0.01',
                'products.*.unit_price' => 'required|numeric|min:0'
            ], [
                'products.required' => 'Please select at least one product to return',
                'products.min' => 'Please select at least one product to return',
                'products.*.quantity.min' => 'Return quantity must be greater than zero',
                'products.*.unit_price.min' => 'Unit price must be greater than zero'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Lock the transaction row for update
            $lockedTransaction = InventoryTransaction::where('id', $inventoryTransaction->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedTransaction) {
                throw new \Exception("Transaction is currently being processed by another request");
            }

            $totalReturnAmount = 0;
            $newReturns = []; // Track only new returns for stock updates
            $returnedProducts = []; // Track products for response
            $currentReturnProductIds = []; // Track IDs of products returned in this operation
            $currentReturnAmounts = []; // Track amounts for current return operation

            // UPDATED VALIDATION: Use the same logic as getProducts method
            foreach ($validated['products'] as $returnProduct) {
                // Get current transaction line quantity (which is already the remaining quantity)
                $currentTransactionLine = InventoryTransactionLine::where([
                    'inventory_transaction_id' => $inventoryTransaction->id,
                    'product_id' => $returnProduct['product_id'],
                    'batch_id' => $returnProduct['batch_id']
                ])->first();

                if (!$currentTransactionLine) {
                    throw new \Exception("Product was not found in the original transaction");
                }

                // Since transaction line quantity is already the remaining quantity after previous returns,
                // we can directly compare against it
                $availableQty = $currentTransactionLine->quantity;

                if ($returnProduct['quantity'] > $availableQty) {
                    $product = Product::find($returnProduct['product_id']);
                    throw new \Exception("Cannot return more than available quantity for " . ($product ? $product->name : 'Unknown Product'));
                }
            }

            // Process returns
            foreach ($validated['products'] as $returnProduct) {
                // Get product details for logging
                $product = Product::findOrFail($returnProduct['product_id']);
                $returnAmount = $returnProduct['quantity'] * $returnProduct['unit_price'];

                // Get original transaction line to calculate line discount
                $originalLine = InventoryTransactionLine::where([
                    'inventory_transaction_id' => $inventoryTransaction->id,
                    'product_id' => $returnProduct['product_id'],
                    'batch_id' => $returnProduct['batch_id']
                ])->first();

                // Calculate proportional line discount for the returned quantity
                $returnLineDiscount = 0;
                if ($originalLine && $originalLine->quantity > 0 && isset($originalLine->line_discount) && $originalLine->line_discount != 0) {
                    // Calculate discount per unit based on ORIGINAL quantity, not current quantity
                    $originalQuantity = $originalLine->quantity + ReturnedProduct::where([
                        'inventory_transaction_id' => $inventoryTransaction->id,
                        'product_id' => $returnProduct['product_id'],
                        'batch_id' => $returnProduct['batch_id']
                    ])->sum('quantity');

                    $discountPerUnit = $originalLine->line_discount / $originalQuantity;
                    $returnLineDiscount = $discountPerUnit * $returnProduct['quantity'];

                    Log::info("Calculated line discount for return", [
                        'product_id' => $returnProduct['product_id'],
                        'original_discount' => $originalLine->line_discount,
                        'original_quantity' => $originalQuantity,
                        'current_line_quantity' => $originalLine->quantity,
                        'return_quantity' => $returnProduct['quantity'],
                        'discount_per_unit' => $discountPerUnit,
                        'return_line_discount' => $returnLineDiscount
                    ]);
                }

                // Check if product was already returned before
                $existingReturn = ReturnedProduct::where([
                    'inventory_transaction_id' => $inventoryTransaction->id,
                    'product_id' => $returnProduct['product_id'],
                    'batch_id' => $returnProduct['batch_id']
                ])->first();

                if ($existingReturn) {
                    // Update existing return record
                    $existingReturn->quantity += $returnProduct['quantity'];
                    $existingReturn->total_amount += $returnAmount;

                    // Add line discount if the column exists
                    if (Schema::hasColumn('returned_products', 'line_discount')) {
                        $existingReturn->line_discount = ($existingReturn->line_discount ?? 0) + $returnLineDiscount;
                    }

                    // Ensure return_date is set if not already
                    if (!$existingReturn->return_date) {
                        $existingReturn->return_date = $inventoryTransaction->transaction_date;
                    }

                    if (!$existingReturn->save()) {
                        throw new \Exception("Failed to update return record for product: " . $product->name);
                    }

                    // Add to tracking arrays for stock updates
                    $newReturns[] = [
                        'product_id' => $returnProduct['product_id'],
                        'batch_id' => $returnProduct['batch_id'],
                        'quantity' => $returnProduct['quantity'],
                        'amount' => $returnAmount
                    ];

                    // Store the ID of the updated return record
                    $currentReturnProductIds[] = $existingReturn->id;

                    // Store the current return amount (not the cumulative amount)
                    $currentReturnAmounts[$existingReturn->id] = [
                        'quantity' => $returnProduct['quantity'],
                        'amount' => $returnAmount,
                        'line_discount' => $returnLineDiscount
                    ];

                    $returnedProducts[] = [
                        'id' => $existingReturn->id,
                        'product_name' => $product->name,
                        'quantity' => $returnProduct['quantity'],
                        'amount' => $returnAmount
                    ];

                    $totalReturnAmount += $returnAmount;

                    Log::info("Product return updated", [
                        'product_id' => $returnProduct['product_id'],
                        'product_name' => $product->name,
                        'quantity' => $returnProduct['quantity'],
                        'new_total_quantity' => $existingReturn->quantity,
                        'amount' => $returnAmount,
                        'new_total_amount' => $existingReturn->total_amount,
                        'line_discount' => $returnLineDiscount,
                        'transaction_id' => $inventoryTransaction->id,
                        'return_record_id' => $existingReturn->id
                    ]);
                } else {
                    // Create new return record
                    $returnData = [
                        'business_id' => $inventoryTransaction->business_id,
                        'inventory_transaction_id' => $inventoryTransaction->id,
                        'product_id' => $returnProduct['product_id'],
                        'batch_id' => $returnProduct['batch_id'],
                        'quantity' => $returnProduct['quantity'],
                        'unit_price' => $returnProduct['unit_price'],
                        'total_amount' => $returnAmount,
                        'created_by' => Auth::id(),
                        'return_date' => $inventoryTransaction->transaction_date,
                    ];

                    // Add line discount if the column exists
                    if (Schema::hasColumn('returned_products', 'line_discount')) {
                        $returnData['line_discount'] = $returnLineDiscount;
                    }

                    $returnRecord = ReturnedProduct::create($returnData);

                    if (!$returnRecord || !$returnRecord->id) {
                        throw new \Exception("Failed to create return record for product: " . $product->name);
                    }

                    // Add to new returns for stock updates
                    $newReturns[] = [
                        'product_id' => $returnProduct['product_id'],
                        'batch_id' => $returnProduct['batch_id'],
                        'quantity' => $returnProduct['quantity'],
                        'amount' => $returnAmount
                    ];

                    // Store the ID of the newly created return record
                    $currentReturnProductIds[] = $returnRecord->id;

                    // Store the current return amount
                    $currentReturnAmounts[$returnRecord->id] = [
                        'quantity' => $returnProduct['quantity'],
                        'amount' => $returnAmount,
                        'line_discount' => $returnLineDiscount
                    ];

                    $returnedProducts[] = [
                        'id' => $returnRecord->id,
                        'product_name' => $product->name,
                        'quantity' => $returnProduct['quantity'],
                        'amount' => $returnAmount
                    ];

                    $totalReturnAmount += $returnAmount;

                    Log::info("Product return created", [
                        'product_id' => $returnProduct['product_id'],
                        'product_name' => $product->name,
                        'quantity' => $returnProduct['quantity'],
                        'amount' => $returnAmount,
                        'line_discount' => $returnLineDiscount,
                        'transaction_id' => $inventoryTransaction->id,
                        'return_record_id' => $returnRecord->id
                    ]);
                }
            }

            // Only update stock for new returns
            foreach ($newReturns as $newReturn) {
                // Update product and batch stock
                $product = Product::findOrFail($newReturn['product_id']);
                $product->current_stock += $newReturn['quantity'];

                if (!$product->save()) {
                    throw new \Exception("Failed to update stock for product: " . $product->name);
                }

                $batch = ProductBatch::findOrFail($newReturn['batch_id']);
                $batch->remaining_quantity += $newReturn['quantity'];

                if (!$batch->save()) {
                    throw new \Exception("Failed to update batch stock for product: " . $product->name);
                }

                Log::info("Stock updated for return", [
                    'product_id' => $newReturn['product_id'],
                    'product_name' => $product->name,
                    'quantity' => $newReturn['quantity'],
                    'new_stock' => $product->current_stock
                ]);
            }

            // Update inventory transaction lines
            $returnsByProductBatch = [];
            foreach ($newReturns as $return) {
                $key = $return['product_id'] . '-' . $return['batch_id'];
                if (!isset($returnsByProductBatch[$key])) {
                    $returnsByProductBatch[$key] = [
                        'product_id' => $return['product_id'],
                        'batch_id' => $return['batch_id'],
                        'quantity' => 0,
                        'amount' => 0
                    ];
                }
                $returnsByProductBatch[$key]['quantity'] += $return['quantity'];
                $returnsByProductBatch[$key]['amount'] += $return['amount'];
            }

            // Update inventory transaction lines and calculate total reduction
            $totalReductionAmount = 0;
            foreach ($returnsByProductBatch as $key => $returnData) {
                // Find the original transaction line
                $transactionLine = InventoryTransactionLine::where([
                    'inventory_transaction_id' => $inventoryTransaction->id,
                    'product_id' => $returnData['product_id'],
                    'batch_id' => $returnData['batch_id']
                ])->first();

                if ($transactionLine) {
                    // Calculate new values
                    $newQuantity = $transactionLine->quantity - $returnData['quantity'];
                    $newLineTotal = $newQuantity * $transactionLine->unit_price;

                    // Calculate the reduction amount for this line
                    $reductionAmount = $transactionLine->line_total - $newLineTotal;
                    $totalReductionAmount += $reductionAmount;

                    // Calculate proportional line discount adjustment
                    $originalLineDiscount = $transactionLine->line_discount ?? 0;
                    $newLineDiscount = 0;

                    if ($originalLineDiscount != 0) {
                        // Calculate discount per unit
                        $discountPerUnit = ($transactionLine->quantity > 0)
                            ? ($originalLineDiscount / $transactionLine->quantity)
                            : 0;

                        // Calculate new line discount for remaining quantity
                        $newLineDiscount = $discountPerUnit * $newQuantity;

                        Log::info("Adjusted line discount for returned product", [
                            'transaction_line_id' => $transactionLine->id,
                            'product_id' => $returnData['product_id'],
                            'original_discount' => $originalLineDiscount,
                            'new_discount' => $newLineDiscount,
                            'discount_per_unit' => $discountPerUnit,
                            'remaining_quantity' => $newQuantity
                        ]);
                    }

                    // Update the transaction line
                    $transactionLine->quantity = $newQuantity;
                    $transactionLine->line_total = $newLineTotal;
                    $transactionLine->line_discount = $newLineDiscount;

                    if (!$transactionLine->save()) {
                        throw new \Exception("Failed to update transaction line for product ID: " . $returnData['product_id']);
                    }

                    Log::info("Updated inventory transaction line for return", [
                        'transaction_line_id' => $transactionLine->id,
                        'product_id' => $returnData['product_id'],
                        'original_quantity' => $transactionLine->quantity + $returnData['quantity'],
                        'returned_quantity' => $returnData['quantity'],
                        'new_quantity' => $newQuantity,
                        'new_line_total' => $newLineTotal,
                        'reduction_amount' => $reductionAmount,
                        'original_discount' => $originalLineDiscount,
                        'new_discount' => $newLineDiscount
                    ]);
                }
            }

            // Update the transaction totals
            try {
                $inventoryTransaction->subtotal -= $totalReductionAmount;
                $inventoryTransaction->grand_total -= $totalReductionAmount;

                if (!$inventoryTransaction->save()) {
                    throw new \Exception("Failed to update transaction totals");
                }

                Log::info("Transaction updated successfully", [
                    'transaction_id' => $inventoryTransaction->id,
                    'new_grand_total' => $inventoryTransaction->grand_total
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to update transaction", [
                    'transaction_id' => $inventoryTransaction->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            // Only create accounting entries if there are new returns
            if ($totalReturnAmount > 0) {
                $this->createReturnAccountingEntries($inventoryTransaction, $totalReturnAmount, $currentReturnProductIds, $currentReturnAmounts);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($returnedProducts) . ' product(s) returned successfully for a total of ৳' . number_format($totalReturnAmount, 2),
                'returned_products' => $returnedProducts,
                'total_amount' => $totalReturnAmount
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Return processing failed - Complete rollback executed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'transaction_id' => $inventoryTransaction->id,
                'user_id' => Auth::id(),
                'step' => 'return_processing'
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process return: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create accounting entries for product returns - CONTRIBUTOR-BASED VERSION
     *
     * @param InventoryTransaction $inventoryTransaction
     * @param float $totalReturnAmount
     * @param array $currentReturnProductIds IDs of products being returned in the current operation
     * @param array $currentReturnAmounts Amounts for products being returned in the current operation
     * @return void
     * @throws \Exception
     */
    private function createReturnAccountingEntries(InventoryTransaction $inventoryTransaction, float $totalReturnAmount, array $currentReturnProductIds = [], array $currentReturnAmounts = [])
    {
        try {
            // Step 1: Create journal transaction with validation
            $journalTransaction = Transaction::create([
                'business_id' => $inventoryTransaction->business_id,
                'transaction_type' => 'Journal',
                'transaction_date' => $inventoryTransaction->transaction_date,
                'amount' => $totalReturnAmount,
                'narration' => "Return for Invoice #{$inventoryTransaction->id}",
                'created_at' => $inventoryTransaction->transaction_date
            ]);

            if (!$journalTransaction || !$journalTransaction->id) {
                throw new \Exception("Failed to create journal transaction for return accounting");
            }

            Log::info('Journal transaction created for return', [
                'journal_id' => $journalTransaction->id,
                'amount' => $totalReturnAmount,
                'original_transaction_id' => $inventoryTransaction->id
            ]);

            // Step 2: Verify customer ledger exists
            $customerLedger = Ledger::find($inventoryTransaction->ledger_id);
            if (!$customerLedger) {
                throw new \Exception("Customer ledger not found for transaction. Ledger ID: {$inventoryTransaction->ledger_id}");
            }

            // Step 3: Credit Customer Ledger with validation
            $customerTransactionLine = TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $inventoryTransaction->ledger_id,
                'debit_amount' => 0,
                'credit_amount' => $totalReturnAmount,
                'narration' => 'Sales Return',
                'created_at' => $inventoryTransaction->transaction_date
            ]);

            if (!$customerTransactionLine || !$customerTransactionLine->id) {
                throw new \Exception("Failed to create customer ledger entry for return");
            }

            Log::info('Customer ledger credited for return', [
                'ledger_id' => $inventoryTransaction->ledger_id,
                'amount' => $totalReturnAmount,
                'transaction_line_id' => $customerTransactionLine->id
            ]);

            // Step 4: Get and validate returned products
            $currentReturnProducts = ReturnedProduct::whereIn('id', $currentReturnProductIds)
                ->with('product')
                ->get();

            if ($currentReturnProducts->count() !== count($currentReturnProductIds)) {
                throw new \Exception("Some returned products could not be found in database");
            }

            // Step 5: NEW - Query actual contributors for returned products instead of category-based lookup
            $staffReturnAmounts = [];
            $staffDiscountAdjustments = [];
            $processedContributors = [];

            foreach ($currentReturnProducts as $returnedProduct) {
                // Validate product exists
                $product = $returnedProduct->product;
                if (!$product) {
                    throw new \Exception("Product not found for returned product ID: {$returnedProduct->id}");
                }

                // Validate return amounts exist
                if (!isset($currentReturnAmounts[$returnedProduct->id])) {
                    throw new \Exception("Return amount data missing for returned product ID: {$returnedProduct->id}");
                }

                $currentReturnQuantity = $currentReturnAmounts[$returnedProduct->id]['quantity'];
                $currentReturnAmount = $currentReturnAmounts[$returnedProduct->id]['amount'];

                // Validate amounts are positive
                if ($currentReturnAmount <= 0) {
                    throw new \Exception("Invalid return amount for product '{$product->name}': {$currentReturnAmount}");
                }

                // NEW: Find actual contributor for this specific product in this transaction
                $contributor = DB::table('inventory_transaction_contributors')
                    ->where('transaction_id', $inventoryTransaction->id)
                    ->where('product_id', $returnedProduct->product_id)
                    ->first();

                if (!$contributor) {
                    throw new \Exception("No contributor found for returned product '{$product->name}' in transaction #{$inventoryTransaction->id}");
                }

                // Verify contributor staff exists
                $contributorStaff = Staff::with('user')->find($contributor->staff_id);
                if (!$contributorStaff) {
                    throw new \Exception("Contributor staff not found for product '{$product->name}'. Staff ID: {$contributor->staff_id}");
                }

                // Get contributor's staff ledger
                $contributorLedger = DB::table('staff_ledgers')
                    ->where('staff_id', $contributor->staff_id)
                    ->first();

                if (!$contributorLedger) {
                    throw new \Exception("Staff ledger not found for contributor '{$contributorStaff->user->name}' (Staff ID: {$contributor->staff_id})");
                }

                // Verify ledger exists
                $ledgerExists = Ledger::find($contributorLedger->ledger_id);
                if (!$ledgerExists) {
                    throw new \Exception("Staff ledger account does not exist for contributor '{$contributorStaff->user->name}'. Ledger ID: {$contributorLedger->ledger_id}");
                }

                // Initialize staff return amount if not exists
                if (!isset($staffReturnAmounts[$contributor->staff_id])) {
                    $staffReturnAmounts[$contributor->staff_id] = [
                        'staff_id' => $contributor->staff_id,
                        'staff_name' => $contributorStaff->user->name,
                        'ledger_id' => $contributorLedger->ledger_id,
                        'total_amount' => 0,
                        'products' => []
                    ];
                }

                // Add return amount to staff total
                $staffReturnAmounts[$contributor->staff_id]['total_amount'] += $currentReturnAmount;
                $staffReturnAmounts[$contributor->staff_id]['products'][] = [
                    'product_id' => $returnedProduct->product_id,
                    'product_name' => $product->name,
                    'return_quantity' => $currentReturnQuantity,
                    'return_amount' => $currentReturnAmount
                ];

                // Handle line discount with validation
                $lineDiscount = 0;

                // First check if the column exists in the table
                if (Schema::hasColumn('returned_products', 'line_discount')) {
                    $lineDiscount = $returnedProduct->line_discount ?? 0;
                }
                // If column doesn't exist or value is null, get from tracking array
                else if (isset($currentReturnAmounts[$returnedProduct->id]['line_discount'])) {
                    $lineDiscount = $currentReturnAmounts[$returnedProduct->id]['line_discount'];
                }
                // If neither exists, try to calculate from the original transaction line
                else {
                    $originalLine = InventoryTransactionLine::where([
                        'inventory_transaction_id' => $inventoryTransaction->id,
                        'product_id' => $returnedProduct->product_id,
                        'batch_id' => $returnedProduct->batch_id
                    ])->first();

                    if ($originalLine && isset($originalLine->line_discount) && $originalLine->line_discount != 0 && $originalLine->quantity > 0) {
                        $originalDiscountPerUnit = $originalLine->line_discount / $originalLine->quantity;
                        $lineDiscount = $originalDiscountPerUnit * $currentReturnQuantity;
                    }
                }

                // Process line discount if exists
                if ($lineDiscount != 0) {
                    // Initialize staff discount adjustments if not exists
                    if (!isset($staffDiscountAdjustments[$contributor->staff_id])) {
                        $staffDiscountAdjustments[$contributor->staff_id] = 0;
                    }

                    // Add to staff discount total
                    $staffDiscountAdjustments[$contributor->staff_id] += $lineDiscount;

                    Log::info('Added discount adjustment to staff total', [
                        'staff_id' => $contributor->staff_id,
                        'staff_name' => $contributorStaff->user->name,
                        'product_id' => $returnedProduct->product_id,
                        'product_name' => $product->name,
                        'returned_quantity' => $currentReturnQuantity,
                        'line_discount' => $lineDiscount,
                        'staff_total_adjustment' => $staffDiscountAdjustments[$contributor->staff_id]
                    ]);
                }

                $processedContributors[] = [
                    'staff_id' => $contributor->staff_id,
                    'staff_name' => $contributorStaff->user->name,
                    'product_id' => $returnedProduct->product_id,
                    'product_name' => $product->name,
                    'contributed_quantity' => $contributor->contributed_quantity,
                    'contributed_amount' => $contributor->contributed_amount,
                    'return_quantity' => $currentReturnQuantity,
                    'return_amount' => $currentReturnAmount
                ];
            }

            // Step 6: Validate we have staff to process
            if (empty($staffReturnAmounts)) {
                throw new \Exception("No valid staff contributors found for return processing");
            }

            // Step 7: Create entries for each contributing staff with full validation
            $createdStaffEntries = [];
            $totalStaffAmount = 0;

            foreach ($staffReturnAmounts as $staffId => $staffData) {
                $amount = $staffData['total_amount'];
                $staffName = $staffData['staff_name'];
                $ledgerId = $staffData['ledger_id'];

                // Create staff debit entry for return deduction
                $staffTransactionLine = TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $ledgerId,
                    'debit_amount' => $amount,
                    'credit_amount' => 0,
                    'narration' => "Sales Return Deduction - " . $staffName,
                    'created_at' => $inventoryTransaction->transaction_date
                ]);

                if (!$staffTransactionLine || !$staffTransactionLine->id) {
                    throw new \Exception("Failed to create staff ledger entry for staff: {$staffName}");
                }

                $createdStaffEntries[] = [
                    'staff_id' => $staffId,
                    'ledger_id' => $ledgerId,
                    'amount' => $amount,
                    'transaction_line_id' => $staffTransactionLine->id
                ];

                $totalStaffAmount += $amount;

                Log::info('Created staff return deduction entry', [
                    'staff_id' => $staffId,
                    'staff_name' => $staffName,
                    'ledger_id' => $ledgerId,
                    'amount' => $amount,
                    'transaction_line_id' => $staffTransactionLine->id,
                    'products_count' => count($staffData['products'])
                ]);

                // Process line discount adjustment if exists for this staff
                if (isset($staffDiscountAdjustments[$staffId]) && $staffDiscountAdjustments[$staffId] != 0) {
                    $discountAdjustment = $staffDiscountAdjustments[$staffId];

                    $discountTransactionLine = TransactionLine::create([
                        'transaction_id' => $journalTransaction->id,
                        'ledger_id' => $ledgerId,
                        // If discount is negative (price was increased), create a debit entry
                        // If discount is positive (price was decreased), create a credit entry
                        'debit_amount' => $discountAdjustment < 0 ? abs($discountAdjustment) : 0,
                        'credit_amount' => $discountAdjustment < 0 ? 0 : abs($discountAdjustment),
                        'narration' => "Price Adjustment Reversal - " . $staffName . " - Invoice #" . $inventoryTransaction->id,
                        'created_at' => $inventoryTransaction->transaction_date
                    ]);

                    if (!$discountTransactionLine || !$discountTransactionLine->id) {
                        throw new \Exception("Failed to create discount adjustment entry for staff: {$staffName}");
                    }

                    Log::info('Created price adjustment reversal entry', [
                        'staff_id' => $staffId,
                        'staff_name' => $staffName,
                        'transaction_id' => $inventoryTransaction->id,
                        'discount_adjustment' => $discountAdjustment,
                        'debit' => $discountAdjustment < 0 ? abs($discountAdjustment) : 0,
                        'credit' => $discountAdjustment < 0 ? 0 : abs($discountAdjustment),
                        'transaction_line_id' => $discountTransactionLine->id
                    ]);
                }
            }

            // Step 8: Validate total amounts match
            if (abs($totalStaffAmount - $totalReturnAmount) > 0.01) {
                throw new \Exception("Staff amounts ({$totalStaffAmount}) do not match total return amount ({$totalReturnAmount})");
            }

            // Step 9: Update ledger balances with error handling
            try {
                // Update customer ledger balance
                $this->recalcLedgerBalance($customerLedger);

                // Update all staff ledger balances
                foreach ($createdStaffEntries as $entry) {
                    $staffLedger = Ledger::find($entry['ledger_id']);
                    if ($staffLedger) {
                        $this->recalcLedgerBalance($staffLedger);
                    } else {
                        throw new \Exception("Staff ledger not found during balance update. Ledger ID: {$entry['ledger_id']}");
                    }
                }

                Log::info('All ledger balances updated successfully for return', [
                    'customer_ledger_id' => $customerLedger->id,
                    'staff_ledgers_updated' => count($createdStaffEntries),
                    'journal_id' => $journalTransaction->id
                ]);
            } catch (\Exception $e) {
                throw new \Exception("Failed to update ledger balances: " . $e->getMessage());
            }

            Log::info('Return accounting entries completed successfully - CONTRIBUTOR-BASED', [
                'transaction_id' => $inventoryTransaction->id,
                'journal_id' => $journalTransaction->id,
                'total_amount' => $totalReturnAmount,
                'staff_contributors_processed' => count($staffReturnAmounts),
                'staff_entries_created' => count($createdStaffEntries),
                'customer_ledger_id' => $customerLedger->id,
                'processed_contributors' => $processedContributors
            ]);
        } catch (\Exception $e) {
            Log::error('Return accounting entry creation failed - triggering rollback', [
                'transaction_id' => $inventoryTransaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'total_return_amount' => $totalReturnAmount,
                'return_product_ids' => $currentReturnProductIds
            ]);

            // Re-throw the exception to trigger the main transaction rollback
            // This ensures that ALL changes (return records, stock updates, transaction updates, AND accounting entries) are rolled back
            throw new \Exception("Accounting entry creation failed: " . $e->getMessage());
        }
    }




    public function deleteReturn($id)
    {
        try {
            DB::beginTransaction();

            $inventoryTransaction = InventoryTransaction::findOrFail($id);

            // Get all returned products for this transaction BEFORE deletion
            $returnedProducts = $inventoryTransaction->returned_products()->with('product')->get();

            if ($returnedProducts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No returns found for this transaction.'
                ], 400);
            }

            $totalReturnedAmount = $returnedProducts->sum('total_amount');

            // Log the operation
            Log::info('Deleting return for transaction', [
                'transaction_id' => $inventoryTransaction->id,
                'returned_amount' => $totalReturnedAmount,
                'current_grand_total' => $inventoryTransaction->grand_total,
                'user_id' => Auth::id()
            ]);

            // Step 1: Delete related accounting transactions
            $relatedTransactions = Transaction::where('narration', "Return for Invoice #{$inventoryTransaction->id}")
                ->get();

            foreach ($relatedTransactions as $transaction) {
                // Delete transaction entries first
                $transaction->entries()->delete();
                // Delete the transaction itself
                $transaction->delete();

                Log::info('Deleted return transaction', [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount
                ]);
            }

            // Step 2: Group returned products by product and batch for inventory updates
            $returnsByProductBatch = [];
            foreach ($returnedProducts as $returnedProduct) {
                $key = $returnedProduct->product_id . '-' . $returnedProduct->batch_id;
                if (!isset($returnsByProductBatch[$key])) {
                    $returnsByProductBatch[$key] = [
                        'product_id' => $returnedProduct->product_id,
                        'batch_id' => $returnedProduct->batch_id,
                        'quantity' => 0,
                        'amount' => 0
                    ];
                }
                $returnsByProductBatch[$key]['quantity'] += $returnedProduct->quantity;
                $returnsByProductBatch[$key]['amount'] += $returnedProduct->total_amount;
            }

            // Step 3: Reverse inventory updates (subtract returned quantities from stock)
            foreach ($returnsByProductBatch as $returnData) {
                // Update product stock (subtract the returned quantity)
                $product = Product::findOrFail($returnData['product_id']);
                $product->current_stock -= $returnData['quantity'];
                $product->save();

                // Update batch stock (subtract the returned quantity)
                $batch = ProductBatch::findOrFail($returnData['batch_id']);
                $batch->remaining_quantity -= $returnData['quantity'];
                $batch->save();

                Log::info('Reversed stock update for return deletion', [
                    'product_id' => $returnData['product_id'],
                    'product_name' => $product->name,
                    'quantity_removed' => $returnData['quantity'],
                    'new_stock' => $product->current_stock,
                    'batch_id' => $returnData['batch_id'],
                    'new_batch_quantity' => $batch->remaining_quantity
                ]);
            }

            // Step 4: Restore inventory transaction lines
            $totalRestorationAmount = 0;
            foreach ($returnsByProductBatch as $returnData) {
                // Find the transaction line
                $transactionLine = InventoryTransactionLine::where([
                    'inventory_transaction_id' => $inventoryTransaction->id,
                    'product_id' => $returnData['product_id'],
                    'batch_id' => $returnData['batch_id']
                ])->first();

                if ($transactionLine) {
                    // Calculate original values before return
                    $originalQuantity = $transactionLine->quantity + $returnData['quantity'];
                    $originalLineTotal = $originalQuantity * $transactionLine->unit_price;

                    // Calculate original line discount
                    $originalLineDiscount = 0;

                    // Get total line discount from all returned products for this line
                    $totalReturnedLineDiscount = $returnedProducts->where('product_id', $returnData['product_id'])
                        ->where('batch_id', $returnData['batch_id'])
                        ->sum(function ($returnedProduct) {
                            // Check if line_discount column exists
                            if (Schema::hasColumn('returned_products', 'line_discount')) {
                                return $returnedProduct->line_discount ?? 0;
                            }
                            return 0;
                        });

                    $originalLineDiscount = ($transactionLine->line_discount ?? 0) + $totalReturnedLineDiscount;

                    // Restore the transaction line to original state
                    $transactionLine->quantity = $originalQuantity;
                    $transactionLine->line_total = $originalLineTotal;
                    $transactionLine->line_discount = $originalLineDiscount;
                    $transactionLine->save();

                    $restorationAmount = $originalLineTotal - ($transactionLine->line_total - $returnData['amount']);
                    $totalRestorationAmount += $returnData['amount'];

                    Log::info("Restored inventory transaction line", [
                        'transaction_line_id' => $transactionLine->id,
                        'product_id' => $returnData['product_id'],
                        'restored_quantity' => $returnData['quantity'],
                        'new_quantity' => $originalQuantity,
                        'new_line_total' => $originalLineTotal,
                        'restoration_amount' => $returnData['amount'],
                        'restored_line_discount' => $totalReturnedLineDiscount,
                        'new_line_discount' => $originalLineDiscount
                    ]);
                }
            }

            // Step 5: Delete all returned product records
            $inventoryTransaction->returned_products()->delete();

            // Step 6: Update the transaction totals (add back the returned amount)
            $inventoryTransaction->subtotal += $totalReturnedAmount;
            $inventoryTransaction->grand_total += $totalReturnedAmount;
            $inventoryTransaction->save();

            // Step 7: Recalculate ledger balances for affected ledgers
            // Get unique ledger IDs that were affected by the return accounting entries
            $affectedLedgerIds = [];

            // Add customer ledger
            $affectedLedgerIds[] = $inventoryTransaction->ledger_id;

            // Add staff ledgers that were affected
            foreach ($returnedProducts->groupBy('product.category_id') as $categoryId => $categoryReturns) {
                $staffLedger = DB::table('staff_ledgers')
                    ->join('staff_product_categories', 'staff_ledgers.staff_id', '=', 'staff_product_categories.staff_id')
                    ->where('staff_product_categories.product_category_id', $categoryId)
                    ->select('staff_ledgers.ledger_id')
                    ->first();

                if ($staffLedger) {
                    $affectedLedgerIds[] = $staffLedger->ledger_id;
                }
            }

            // Recalculate balances for all affected ledgers
            foreach (array_unique($affectedLedgerIds) as $ledgerId) {
                $ledger = Ledger::find($ledgerId);
                if ($ledger) {
                    $this->recalcLedgerBalance($ledger);
                    Log::info('Recalculated ledger balance after return deletion', [
                        'ledger_id' => $ledgerId,
                        'ledger_name' => $ledger->name
                    ]);
                }
            }

            DB::commit();

            Log::info('Successfully deleted return for transaction', [
                'transaction_id' => $inventoryTransaction->id,
                'returned_amount' => $totalReturnedAmount,
                'new_grand_total' => $inventoryTransaction->grand_total
            ]);

            return response()->json([
                'success' => true,
                'message' => "Return of ৳" . number_format($totalReturnedAmount, 2) . " has been deleted successfully. Amount added back to grand total."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting return', [
                'transaction_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete return: ' . $e->getMessage()
            ], 500);
        }
    }


    // --------------------------------STORE------------------------------------------------------
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Check if the current user has the required role
            $user = Auth::user();
            if ($user->roles->pluck('name')->intersect(['staff', 'admin'])->isEmpty()) {
                throw new \Exception('You do not have permission to perform this action');
            }

            // IMPORTANT: Only preprocess customer ledger for SALE transactions
            if ($request->input('entry_type') === 'sale') {
                Log::info('Processing SALE transaction - preprocessing customer ledger', [
                    'entry_type' => $request->input('entry_type'),
                    'original_ledger_id' => $request->input('ledger_id'),
                    'user_id' => Auth::id()
                ]);

                $localLedgerId = $this->preprocessCustomerLedger($request);

                Log::info('Customer ledger preprocessing completed for SALE', [
                    'original_ledger_id' => $request->input('original_ledger_id', 'not_set'),
                    'final_ledger_id' => $request->input('ledger_id'),
                    'local_ledger_id' => $localLedgerId
                ]);
            } else {
                Log::info('Processing PURCHASE transaction - skipping customer preprocessing', [
                    'entry_type' => $request->input('entry_type'),
                    'ledger_id' => $request->input('ledger_id'),
                    'user_id' => Auth::id()
                ]);
            }

            // Now validate with the updated request (for sales) or original request (for purchases)
            $validated = $this->validateTransactionRequest($request);

            Log::info('Transaction validation passed', [
                'entry_type' => $validated['entry_type'],
                'original_ledger_id' => $request->input('original_ledger_id', 'not_set'),
                'final_ledger_id' => $validated['ledger_id'],
                'user_id' => Auth::id()
            ]);

            // Check for existing transaction for this customer today (ONLY for sales)
            if ($validated['entry_type'] === 'sale') {
                $today = Carbon::now()->format('Y-m-d');
                $existingTransaction = InventoryTransaction::where('ledger_id', $validated['ledger_id'])
                    ->whereDate('transaction_date', $today)
                    ->lockForUpdate()
                    ->first();

                if ($existingTransaction && !$request->has('append_to_transaction')) {
                    throw new \Exception('A transaction already exists for this customer today');
                }
            }

            if ($validated['entry_type'] === 'purchase') {
                return $this->processPurchaseTransaction($validated);
            } else {
                return $this->processSaleTransaction($validated);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction Creation Failed - Complete Rollback Executed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'entry_type' => $request->input('entry_type'),
                'original_ledger_id' => $request->input('ledger_id'),
                'step' => 'main_store_method'
            ]);

            // Return appropriate status code based on error type
            $statusCode = 500;
            if (strpos($e->getMessage(), 'permission') !== false) {
                $statusCode = 403;
            } elseif (strpos($e->getMessage(), 'already exists') !== false) {
                $statusCode = 409;
            } elseif (strpos($e->getMessage(), 'validation') !== false) {
                $statusCode = 422;
            }

            return response()->json([
                'success' => false,
                'message' => 'Transaction failed: ' . $e->getMessage(),
                'transaction_id' => null,
                'exists' => strpos($e->getMessage(), 'already exists') !== false
            ], $statusCode);
        }
    }


    /**
     * Enhanced customer ledger preprocessing with strict validation
     */
    private function preprocessCustomerLedger(Request $request)
    {
        try {
            $originalLedgerId = $request->input('ledger_id');
            $businessId = $this->getBusinessId();

            Log::info('Enhanced customer preprocessing - START', [
                'original_ledger_id' => $originalLedgerId,
                'business_id' => $businessId,
                'user_id' => Auth::id()
            ]);

            // Store original for logging
            if (!$request->has('original_ledger_id')) {
                $request->merge(['original_ledger_id' => $originalLedgerId]);
            }

            // Convert to integer if it's a string
            $ledgerId = is_string($originalLedgerId) ? (int)$originalLedgerId : $originalLedgerId;

            // STEP 1: Check if common_customer_id exists locally → Use if found
            $existingLocalLedger = Ledger::where('business_id', $businessId)
                ->where('common_customer_id', $ledgerId)
                ->where('ledger_type', 'Sundry Debtors (Customer)')
                ->first();

            if ($existingLocalLedger) {
                Log::info('STEP 1: Found existing local customer ledger by common_customer_id', [
                    'local_ledger_id' => $existingLocalLedger->id,
                    'common_customer_id' => $ledgerId,
                    'customer_name' => $existingLocalLedger->name,
                    'ledger_type' => $existingLocalLedger->ledger_type
                ]);

                // CRITICAL: Validate it's actually a customer ledger
                if ($existingLocalLedger->ledger_type !== 'Sundry Debtors (Customer)') {
                    throw new \Exception("Found ledger is not a customer ledger. Type: {$existingLocalLedger->ledger_type}");
                }

                $request->merge(['ledger_id' => $existingLocalLedger->id]);
                return $existingLocalLedger->id;
            }

            // STEP 2: Check if ledger_id is a local customer ledger → Use if found
            $localCustomerLedger = Ledger::where('id', $ledgerId)
                ->where('business_id', $businessId)
                ->where('ledger_type', 'Sundry Debtors (Customer)')
                ->first();

            if ($localCustomerLedger) {
                Log::info('STEP 2: Found existing local customer ledger by ID with correct type', [
                    'local_ledger_id' => $localCustomerLedger->id,
                    'customer_name' => $localCustomerLedger->name,
                    'ledger_type' => $localCustomerLedger->ledger_type,
                    'common_customer_id' => $localCustomerLedger->common_customer_id
                ]);

                return $localCustomerLedger->id;
            }

            // STEP 3: Check if it's a non-customer ledger (REJECT)
            $nonCustomerLedger = Ledger::where('id', $ledgerId)
                ->where('business_id', $businessId)
                ->where('ledger_type', '!=', 'Sundry Debtors (Customer)')
                ->first();

            if ($nonCustomerLedger) {
                throw new \Exception("Selected ledger '{$nonCustomerLedger->name}' is a '{$nonCustomerLedger->ledger_type}' ledger, not a customer ledger. Please select a customer from the customer list.");
            }

            // STEP 4: Try to create from common database
            Log::info('STEP 4: Local customer not found, attempting to create from common database', [
                'common_customer_id' => $ledgerId,
                'business_id' => $businessId
            ]);

            try {
                $createdLedger = $this->getOrCreateLocalCustomer($ledgerId, $businessId);

                // Verify creation worked and has correct type
                if (!$createdLedger || $createdLedger->ledger_type !== 'Sundry Debtors (Customer)') {
                    throw new \Exception("Created ledger does not have correct customer type");
                }

                Log::info('STEP 4: Customer ledger created successfully from common DB', [
                    'local_ledger_id' => $createdLedger->id,
                    'common_customer_id' => $ledgerId,
                    'customer_name' => $createdLedger->name,
                    'ledger_type' => $createdLedger->ledger_type
                ]);

                $request->merge(['ledger_id' => $createdLedger->id]);
                return $createdLedger->id;
            } catch (\Exception $commonDbError) {
                // STEP 5: If common DB fails, final fallback check
                Log::info('STEP 5: Common DB creation failed, performing final fallback check', [
                    'common_db_error' => $commonDbError->getMessage(),
                    'checking_local_ledger_id' => $ledgerId,
                    'business_id' => $businessId
                ]);

                // Final fallback: double-check local customer ledger
                $fallbackLocalCustomerLedger = Ledger::where('id', $ledgerId)
                    ->where('business_id', $businessId)
                    ->where('ledger_type', 'Sundry Debtors (Customer)')
                    ->first();

                if ($fallbackLocalCustomerLedger) {
                    Log::info('STEP 5: Found existing local customer ledger (fallback after common DB failure)', [
                        'local_ledger_id' => $fallbackLocalCustomerLedger->id,
                        'customer_name' => $fallbackLocalCustomerLedger->name,
                        'ledger_type' => $fallbackLocalCustomerLedger->ledger_type,
                        'common_customer_id' => $fallbackLocalCustomerLedger->common_customer_id,
                        'fallback_success' => true
                    ]);

                    return $fallbackLocalCustomerLedger->id;
                }

                // STEP 6: Complete failure - throw comprehensive error
                Log::error('STEP 6: Complete customer preprocessing failure', [
                    'ledger_id' => $ledgerId,
                    'business_id' => $businessId,
                    'common_db_error' => $commonDbError->getMessage()
                ]);

                throw new \Exception("Customer preprocessing failed: Unable to find or create customer ledger (ID: {$ledgerId}). " .
                    "This may be because: 1) The ID doesn't exist in common database, 2) It's not a customer ledger, or 3) " .
                    "Database connection issues. Original error: " . $commonDbError->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('Enhanced customer preprocessing failed', [
                'original_ledger_id' => $originalLedgerId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id()
            ]);
            throw new \Exception("Customer preprocessing failed: " . $e->getMessage());
        }
    }


    /**
     * Get or create local customer ledger from common database
     * FIXED: Proper location field handling
     */
    private function getOrCreateLocalCustomer($commonCustomerId, $businessId)
    {
        try {
            Log::info('Getting or creating local customer', [
                'common_customer_id' => $commonCustomerId,
                'business_id' => $businessId
            ]);

            // First, check if we already have this customer locally
            $existingLedger = Ledger::where('business_id', $businessId)
                ->where('common_customer_id', $commonCustomerId)
                ->first();

            if ($existingLedger) {
                Log::info('Found existing local customer ledger', [
                    'local_ledger_id' => $existingLedger->id,
                    'customer_name' => $existingLedger->name,
                    'common_customer_id' => $commonCustomerId
                ]);
                return $existingLedger;
            }

            // Get customer from common database
            $commonCustomer = DB::connection('mysql_common')
                ->table('tbl_customer_ledgers')
                ->where('ledger_id', $commonCustomerId)
                ->first();

            if (!$commonCustomer) {
                throw new \Exception("Customer not found in common database with ID: {$commonCustomerId}");
            }

            Log::info('Found customer in common database', [
                'common_customer_id' => $commonCustomerId,
                'ledger_name' => $commonCustomer->ledger_name,
                'contact_number' => $commonCustomer->contact_number,
                'district' => $commonCustomer->district,
                'sub_district' => $commonCustomer->sub_district,
                'village' => $commonCustomer->village
            ]);

            // Build full location string EXACTLY as in common DB
            $locationParts = [];
            if (!empty($commonCustomer->village)) {
                $locationParts[] = trim($commonCustomer->village);
            }
            if (!empty($commonCustomer->sub_district)) {
                $locationParts[] = trim($commonCustomer->sub_district);
            }
            if (!empty($commonCustomer->district)) {
                $locationParts[] = trim($commonCustomer->district);
            }
            $fullLocation = implode(', ', $locationParts);

            // Check if customer with same name and location already exists locally
            $existingByNameAndLocation = Ledger::where('business_id', $businessId)
                ->where('name', $commonCustomer->ledger_name)
                ->where('location', $fullLocation)
                ->first();

            if ($existingByNameAndLocation) {
                // Update existing ledger with common_customer_id
                $existingByNameAndLocation->update([
                    'common_customer_id' => $commonCustomerId,
                    'contact' => $commonCustomer->contact_number,
                    'updated_at' => now()
                ]);

                Log::info('Updated existing customer with common ID', [
                    'local_ledger_id' => $existingByNameAndLocation->id,
                    'customer_name' => $existingByNameAndLocation->name,
                    'location' => $existingByNameAndLocation->location,
                    'common_customer_id' => $commonCustomerId
                ]);

                return $existingByNameAndLocation;
            }

            // FIXED: Create new ledger with proper field mapping
            $newLedger = Ledger::create([
                'business_id' => $businessId,
                'name' => $commonCustomer->ledger_name,           // Customer name
                'contact' => $commonCustomer->contact_number,     // Contact number
                'location' => $fullLocation,                      // FIXED: Use 'location' field consistently
                'ledger_type' => 'Sundry Debtors (Customer)',    // Fixed ledger type
                'common_customer_id' => $commonCustomerId,        // Link to common DB
                'opening_balance' => 0,
                'balance_type' => 'Dr',
                'current_balance' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            if (!$newLedger || !$newLedger->id) {
                throw new \Exception('Failed to create local customer ledger');
            }

            Log::info('Created new local customer ledger with location', [
                'local_ledger_id' => $newLedger->id,
                'customer_name' => $newLedger->name,
                'ledger_type' => $newLedger->ledger_type,
                'location' => $newLedger->location,                // FIXED: Log the location field
                'common_customer_id' => $commonCustomerId,
                'contact' => $newLedger->contact,
                'location_components' => [
                    'village' => $commonCustomer->village,
                    'sub_district' => $commonCustomer->sub_district,
                    'district' => $commonCustomer->district,
                    'combined' => $fullLocation
                ]
            ]);

            return $newLedger;
        } catch (\Exception $e) {
            Log::error('Failed to get or create local customer', [
                'common_customer_id' => $commonCustomerId,
                'business_id' => $businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Customer creation failed: " . $e->getMessage());
        }
    }

    /**
     * Create local customer copy from common database with strict validation
     * FIXED: Consistent field naming for location
     */
    public function createLocalCustomer(Request $request)
    {
        $request->validate([
            'common_customer_id' => 'required|integer',
            'selection_timestamp' => 'required|integer',
            'strict_mode' => 'boolean'
        ]);

        DB::beginTransaction();
        try {
            $commonCustomerId = $request->common_customer_id;
            $businessId = $this->getBusinessId();

            Log::info('🔄 Creating local customer copy', [
                'common_customer_id' => $commonCustomerId,
                'business_id' => $businessId,
                'timestamp' => $request->selection_timestamp
            ]);

            // CRITICAL: Check again inside transaction to prevent race condition
            $existingLocal = Ledger::where('business_id', $businessId)
                ->where('common_customer_id', $commonCustomerId)
                ->lockForUpdate() // Prevent concurrent creation
                ->first();

            if ($existingLocal) {
                Log::info('✅ Local copy already exists (race condition prevented)', [
                    'existing_local_id' => $existingLocal->id,
                    'common_customer_id' => $commonCustomerId
                ]);

                DB::commit();
                return response()->json([
                    'success' => true,
                    'local_customer_id' => $existingLocal->id,
                    'customer' => $existingLocal,
                    'message' => 'Local copy already exists',
                    'was_existing' => true
                ]);
            }

            // Fetch common customer data
            $commonCustomer = DB::connection('mysql_common')
                ->table('tbl_customer_ledgers')
                ->where('ledger_id', $commonCustomerId)
                ->first();

            if (!$commonCustomer) {
                throw new \Exception('Common customer not found: ' . $commonCustomerId);
            }

            // Verify timestamp (security check)
            $timeDiff = time() * 1000 - $request->selection_timestamp;
            if ($timeDiff > 60000) { // 60 seconds max
                throw new \Exception('Selection timestamp too old, please refresh and try again');
            }

            // FIXED: Build location consistently
            $locationParts = [];
            if (!empty($commonCustomer->village)) {
                $locationParts[] = trim($commonCustomer->village);
            }
            if (!empty($commonCustomer->sub_district)) {
                $locationParts[] = trim($commonCustomer->sub_district);
            }
            if (!empty($commonCustomer->district)) {
                $locationParts[] = trim($commonCustomer->district);
            }
            $fullLocation = implode(', ', $locationParts);

            // FIXED: Create local customer copy with consistent field names
            $localCustomer = Ledger::create([
                'business_id' => $businessId,
                'name' => $commonCustomer->ledger_name,
                'ledger_type' => 'Sundry Debtors (Customer)',
                'opening_balance' => 0,
                'current_balance' => 0,
                'common_customer_id' => $commonCustomerId,
                'contact' => $commonCustomer->contact_number,        // FIXED: Use 'contact' not 'contact_number'
                'location' => $fullLocation,                         // FIXED: Use 'location' not 'address'
                'created_by' => Auth::id(),
                'is_active' => true,
                'balance_type' => 'Dr'                              // ADDED: Default balance type
            ]);

            Log::info('✅ Local customer copy created successfully with location', [
                'local_customer_id' => $localCustomer->id,
                'common_customer_id' => $commonCustomerId,
                'customer_name' => $localCustomer->name,
                'location' => $localCustomer->location,              // FIXED: Log location field
                'contact' => $localCustomer->contact,
                'location_breakdown' => [
                    'village' => $commonCustomer->village,
                    'sub_district' => $commonCustomer->sub_district,
                    'district' => $commonCustomer->district
                ]
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'local_customer_id' => $localCustomer->id,
                'customer' => $localCustomer,
                'message' => 'Local customer copy created successfully',
                'was_existing' => false
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('💥 Failed to create local customer copy', [
                'common_customer_id' => $request->common_customer_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create local customer copy: ' . $e->getMessage()
            ], 500);
        }
    }


    public function determineCustomerType($ledgerId)
    {
        try {
            Log::info('🔍 Determining customer type for ledger ID: ' . $ledgerId, [
                'business_id' => $this->getBusinessId(),
                'timestamp' => now()
            ]);

            // Step 1: Check if it's a common customer (from shared DB)
            $commonCustomer = DB::connection('mysql_common')
                ->table('tbl_customer_ledgers')
                ->where('ledger_id', $ledgerId)
                ->first();

            if ($commonCustomer) {
                Log::info('✅ Found common customer', [
                    'ledger_id' => $ledgerId,
                    'customer_name' => $commonCustomer->ledger_name,
                    'location' => $commonCustomer->village . ', ' . $commonCustomer->sub_district
                ]);

                // Check if local copy exists by common_customer_id (CRITICAL FIX)
                $localCopy = Ledger::where('business_id', $this->getBusinessId())
                    ->where('common_customer_id', $ledgerId) // Use common_customer_id, not id
                    ->where('ledger_type', 'Sundry Debtors (Customer)')
                    ->first();

                $result = [
                    'success' => true,
                    'exists' => true,
                    'is_common' => true,
                    'is_local' => false,
                    'is_valid_customer' => true,
                    'has_local_copy' => $localCopy ? true : false,
                    'local_id' => $localCopy ? $localCopy->id : null,
                    'customer_name' => $commonCustomer->ledger_name,
                    'ledger_type' => 'Sundry Debtors (Customer)',
                    'common_customer_id' => $ledgerId,
                    'customer_location' => $commonCustomer->village . ', ' . $commonCustomer->sub_district . ', ' . $commonCustomer->district
                ];

                Log::info('📋 Common customer determination result', $result);
                return response()->json($result);
            }

            // Step 2: Check if it's a local customer
            $localCustomer = Ledger::where('business_id', $this->getBusinessId())
                ->where('id', $ledgerId)
                ->where('ledger_type', 'Sundry Debtors (Customer)')
                ->first();

            if ($localCustomer) {
                Log::info('✅ Found local customer', [
                    'ledger_id' => $ledgerId,
                    'customer_name' => $localCustomer->name,
                    'common_customer_id' => $localCustomer->common_customer_id
                ]);

                $result = [
                    'success' => true,
                    'exists' => true,
                    'is_common' => false,
                    'is_local' => true,
                    'is_valid_customer' => true,
                    'has_local_copy' => true,
                    'local_id' => $localCustomer->id,
                    'customer_name' => $localCustomer->name,
                    'ledger_type' => $localCustomer->ledger_type,
                    'common_customer_id' => $localCustomer->common_customer_id
                ];

                Log::info('📋 Local customer determination result', $result);
                return response()->json($result);
            }

            // Customer doesn't exist anywhere
            Log::warning('❌ Customer not found', ['ledger_id' => $ledgerId]);
            return response()->json([
                'success' => false,
                'exists' => false,
                'message' => 'Customer not found in any database'
            ], 404);
        } catch (\Exception $e) {
            Log::error('💥 Customer type determination failed', [
                'ledger_id' => $ledgerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'exists' => false,
                'message' => 'Failed to determine customer type: ' . $e->getMessage()
            ], 500);
        }
    }



    // Add this method to generate invoice ID
    private function generateInvoiceId($user)
    {
        // Get user's first and last name
        $nameParts = explode(' ', $user->name);
        $firstName = $nameParts[0] ?? '';
        $lastName = end($nameParts) ?? '';

        // Get initials
        $firstInitial = strtoupper(substr($firstName, 0, 1));
        $lastInitial = strtoupper(substr($lastName, 0, 1));
        $initials = $firstInitial . $lastInitial;

        // Get user ID for staff or admin
        $userId = $user->id;
        $staffId = null;

        if ($user->roles->pluck('name')->contains('staff')) {
            $staff = Staff::where('user_id', $userId)->first();
            $staffId = $staff ? $staff->id : null;
        } elseif ($user->roles->pluck('name')->contains('admin')) {
            $admin = BusinessAdmin::where('user_id', $userId)->first();
            $staffId = $admin ? $admin->id : null;
        }

        if (!$staffId) {
            // Fallback if no staff/admin record found
            return $initials . '-' . time();
        }

        // Get all transactions for this staff ordered by creation date
        $staffTransactions = InventoryTransaction::whereHas('creators', function ($q) use ($staffId) {
            $q->where('staff_id', $staffId);
        })
            ->orderBy('created_at', 'asc')
            ->get();

        // Simply count the total number of transactions and add 1
        $memoCounter = $staffTransactions->count() + 1;

        // Format: Initials-SequentialNumber
        return $initials . '-' . $memoCounter;
    }

    /**
     * Process purchase transaction with full atomicity
     */
    private function processPurchaseTransaction($validated)
    {
        try {
            Log::info('Starting purchase transaction processing', [
                'user_id' => Auth::id(),
                'lines_count' => count($validated['lines']),
                'grand_total' => $validated['grand_total'],
                'lines_data' => $validated['lines'] // Add this to see the actual line data
            ]);

            // Step 1: Get and validate business ID
            $businessId = $this->getBusinessId();
            if (!$businessId) {
                throw new \Exception('Could not determine business ID for current user');
            }

            // Step 2: Create base transaction with validation
            $transaction = $this->createBaseTransaction($validated);
            if (!$transaction || !$transaction->id) {
                throw new \Exception('Failed to create base purchase transaction');
            }

            // Step 3: Get category mappings for common products
            $categoryMappings = ProductCategory::where('business_id', $businessId)
                ->whereNotNull('common_category_id')
                ->pluck('id', 'common_category_id')
                ->toArray();

            Log::info('Category mappings loaded', [
                'business_id' => $businessId,
                'mappings_count' => count($categoryMappings)
            ]);

            // Step 4: Process each purchase line atomically
            $processedLines = [];
            $createdProducts = [];
            $updatedBatches = [];

            foreach ($validated['lines'] as $index => $line) {
                try {
                    // Add detailed logging for each line BEFORE processing
                    Log::info('About to process purchase line', [
                        'line_index' => $index,
                        'line_number' => $index + 1,
                        'line_data' => $line,
                        'line_keys' => array_keys($line ?? []),
                        'has_product_id' => isset($line['product_id']),
                        'product_id_value' => $line['product_id'] ?? 'MISSING'
                    ]);

                    // Make sure we're passing the line data correctly
                    if (!is_array($line)) {
                        throw new \Exception("Line data is not an array. Type: " . gettype($line) . ", Value: " . json_encode($line));
                    }

                    $lineResult = $this->processPurchaseLineAtomic($transaction, $line, $businessId, $categoryMappings, $index + 1);

                    if (!$lineResult['success']) {
                        throw new \Exception($lineResult['error']);
                    }

                    $processedLines[] = $lineResult['data'];

                    // Track created products and batches for logging
                    if (isset($lineResult['data']['product_created']) && $lineResult['data']['product_created']) {
                        $createdProducts[] = $lineResult['data']['product_id'];
                    }
                    $updatedBatches[] = $lineResult['data']['batch_id'];

                    Log::info('Purchase line completed successfully', [
                        'line_number' => $index + 1,
                        'processed_lines_count' => count($processedLines)
                    ]);
                } catch (\Exception $e) {
                    Log::error('Purchase line processing error', [
                        'line_index' => $index,
                        'line_number' => $index + 1,
                        'error' => $e->getMessage(),
                        'line_data' => $line ?? 'null',
                        'processed_so_far' => count($processedLines)
                    ]);
                    throw new \Exception("Line processing failed for purchase line " . ($index + 1) . ": " . $e->getMessage());
                }
            }

            Log::info('All purchase lines processed successfully', [
                'transaction_id' => $transaction->id,
                'lines_processed' => count($processedLines),
                'products_created' => count($createdProducts),
                'batches_created' => count($updatedBatches)
            ]);

            // Step 5: Create accounting entries
            $this->createAccountingEntries($transaction);

            // Step 6: Finalize transaction
            $this->finalizeTransaction();

            Log::info('Purchase transaction completed successfully', [
                'transaction_id' => $transaction->id,
                'invoice_id' => $transaction->invoice_id,
                'grand_total' => $transaction->grand_total
            ]);

            // ADD THIS LINE HERE:
            $this->clearRelatedShopfrontCache($businessId);

            return response()->json([
                'success' => true,
                'message' => 'Purchase transaction processed successfully',
                'transaction_id' => $transaction->id,
                'invoice_id' => $transaction->invoice_id,
                'data' => [
                    'lines_processed' => count($processedLines),
                    'products_created' => count($createdProducts),
                    'grand_total' => $transaction->grand_total
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Purchase transaction processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'validated_data' => $validated
            ]);

            throw new \Exception("Purchase processing failed: " . $e->getMessage());
        }
    }

    /**
     * Clear shopfront cache after inventory changes
     */
    private function clearRelatedShopfrontCache($businessId)
    {
        try {
            // Find all shopfronts for this business
            $shopfronts = \App\Models\BusinessShopfront::where('business_id', $businessId)
                ->where('is_active', true)
                ->get();

            // Clear cache for each shopfront
            foreach ($shopfronts as $shopfront) {
                $cacheKey = "shopfront_{$shopfront->shopfront_id}";
                \Illuminate\Support\Facades\Cache::forget($cacheKey);
            }

            Log::info('Cleared shopfront cache after inventory change', [
                'business_id' => $businessId,
                'shopfronts_cleared' => $shopfronts->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to clear shopfront cache: ' . $e->getMessage());
        }
    }


    /**
     * Validate transaction integrity after processing
     */
    private function validateTransactionIntegrity($transaction, $validated)
    {
        try {
            // Validate all intended discounts were processed
            $requestedDiscounts = 0;
            foreach ($validated['lines'] as $line) {
                $requestedDiscounts += $line['line_discount'] ?? 0;
            }

            $processedDiscounts = InventoryTransactionLine::where('inventory_transaction_id', $transaction->id)
                ->sum('line_discount');

            if (abs($requestedDiscounts - $processedDiscounts) > 0.01) {
                throw new \Exception("Discount processing mismatch. Requested: {$requestedDiscounts}, Processed: {$processedDiscounts}");
            }

            Log::info('Transaction integrity validated', [
                'transaction_id' => $transaction->id,
                'requested_discounts' => $requestedDiscounts,
                'processed_discounts' => $processedDiscounts,
                'discounts_match' => true
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Transaction integrity validation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Transaction integrity validation failed: " . $e->getMessage());
        }
    }

    private function processSaleTransaction($validated)
    {
        try {
            // Step 1: Create base transaction with validation
            $transaction = $this->createBaseTransaction($validated);
            if (!$transaction || !$transaction->id) {
                throw new \Exception("Failed to create base transaction record");
            }
            Log::info('Base transaction created successfully', [
                'transaction_id' => $transaction->id,
                'invoice_id' => $transaction->invoice_id,
                'grand_total' => $transaction->grand_total
            ]);

            // Step 2: Get and validate current user and their permissions
            $user = Auth::user();
            $userRole = $user->roles->first();
            if (!$userRole) {
                throw new \Exception("User has no assigned role");
            }
            $isAdmin = $userRole->name === 'admin';
            $userRecord = null;
            $staffCategories = collect();

            if (!$isAdmin) {
                $userRecord = Staff::where('user_id', $user->id)->first();
                if (!$userRecord) {
                    throw new \Exception("Staff record not found for the current user");
                }
                // Get the staff's assigned categories with validation
                $staffCategories = $userRecord->productCategories;
                if ($staffCategories->isEmpty()) {
                    throw new \Exception("Staff has no assigned product categories");
                }
            }

            // Step 3: Process each transaction line with comprehensive validation
            $processedLines = [];
            $totalProcessedAmount = 0;

            foreach ($validated['lines'] as $lineIndex => $line) {
                try {
                    // Validate product exists
                    $product = Product::find($line['product_id']);
                    if (!$product) {
                        throw new \Exception("Product not found for line " . ($lineIndex + 1));
                    }

                    // Check category permission for staff (skip for admin)
                    if (!$isAdmin) {
                        $canProcessProduct = $staffCategories->contains('id', $product->category_id);
                        if (!$canProcessProduct) {
                            $categoryName = $product->category ? $product->category->name : 'Unknown Category';
                            throw new \Exception("You don't have permission to sell products in the {$categoryName} category");
                        }
                    }

                    // Process the sale line with validation
                    $lineResult = $this->processSaleLine($transaction, $product, $line);
                    if (!$lineResult['success']) {
                        throw new \Exception("Failed to process line for product '{$product->name}': " . $lineResult['error']);
                    }

                    $processedLines[] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'category_id' => $product->category_id,
                        'quantity' => $line['quantity'],
                        'amount' => $line['line_total']
                    ];
                    $totalProcessedAmount += $line['line_total'];

                    // Record the staff as a contributor for this product's category (CRITICAL - Must succeed)
                    if (!$isAdmin && $userRecord) {
                        try {
                            // Use direct DB insertion for better error handling
                            $contributorId = DB::table('inventory_transaction_contributors')->insertGetId([
                                'transaction_id' => $transaction->id,
                                'staff_id' => $userRecord->id,
                                'product_category_id' => $product->category_id,
                                'product_id' => $product->id,
                                'contributed_quantity' => $line['quantity'],
                                'contributed_amount' => $line['line_total'],
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);

                            if (!$contributorId) {
                                throw new \Exception("Failed to create contributor record - database insertion returned null");
                            }

                            Log::info('Staff contributor record created successfully', [
                                'contributor_id' => $contributorId,
                                'transaction_id' => $transaction->id,
                                'staff_id' => $userRecord->id,
                                'product_category_id' => $product->category_id,
                                'product_id' => $product->id,
                                'product_name' => $product->name,
                                'contributed_quantity' => $line['quantity'],
                                'contributed_amount' => $line['line_total']
                            ]);
                        } catch (\Exception $e) {
                            // Check if it's a duplicate entry error
                            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                                // For duplicate entries, try to update the existing record
                                try {
                                    $existingContributor = DB::table('inventory_transaction_contributors')
                                        ->where('transaction_id', $transaction->id)
                                        ->where('staff_id', $userRecord->id)
                                        ->where('product_id', $product->id)
                                        ->first();

                                    if ($existingContributor) {
                                        $updateResult = DB::table('inventory_transaction_contributors')
                                            ->where('transaction_id', $transaction->id)
                                            ->where('staff_id', $userRecord->id)
                                            ->where('product_id', $product->id)
                                            ->update([
                                                'contributed_quantity' => $existingContributor->contributed_quantity + $line['quantity'],
                                                'contributed_amount' => $existingContributor->contributed_amount + $line['line_total'],
                                                'updated_at' => now()
                                            ]);

                                        if (!$updateResult) {
                                            throw new \Exception("Failed to update existing contributor record");
                                        }

                                        Log::info('Updated existing contributor record', [
                                            'transaction_id' => $transaction->id,
                                            'staff_id' => $userRecord->id,
                                            'product_id' => $product->id,
                                            'added_quantity' => $line['quantity'],
                                            'added_amount' => $line['line_total']
                                        ]);
                                    } else {
                                        throw new \Exception("Duplicate entry detected but existing record not found");
                                    }
                                } catch (\Exception $updateException) {
                                    Log::error('Critical contributor record failure', [
                                        'original_error' => $e->getMessage(),
                                        'update_error' => $updateException->getMessage(),
                                        'transaction_id' => $transaction->id,
                                        'staff_id' => $userRecord->id,
                                        'product_id' => $product->id,
                                        'product_name' => $product->name
                                    ]);
                                    throw new \Exception("CRITICAL FAILURE: Unable to record staff contribution for product '{$product->name}'. Original error: {$e->getMessage()}. Update attempt failed: {$updateException->getMessage()}");
                                }
                            } else {
                                // Any non-duplicate error is critical and must fail the transaction
                                Log::error('Critical contributor record creation failed', [
                                    'error' => $e->getMessage(),
                                    'transaction_id' => $transaction->id,
                                    'staff_id' => $userRecord->id,
                                    'product_id' => $product->id,
                                    'product_name' => $product->name,
                                    'line_index' => $lineIndex + 1
                                ]);
                                throw new \Exception("CRITICAL FAILURE: Unable to record staff contribution for product '{$product->name}' on line " . ($lineIndex + 1) . ". Transaction must be aborted. Error: " . $e->getMessage());
                            }
                        }
                    }

                    Log::info('Transaction line processed successfully', [
                        'transaction_id' => $transaction->id,
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'quantity' => $line['quantity'],
                        'line_total' => $line['line_total']
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception("Line processing failed for product line " . ($lineIndex + 1) . ": " . $e->getMessage());
                }
            }

            // Step 4: Validate processed amounts match expected totals
            if (abs($totalProcessedAmount - $validated['subtotal']) > 0.01) {
                throw new \Exception("Processed amount ({$totalProcessedAmount}) does not match expected subtotal ({$validated['subtotal']})");
            }

            Log::info('All transaction lines processed successfully', [
                'transaction_id' => $transaction->id,
                'lines_processed' => count($processedLines),
                'total_amount' => $totalProcessedAmount,
                'contributor_records_created' => !$isAdmin ? count($processedLines) : 0
            ]);

            // Step 5: Create accounting entries with full validation
            $this->createAccountingEntries($transaction);

            // Step 6: Finalize transaction
            $this->finalizeTransaction();

            // Step 7: Validate transaction integrity
            $this->validateTransactionIntegrity($transaction, $validated);

            Log::info('Sale transaction completed successfully', [
                'transaction_id' => $transaction->id,
                'invoice_id' => $transaction->invoice_id,
                'user_id' => $user->id,
                'is_admin' => $isAdmin,
                'lines_count' => count($processedLines),
                'grand_total' => $transaction->grand_total,
                'contributor_records' => !$isAdmin ? count($processedLines) : 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sale transaction processed successfully',
                'transaction_id' => $transaction->id,
                'invoice_id' => $transaction->invoice_id,
                'grand_total' => $transaction->grand_total
            ]);
        } catch (\Exception $e) {
            Log::error('Sale transaction processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
                'step' => 'process_sale_transaction'
            ]);
            // Re-throw to trigger main transaction rollback
            throw new \Exception("Sale processing failed: " . $e->getMessage());
        }
    }




    /**
     * Enhanced transaction request validation with customer verification
     */
    private function validateTransactionRequest(Request $request)
    {
        $baseValidation = [
            'entry_type' => ['required', 'in:sale,purchase'],
            'transaction_date' => ['required', 'date'],
            'ledger_id' => ['required', 'integer', function ($attribute, $value, $fail) {
                // Enhanced ledger validation
                $businessId = $this->getBusinessId();
                $ledger = Ledger::where('business_id', $businessId)
                    ->where('id', $value)
                    ->first();

                if (!$ledger) {
                    $fail('The selected ledger does not exist or does not belong to your business.');
                    return;
                }

                // CRITICAL: For sales, ensure it's a customer ledger
                if (request()->input('entry_type') === 'sale' && $ledger->ledger_type !== 'Sundry Debtors (Customer)') {
                    $fail("The selected ledger '{$ledger->name}' is a '{$ledger->ledger_type}' ledger, not a customer ledger. Please select a customer.");
                    return;
                }
            }],
            'payment_method' => ['required', 'in:cash,credit'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', function ($attribute, $value, $fail) {
                // Check in local products table
                $exists = Product::where('id', $value)->exists();
                if (!$exists) {
                    // Check in common products table
                    $existsInCommon = DB::connection('mysql_common')
                        ->table('tbl_common_product')
                        ->where('product_id', $value)
                        ->exists();
                    if (!$existsInCommon) {
                        $fail('The selected product does not exist.');
                    }
                }
            }],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_price' => ['required', 'numeric', 'gt:0'],
            'lines.*.line_total' => ['required', 'numeric', 'gt:0'],
            'lines.*.line_discount' => ['nullable', 'numeric'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'round_off' => ['required', 'numeric'],
            'discount' => ['required', 'numeric', 'min:0'],
            'grand_total' => ['required', 'numeric', 'min:0'],
            // Enhanced customer verification fields
            'original_ledger_id' => ['nullable', 'integer'],
            'customer_verification' => ['nullable', 'array'],
            'customer_verification.selection_timestamp' => ['nullable', 'integer'],
            'customer_verification.customer_type' => ['nullable', 'in:local,common'],
            'customer_verification.ledger_type' => ['nullable', 'string']
        ];

        if ($request->input('entry_type') === 'purchase') {
            $baseValidation['lines.*.trade_price'] = ['required', 'numeric', 'gt:0'];
        }

        $validated = $request->validate($baseValidation);

        // Additional customer verification logging
        if (isset($validated['customer_verification'])) {
            Log::info('Customer verification data received in validation', [
                'ledger_id' => $validated['ledger_id'],
                'original_ledger_id' => $validated['original_ledger_id'] ?? null,
                'verification_data' => $validated['customer_verification'],
                'entry_type' => $validated['entry_type']
            ]);
        }

        return $validated;
    }




    private function createBaseTransaction($validated)
    {
        try {
            // Validate required fields
            if (!isset($validated['entry_type']) || !in_array($validated['entry_type'], ['sale', 'purchase'])) {
                throw new \Exception('Invalid or missing entry type');
            }

            if (!isset($validated['grand_total']) || $validated['grand_total'] <= 0) {
                throw new \Exception('Invalid grand total amount');
            }

            if (!isset($validated['ledger_id'])) {
                throw new \Exception('Ledger ID is required');
            }

            $businessId = $this->getBusinessId();
            if (!$businessId) {
                throw new \Exception('Could not determine business ID for current user');
            }

            // Verify ledger exists and belongs to business (since we pre-resolved it)
            $ledger = Ledger::where('id', $validated['ledger_id'])
                ->where('business_id', $businessId)
                ->first();

            if (!$ledger) {
                throw new \Exception('Ledger not found or does not belong to this business');
            }

            // Generate invoice ID with validation
            $invoiceId = $this->generateInvoiceId(Auth::user());
            if (!$invoiceId) {
                throw new \Exception('Failed to generate invoice ID');
            }

            $transaction = InventoryTransaction::create([
                'business_id' => $businessId,
                'entry_type' => $validated['entry_type'],
                'transaction_date' => $validated['transaction_date'],
                'ledger_id' => $validated['ledger_id'], // Already resolved
                'payment_method' => $validated['payment_method'],
                'subtotal' => $validated['subtotal'],
                'round_off' => $validated['round_off'],
                'discount' => $validated['discount'],
                'grand_total' => $validated['grand_total'],
                'invoice_id' => $invoiceId
            ]);

            if (!$transaction || !$transaction->id) {
                throw new \Exception('Failed to create inventory transaction record');
            }

            Log::info('Base transaction created successfully', [
                'transaction_id' => $transaction->id,
                'invoice_id' => $invoiceId,
                'entry_type' => $validated['entry_type'],
                'grand_total' => $validated['grand_total'],
                'business_id' => $businessId,
                'ledger_id' => $validated['ledger_id'],
                'customer_name' => $ledger->name
            ]);

            return $transaction;
        } catch (\Exception $e) {
            Log::error('Base transaction creation failed', [
                'error' => $e->getMessage(),
                'validated_data' => $validated,
                'user_id' => Auth::id()
            ]);
            throw new \Exception("Base transaction creation failed: " . $e->getMessage());
        }
    }





    /**
     * Process individual purchase line with full atomicity and validation
     */
    private function processPurchaseLineAtomic($transaction, $line, $businessId, $categoryMappings, $lineNumber)
    {
        try {
            // Add comprehensive logging at the start
            Log::info('Processing purchase line', [
                'line_number' => $lineNumber,
                'original_product_id' => $line['product_id'] ?? 'MISSING',
                'line_data' => $line,
                'line_keys' => array_keys($line ?? [])
            ]);

            // Step 1: Validate line data with better error messages
            $requiredFields = ['product_id', 'quantity', 'unit_price', 'trade_price', 'line_total'];
            foreach ($requiredFields as $field) {
                if (!isset($line[$field])) {
                    return [
                        'success' => false,
                        'error' => "Missing required field '{$field}' in line data. Available fields: " . implode(', ', array_keys($line ?? []))
                    ];
                }

                if (in_array($field, ['quantity', 'unit_price', 'trade_price', 'line_total']) && (!is_numeric($line[$field]) || $line[$field] <= 0)) {
                    return [
                        'success' => false,
                        'error' => "Invalid value for field '{$field}': {$line[$field]}. Must be a positive number."
                    ];
                }
            }

            // Extract and validate product ID
            $originalProductId = $line['product_id'];
            $actualProductId = $this->extractActualProductId($originalProductId);

            Log::info('Product ID extracted', [
                'line_number' => $lineNumber,
                'original_product_id' => $originalProductId,
                'actual_product_id' => $actualProductId
            ]);

            // Step 2: Get or create product
            $productResult = $this->getOrCreateProductForPurchase($actualProductId, $businessId, $categoryMappings, $line);
            if (!$productResult['success']) {
                return $productResult;
            }

            $product = $productResult['product'];
            $productCreated = $productResult['created'];

            Log::info('Product resolved for purchase line', [
                'line_number' => $lineNumber,
                'original_product_id' => $originalProductId,
                'resolved_local_product_id' => $product->id,
                'product_name' => $product->name,
                'product_created' => $productCreated,
                'common_product_id' => $actualProductId
            ]);

            // Step 3: Create batch for the product
            $batchResult = $this->createPurchaseBatch($product, $line);
            if (!$batchResult['success']) {
                return $batchResult;
            }

            $batch = $batchResult['batch'];

            // Step 4: Create transaction line
            $transactionLineResult = $this->createPurchaseTransactionLine($transaction, $product, $batch, $line);
            if (!$transactionLineResult['success']) {
                return $transactionLineResult;
            }

            $transactionLine = $transactionLineResult['transaction_line'];

            // Step 5: Update product stock
            $stockResult = $this->updateProductStockForPurchase($product, $line['quantity']);
            if (!$stockResult['success']) {
                return $stockResult;
            }

            Log::info('Purchase line processed successfully', [
                'line_number' => $lineNumber,
                'original_product_id' => $originalProductId,
                'local_product_id' => $product->id,
                'product_name' => $product->name,
                'product_created' => $productCreated,
                'batch_id' => $batch->id,
                'quantity' => $line['quantity'],
                'transaction_line_id' => $transactionLine->id,
                'new_stock' => $product->fresh()->current_stock
            ]);

            return [
                'success' => true,
                'data' => [
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'transaction_line_id' => $transactionLine->id,
                    'quantity' => $line['quantity'],
                    'product_created' => $productCreated,
                    'stock_updated' => true
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Purchase line processing failed', [
                'line_number' => $lineNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line_data' => $line ?? 'null'
            ]);

            return [
                'success' => false,
                'error' => "Line processing exception: " . $e->getMessage()
            ];
        }
    }

    /**
     * Extract actual product ID from potentially prefixed ID
     */
    private function extractActualProductId($productId)
    {
        // Handle common_ prefixed IDs
        if (is_string($productId) && strpos($productId, 'common_') === 0) {
            return (int) str_replace('common_', '', $productId);
        }

        // Handle regular numeric IDs
        return (int) $productId;
    }

    /**
     * Get existing product or create new one from common products
     */
    private function getOrCreateProductForPurchase($productId, $businessId, $categoryMappings, $line)
    {
        try {
            Log::info('Looking up product for purchase', [
                'product_id' => $productId,
                'business_id' => $businessId
            ]);

            // Step 1: Try to get local product first
            $product = Product::where('business_id', $businessId)->find($productId);

            if ($product) {
                Log::info('Using existing local product', [
                    'product_id' => $product->id,
                    'product_name' => $product->name
                ]);

                return [
                    'success' => true,
                    'product' => $product,
                    'created' => false
                ];
            }

            // Step 2: Check common products if local product not found
            $commonProduct = CommonProduct::find($productId);
            if (!$commonProduct) {
                return [
                    'success' => false,
                    'error' => "Product not found in either local or common database (ID: {$productId})"
                ];
            }

            Log::info('Found common product, checking for existing local copy', [
                'common_product_id' => $commonProduct->product_id,
                'common_product_name' => $commonProduct->product_name
            ]);

            // Step 3: Look for existing local copy using common product name
            $existingProduct = Product::where('business_id', $businessId)
                ->where('name', $commonProduct->product_name)
                ->first();

            if ($existingProduct) {
                Log::info('Found existing local product by name', [
                    'product_id' => $existingProduct->id,
                    'product_name' => $existingProduct->name,
                    'common_product_id' => $commonProduct->product_id
                ]);

                return [
                    'success' => true,
                    'product' => $existingProduct,
                    'created' => false
                ];
            }

            // Step 4: Create new product from common product
            return $this->createProductFromCommon($commonProduct, $businessId, $categoryMappings, $line);
        } catch (\Exception $e) {
            Log::error('Product lookup/creation failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => "Product lookup/creation failed: " . $e->getMessage()
            ];
        }
    }


    /**
     * Create new product from common product data - PRODUCTION READY
     */
    private function createProductFromCommon($commonProduct, $businessId, $categoryMappings, $line)
    {
        try {
            // Step 1: Validate common product data
            if (!$commonProduct || !$commonProduct->product_id) {
                return [
                    'success' => false,
                    'error' => "Invalid common product data provided"
                ];
            }

            // Step 2: Map common category ID to business category ID
            $businessCategoryId = $categoryMappings[$commonProduct->category_id] ?? null;

            if (!$businessCategoryId) {
                Log::error('Category mapping not found', [
                    'common_category_id' => $commonProduct->category_id,
                    'available_mappings' => array_keys($categoryMappings),
                    'product_name' => $commonProduct->product_name
                ]);

                return [
                    'success' => false,
                    'error' => "Product category '{$commonProduct->category_id}' not found in your business. Please import the category first."
                ];
            }

            // Step 3: Verify business category exists
            $categoryExists = ProductCategory::where('id', $businessCategoryId)
                ->where('business_id', $businessId)
                ->exists();

            if (!$categoryExists) {
                return [
                    'success' => false,
                    'error' => "Mapped category does not exist in business database"
                ];
            }

            // Step 4: Check for duplicate by common_product_id (safety check)
            $existingDuplicate = Product::where('business_id', $businessId)
                ->where('common_product_id', $commonProduct->product_id)
                ->first();

            if ($existingDuplicate) {
                Log::warning('Duplicate product creation attempt prevented', [
                    'common_product_id' => $commonProduct->product_id,
                    'existing_product_id' => $existingDuplicate->id
                ]);

                return [
                    'success' => true,
                    'product' => $existingDuplicate,
                    'created' => false
                ];
            }

            // Step 5: Prepare product data with validation
            $productData = [
                'business_id' => $businessId,
                'common_product_id' => $commonProduct->product_id,
                'name' => $commonProduct->product_name,
                'barcode' => $commonProduct->barcode,
                'category_id' => $businessCategoryId,
                'unit_id' => $commonProduct->unit_id,
                'dealer_price' => max(0, (float)($line['unit_price'] ?? 0)),
                'trade_price' => max(0, (float)($line['trade_price'] ?? 0)),
                'current_stock' => 0,
                'image' => $commonProduct->image
            ];

            // Step 6: Validate required fields
            if (empty($productData['name'])) {
                return [
                    'success' => false,
                    'error' => "Product name is required"
                ];
            }

            if ($productData['dealer_price'] <= 0 || $productData['trade_price'] <= 0) {
                return [
                    'success' => false,
                    'error' => "Valid dealer price and trade price are required"
                ];
            }

            // Step 7: Create the product
            $product = Product::create($productData);

            if (!$product || !$product->id) {
                return [
                    'success' => false,
                    'error' => "Failed to create product record in database"
                ];
            }

            Log::info('Successfully created new product from common product', [
                'common_product_id' => $commonProduct->product_id,
                'new_local_product_id' => $product->id,
                'product_name' => $product->name,
                'business_category_id' => $businessCategoryId,
                'dealer_price' => $product->dealer_price,
                'trade_price' => $product->trade_price
            ]);

            return [
                'success' => true,
                'product' => $product,
                'created' => true
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            // Handle database constraint violations
            if (str_contains($e->getMessage(), 'unique_business_common_product')) {
                Log::warning('Duplicate product creation prevented by database constraint', [
                    'common_product_id' => $commonProduct->product_id,
                    'business_id' => $businessId
                ]);

                // Try to find the existing product
                $existingProduct = Product::where('business_id', $businessId)
                    ->where('common_product_id', $commonProduct->product_id)
                    ->first();

                if ($existingProduct) {
                    return [
                        'success' => true,
                        'product' => $existingProduct,
                        'created' => false
                    ];
                }
            }

            Log::error('Database error during product creation', [
                'common_product_id' => $commonProduct->product_id ?? 'unknown',
                'error' => $e->getMessage(),
                'business_id' => $businessId
            ]);

            return [
                'success' => false,
                'error' => "Database error during product creation: " . $e->getMessage()
            ];
        } catch (\Exception $e) {
            Log::error('Product creation from common failed', [
                'common_product_id' => $commonProduct->product_id ?? 'unknown',
                'product_name' => $commonProduct->product_name ?? 'unknown',
                'business_id' => $businessId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => "Product creation failed: " . $e->getMessage()
            ];
        }
    }



    /**
     * Create batch for purchase with validation
     */
    private function createPurchaseBatch($product, $line)
    {
        try {
            // Validate inputs
            if (!$product || !$product->id) {
                return [
                    'success' => false,
                    'error' => "Invalid product provided for batch creation"
                ];
            }

            // Generate unique batch number
            $batchNumber = 'B-' . time() . rand(1000, 9999);

            // Ensure batch number is unique
            while (ProductBatch::where('batch_number', $batchNumber)->exists()) {
                $batchNumber = 'B-' . time() . rand(1000, 9999);
            }

            $batchData = [
                'product_id' => $product->id,
                'batch_number' => $batchNumber,
                'dealer_price' => $line['unit_price'],
                'trade_price' => $line['trade_price'],
                'remaining_quantity' => $line['quantity'],
                'batch_date' => now()->toDateString(),
                'is_opening_batch' => false
            ];

            $batch = ProductBatch::create($batchData);

            if (!$batch || !$batch->id) {
                return [
                    'success' => false,
                    'error' => "Failed to create product batch"
                ];
            }

            Log::info('Purchase batch created', [
                'batch_id' => $batch->id,
                'batch_number' => $batch->batch_number,
                'product_id' => $product->id,
                'quantity' => $line['quantity']
            ]);

            return [
                'success' => true,
                'batch' => $batch
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Batch creation failed: " . $e->getMessage()
            ];
        }
    }

    /**
     * Create purchase transaction line with validation
     */
    private function createPurchaseTransactionLine($transaction, $product, $batch, $line)
    {
        try {
            // Validate inputs
            if (!$transaction || !$transaction->id) {
                return [
                    'success' => false,
                    'error' => "Invalid transaction provided"
                ];
            }

            if (!$product || !$product->id) {
                return [
                    'success' => false,
                    'error' => "Invalid product provided"
                ];
            }

            if (!$batch || !$batch->id) {
                return [
                    'success' => false,
                    'error' => "Invalid batch provided"
                ];
            }

            $transactionLineData = [
                'inventory_transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'quantity' => $line['quantity'],
                'unit_price' => $line['unit_price'],
                'dealer_price' => $line['unit_price'],
                'line_total' => $line['line_total']
            ];

            $transactionLine = InventoryTransactionLine::create($transactionLineData);

            if (!$transactionLine || !$transactionLine->id) {
                return [
                    'success' => false,
                    'error' => "Failed to create transaction line"
                ];
            }

            Log::info('Purchase transaction line created', [
                'transaction_line_id' => $transactionLine->id,
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'quantity' => $line['quantity']
            ]);

            return [
                'success' => true,
                'transaction_line' => $transactionLine
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Transaction line creation failed: " . $e->getMessage()
            ];
        }
    }

    /**
     * Update product stock for purchase with validation
     */
    private function updateProductStockForPurchase($product, $quantity)
    {
        try {
            if (!$product || !$product->id) {
                return [
                    'success' => false,
                    'error' => "Invalid product provided for stock update"
                ];
            }

            if (!is_numeric($quantity) || $quantity <= 0) {
                return [
                    'success' => false,
                    'error' => "Invalid quantity provided for stock update"
                ];
            }

            // Store original stock for logging
            $originalStock = $product->current_stock;

            // Update product stock
            $product->current_stock += $quantity;

            if (!$product->save()) {
                return [
                    'success' => false,
                    'error' => "Failed to update product stock in database"
                ];
            }

            Log::info('Product stock updated for purchase', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'original_stock' => $originalStock,
                'quantity_added' => $quantity,
                'new_stock' => $product->current_stock
            ]);

            return [
                'success' => true,
                'original_stock' => $originalStock,
                'new_stock' => $product->current_stock,
                'quantity_added' => $quantity
            ];
        } catch (\Exception $e) {
            Log::error('Product stock update failed', [
                'product_id' => $product->id ?? 'unknown',
                'quantity' => $quantity,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => "Stock update failed: " . $e->getMessage()
            ];
        }
    }



    private function processSaleLine($transaction, $product, $line)
    {
        try {
            $remainingQtyToDeduct = $line['quantity'];

            // Validate stock availability
            if ($product->current_stock < $remainingQtyToDeduct) {
                return [
                    'success' => false,
                    'error' => "Insufficient stock. Available: {$product->current_stock}, Required: {$remainingQtyToDeduct}"
                ];
            }

            // Get current staff and their categories for discount validation
            $currentUser = Auth::user();
            $currentStaff = null;
            $staffCategories = collect();

            // Safely get staff record
            if ($currentUser) {
                $currentStaff = Staff::where('user_id', $currentUser->id)->first();
                if ($currentStaff) {
                    $staffCategories = $currentStaff->productCategories->pluck('id');
                }
            }

            // FIXED: Enhanced line discount validation
            $requestedDiscount = $line['line_discount'] ?? 0;
            $lineDiscount = 0;

            // Check if user is admin
            $isAdmin = $currentUser && $currentUser->roles->pluck('name')->contains('admin');

            // If discount was requested, validate it properly
            if ($requestedDiscount != 0) {
                // Validate discount is numeric
                if (!is_numeric($requestedDiscount)) {
                    throw new \Exception("Invalid line discount value '{$requestedDiscount}' for product '{$product->name}'. Discount must be a number.");
                }

                if ($isAdmin) {
                    // ADMIN: Can apply line discount to any product
                    $lineDiscount = $requestedDiscount;
                    Log::info('Admin line discount applied', [
                        'product_id' => $product->id,
                        'discount' => $lineDiscount,
                        'user_id' => $currentUser->id
                    ]);
                } elseif ($currentStaff && $staffCategories->contains($product->category_id)) {
                    // STAFF: Only for their assigned categories
                    $lineDiscount = $requestedDiscount;
                    Log::info('Staff line discount applied', [
                        'product_id' => $product->id,
                        'category_id' => $product->category_id,
                        'discount' => $lineDiscount,
                        'staff_id' => $currentStaff->id
                    ]);
                } else {
                    // CRITICAL: Staff trying to apply discount to non-assigned category
                    $categoryName = $product->category ? $product->category->name : 'Unknown Category';
                    throw new \Exception("You cannot apply line discount of {$requestedDiscount} to product '{$product->name}' in '{$categoryName}' category. You can only apply discounts to products in your assigned categories.");
                }
            } else {
                // No discount requested - this is fine
                $lineDiscount = 0;
            }


            Log::info('Processing sale line', [
                'product_id' => $product->id,
                'quantity' => $remainingQtyToDeduct,
                'line_discount' => $lineDiscount,
                'is_admin' => $isAdmin,
                'has_staff' => !is_null($currentStaff),
                'staff_can_discount' => $currentStaff && $staffCategories->contains($product->category_id)
            ]);

            // Get available batches with validation
            $batches = ProductBatch::where('product_id', $product->id)
                ->where('remaining_quantity', '>', 0)
                ->orderBy('batch_date', 'asc')
                ->get();

            if ($batches->isEmpty()) {
                return [
                    'success' => false,
                    'error' => "No available batches found for this product"
                ];
            }

            // Validate total available quantity in batches
            $totalAvailableInBatches = $batches->sum('remaining_quantity');
            if ($totalAvailableInBatches < $remainingQtyToDeduct) {
                return [
                    'success' => false,
                    'error' => "Insufficient batch quantity. Available in batches: {$totalAvailableInBatches}, Required: {$remainingQtyToDeduct}"
                ];
            }

            $processedBatches = [];

            // Process batches using FIFO
            foreach ($batches as $batch) {
                if ($remainingQtyToDeduct <= 0) break;

                $qtyToDeduct = min($batch->remaining_quantity, $remainingQtyToDeduct);

                // Update batch quantity
                $batch->remaining_quantity -= $qtyToDeduct;
                if (!$batch->save()) {
                    return [
                        'success' => false,
                        'error' => "Failed to update batch quantity for batch ID: {$batch->id}"
                    ];
                }

                // Create transaction line with validation - FIXED: Ensure all fields are properly set
                $transactionLineData = [
                    'inventory_transaction_id' => $transaction->id,
                    'product_id' => $product->id,
                    'batch_id' => $batch->id,
                    'quantity' => $qtyToDeduct,
                    'unit_price' => $line['unit_price'] ?? 0,
                    'dealer_price' => $batch->dealer_price ?? 0,
                    'line_total' => $qtyToDeduct * ($line['unit_price'] ?? 0),
                    'line_discount' => $lineDiscount
                ];

                $transactionLine = InventoryTransactionLine::create($transactionLineData);

                if (!$transactionLine || !$transactionLine->id) {
                    return [
                        'success' => false,
                        'error' => "Failed to create transaction line for batch ID: {$batch->id}"
                    ];
                }

                $processedBatches[] = [
                    'batch_id' => $batch->id,
                    'quantity_deducted' => $qtyToDeduct,
                    'transaction_line_id' => $transactionLine->id
                ];

                $remainingQtyToDeduct -= $qtyToDeduct;

                Log::info('Batch processed for sale', [
                    'batch_id' => $batch->id,
                    'quantity_deducted' => $qtyToDeduct,
                    'remaining_in_batch' => $batch->remaining_quantity,
                    'transaction_line_id' => $transactionLine->id
                ]);
            }

            // Validate all quantity was processed
            if ($remainingQtyToDeduct > 0) {
                return [
                    'success' => false,
                    'error' => "Could not process full quantity. Remaining: {$remainingQtyToDeduct}"
                ];
            }

            // Update product stock with validation
            $originalStock = $product->current_stock;
            $product->current_stock -= $line['quantity'];

            if (!$product->save()) {
                return [
                    'success' => false,
                    'error' => "Failed to update product stock"
                ];
            }

            Log::info('Product stock updated for sale', [
                'product_id' => $product->id,
                'original_stock' => $originalStock,
                'quantity_sold' => $line['quantity'],
                'new_stock' => $product->current_stock,
                'batches_processed' => count($processedBatches)
            ]);

            return [
                'success' => true,
                'batches_processed' => $processedBatches,
                'stock_updated' => true
            ];
        } catch (\Exception $e) {
            Log::error('Sale line processing failed', [
                'product_id' => $product->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line_data' => $line ?? 'null'
            ]);

            return [
                'success' => false,
                'error' => "Line processing exception: " . $e->getMessage()
            ];
        }
    }



    /**
     * Finalize transaction with validation
     */
    private function finalizeTransaction()
    {
        try {
            // Commit the database transaction
            DB::commit();

            // Update cache timestamp
            Cache::put('products_last_updated', now()->timestamp);

            Log::info('Transaction finalized successfully', [
                'timestamp' => now()->timestamp,
                'user_id' => Auth::id()
            ]);
        } catch (\Exception $e) {
            Log::error('Transaction finalization failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            throw new \Exception("Transaction finalization failed: " . $e->getMessage());
        }
    }


    /**
     * Enhanced accounting entries creation with method selection
     */
    private function createAccountingEntries($transaction)
    {
        try {
            $narration = $transaction->entry_type === 'sale'
                ? "Sales Transaction - ID {$transaction->id}"
                : "Purchase Transaction - ID {$transaction->id}";

            $journalTransaction = Transaction::create([
                'business_id' => $transaction->business_id,
                'transaction_type' => 'Journal',
                'transaction_date' => $transaction->transaction_date,
                'amount' => $transaction->grand_total,
                'narration' => $narration,
                'created_at' => $transaction->transaction_date
            ]);

            if (!$journalTransaction || !$journalTransaction->id) {
                throw new \Exception('Failed to create journal transaction');
            }

            if ($transaction->entry_type === 'sale') {
                $this->createSaleJournalEntries($journalTransaction, $transaction);
            } else {
                $this->createPurchaseJournalEntries($journalTransaction, $transaction);
            }

            Log::info('Accounting entries created successfully', [
                'transaction_id' => $transaction->id,
                'journal_id' => $journalTransaction->id,
                'entry_type' => $transaction->entry_type,
                'amount' => $transaction->grand_total
            ]);
        } catch (\Exception $e) {
            Log::error('Accounting entries creation failed', [
                'transaction_id' => $transaction->id ?? 'unknown',
                'entry_type' => $transaction->entry_type ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Accounting entries creation failed: " . $e->getMessage());
        }
    }

    /**
     * Creates journal entries for an inventory sale transaction - FIXED VERSION
     */
    private function createSaleJournalEntries($journalTransaction, $transaction)
    {
        try {
            // Get current user and validate
            $currentUser = Auth::user();
            if (!$currentUser) {
                throw new \Exception("No authenticated user found");
            }

            $isAdmin = $currentUser->roles->pluck('name')->contains('admin');

            // Get payment ledger
            $paymentLedger = $this->findPaymentLedger($transaction);
            if (!$paymentLedger) {
                throw new \Exception("Payment ledger not found for transaction");
            }

            // CRITICAL: Get transaction lines with line_discount data
            $transactionLines = InventoryTransactionLine::where('inventory_transaction_id', $transaction->id)
                ->with('product')
                ->get();

            if ($transactionLines->isEmpty()) {
                throw new \Exception("No transaction lines found for accounting entries");
            }

            Log::info('Sale journal entries - transaction lines loaded', [
                'transaction_id' => $transaction->id,
                'lines_count' => $transactionLines->count(),
                'payment_method' => $transaction->payment_method,
                'is_admin' => $isAdmin,
                'line_discount_data' => $transactionLines->map(function ($line) {
                    return [
                        'line_id' => $line->id,
                        'product_id' => $line->product_id,
                        'line_discount' => $line->line_discount ?? 'NULL',
                        'line_total' => $line->line_total
                    ];
                })->toArray()
            ]);

            // Process based on payment method
            if ($transaction->payment_method === 'cash') {
                $this->processCashSaleEntriesFixed($journalTransaction, $transaction, $paymentLedger, $transactionLines, $isAdmin);
            } elseif ($transaction->payment_method === 'credit') {
                $this->processCreditSaleEntries($journalTransaction, $transaction, $transactionLines, $currentUser, $isAdmin);
            } else {
                throw new \Exception("Invalid payment method: {$transaction->payment_method}");
            }

            // Recalculate affected ledger balances
            $this->recalculateAffectedLedgerBalancesForSale($transaction, $paymentLedger, $currentUser, $isAdmin);

            Log::info('Sale journal entries completed successfully', [
                'transaction_id' => $transaction->id,
                'journal_id' => $journalTransaction->id,
                'payment_method' => $transaction->payment_method
            ]);
        } catch (\Exception $e) {
            Log::error('Sale journal entries creation failed', [
                'transaction_id' => $transaction->id ?? 'unknown',
                'journal_id' => $journalTransaction->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception("Sale journal entries failed: " . $e->getMessage());
        }
    }


    /**
     * Recalculate all affected ledger balances using the existing recalcLedgerBalance method
     */
    private function recalculateAffectedLedgerBalancesForSale($transaction, $paymentLedger, $currentUser, $isAdmin)
    {
        try {
            $ledgersToRecalculate = [];

            // Always recalculate payment ledger
            $ledgersToRecalculate[] = [
                'ledger' => $paymentLedger,
                'description' => 'Payment ledger'
            ];

            // Always recalculate customer ledger
            $customerLedger = Ledger::find($transaction->ledger_id);
            if (!$customerLedger) {
                throw new \Exception("Customer ledger not found for balance recalculation. Ledger ID: {$transaction->ledger_id}");
            }
            $ledgersToRecalculate[] = [
                'ledger' => $customerLedger,
                'description' => 'Customer ledger'
            ];

            // For credit sales, recalculate staff ledger balance if applicable
            if ($transaction->payment_method === 'credit' && $currentUser->roles->pluck('name')->contains('staff')) {
                $currentStaff = Staff::where('user_id', $currentUser->id)->first();
                if ($currentStaff) {
                    $staffLedgers = $currentStaff->ledgers()
                        ->where('ledger_type', 'Sales Accounts')
                        ->get();

                    foreach ($staffLedgers as $staffLedger) {
                        $ledgersToRecalculate[] = [
                            'ledger' => $staffLedger,
                            'description' => "Staff ledger (ID: {$staffLedger->id})"
                        ];
                    }
                }
            }

            // For admin cash sales, recalculate sales ledger
            if ($transaction->payment_method === 'cash' && $isAdmin) {
                try {
                    $salesLedger = $this->findDefaultSalesLedger($transaction->business_id);
                    $ledgersToRecalculate[] = [
                        'ledger' => $salesLedger,
                        'description' => 'Default sales ledger'
                    ];
                } catch (\Exception $e) {
                    Log::warning('Could not find sales ledger for recalculation', [
                        'business_id' => $transaction->business_id,
                        'error' => $e->getMessage()
                    ]);
                    // Don't throw here as this is not critical to transaction success
                }
            }

            // Recalculate all identified ledgers using the existing method
            foreach ($ledgersToRecalculate as $ledgerInfo) {
                try {
                    $this->recalcLedgerBalance($ledgerInfo['ledger']);

                    Log::info('Ledger balance recalculated successfully', [
                        'ledger_id' => $ledgerInfo['ledger']->id,
                        'ledger_name' => $ledgerInfo['ledger']->name,
                        'description' => $ledgerInfo['description'],
                        'new_balance' => $ledgerInfo['ledger']->current_balance,
                        'transaction_id' => $transaction->id
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception("Failed to recalculate {$ledgerInfo['description']} (ID: {$ledgerInfo['ledger']->id}): " . $e->getMessage());
                }
            }

            Log::info('All affected ledger balances recalculated successfully', [
                'transaction_id' => $transaction->id,
                'payment_method' => $transaction->payment_method,
                'ledgers_recalculated' => count($ledgersToRecalculate)
            ]);
        } catch (\Exception $e) {
            Log::error('Ledger balance recalculation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Ledger balance recalculation failed: " . $e->getMessage());
        }
    }

    /**
     * Process cash sale entries with line discount handling - FIXED VERSION
     */
    private function processCashSaleEntriesFixed($journalTransaction, $transaction, $paymentLedger, $transactionLines, $isAdmin)
    {
        DB::beginTransaction();

        try {
            Log::info('CASH SALE DEBUG - Starting processing', [
                'transaction_id' => $transaction->id,
                'is_admin' => $isAdmin,
                'lines_count' => $transactionLines->count(),
                'transaction_lines_debug' => $transactionLines->map(function ($line) {
                    return [
                        'id' => $line->id,
                        'product_id' => $line->product_id,
                        'line_discount' => $line->line_discount,
                        'line_total' => $line->line_total,
                        'has_line_discount_field' => array_key_exists('line_discount', $line->getAttributes())
                    ];
                })->toArray()
            ]);

            // Step 1: Create cash debit entry
            $cashDebitLine = TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $paymentLedger->id,
                'debit_amount' => $transaction->grand_total,
                'credit_amount' => 0,
                'narration' => 'Cash Sales - Invoice #' . $transaction->id,
                'created_at' => $transaction->transaction_date
            ]);

            if (!$cashDebitLine || !$cashDebitLine->id) {
                throw new \Exception("Failed to create cash debit entry");
            }

            // Step 2: Process based on user role
            if ($isAdmin) {
                // ADMIN: Credit default sales ledger + handle line discounts
                $salesLedger = $this->findDefaultSalesLedger($transaction->business_id);

                // Create sales credit entry
                $salesCreditLine = TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $salesLedger->id,
                    'debit_amount' => 0,
                    'credit_amount' => $transaction->grand_total,
                    'narration' => 'Sales Revenue - Invoice #' . $transaction->id,
                    'created_at' => $transaction->transaction_date
                ]);

                if (!$salesCreditLine || !$salesCreditLine->id) {
                    throw new \Exception("Failed to create sales revenue entry");
                }

                // Calculate total line discount (SAME AS CREDIT SALES)
                $totalLineDiscount = $transactionLines->sum('line_discount');

                Log::info('ADMIN CASH SALE - Line discount calculation', [
                    'transaction_id' => $transaction->id,
                    'total_line_discount' => $totalLineDiscount,
                    'individual_discounts' => $transactionLines->map(function ($line) {
                        return [
                            'line_id' => $line->id,
                            'line_discount' => $line->line_discount
                        ];
                    })->toArray()
                ]);

                // Create line discount adjustment entry (MANDATORY for admin)
                if ($totalLineDiscount != 0) {
                    $discountLine = TransactionLine::create([
                        'transaction_id' => $journalTransaction->id,
                        'ledger_id' => $salesLedger->id, // Default sales ledger for ALL categories
                        'debit_amount' => $totalLineDiscount > 0 ? abs($totalLineDiscount) : 0,
                        'credit_amount' => $totalLineDiscount < 0 ? abs($totalLineDiscount) : 0,
                        'narration' => 'Line Discount Adjustment - All Categories - Invoice #' . $transaction->id,
                        'created_at' => $transaction->transaction_date
                    ]);

                    if (!$discountLine || !$discountLine->id) {
                        throw new \Exception("CRITICAL: Failed to create line discount entry for admin cash sale");
                    }

                    Log::info('ADMIN CASH SALE - Line discount entry created', [
                        'transaction_line_id' => $discountLine->id,
                        'ledger_id' => $salesLedger->id,
                        'discount_amount' => $totalLineDiscount,
                        'entry_type' => $totalLineDiscount > 0 ? 'debit' : 'credit'
                    ]);
                } else {
                    Log::info('ADMIN CASH SALE - No line discounts to process', [
                        'transaction_id' => $transaction->id,
                        'total_line_discount' => $totalLineDiscount
                    ]);
                }
            } else {
                // STAFF: Credit customer ledger
                $customerCreditLine = TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $transaction->ledger_id,
                    'debit_amount' => 0,
                    'credit_amount' => $transaction->grand_total,
                    'narration' => 'Cash Sales Receivable - Invoice #' . $transaction->id,
                    'created_at' => $transaction->transaction_date
                ]);

                if (!$customerCreditLine || !$customerCreditLine->id) {
                    throw new \Exception("Failed to create customer credit entry");
                }

                // CRITICAL FIX: Handle staff line discounts in cash sales
                $totalLineDiscount = $transactionLines->sum('line_discount');

                if ($totalLineDiscount != 0) {
                    // Get current staff and their sales ledger
                    $currentUser = Auth::user();
                    $currentStaff = Staff::where('user_id', $currentUser->id)->first();

                    if (!$currentStaff) {
                        throw new \Exception("Staff record required for discount accounting in cash sale");
                    }

                    $staffLedgers = $currentStaff->ledgers()->where('ledger_type', 'Sales Accounts')->get();

                    if ($staffLedgers->isEmpty()) {
                        throw new \Exception("Staff sales ledger required for discount accounting in cash sale");
                    }

                    $staffLedger = $staffLedgers->first();

                    // Create discount adjustment entry for staff cash sale
                    $discountLine = TransactionLine::create([
                        'transaction_id' => $journalTransaction->id,
                        'ledger_id' => $staffLedger->id,
                        'debit_amount' => $totalLineDiscount > 0 ? abs($totalLineDiscount) : 0,
                        'credit_amount' => $totalLineDiscount < 0 ? abs($totalLineDiscount) : 0,
                        'narration' => 'Cash Sale Line Discount Adjustment - Invoice #' . $transaction->id,
                        'created_at' => $transaction->transaction_date
                    ]);

                    if (!$discountLine || !$discountLine->id) {
                        throw new \Exception("CRITICAL: Failed to create staff discount entry for cash sale");
                    }

                    Log::info('Staff cash sale discount entry created', [
                        'transaction_id' => $transaction->id,
                        'staff_ledger_id' => $staffLedger->id,
                        'discount_amount' => $totalLineDiscount,
                        'transaction_line_id' => $discountLine->id
                    ]);
                }
            }

            DB::commit();

            Log::info('Cash sale entries completed successfully', [
                'transaction_id' => $transaction->id,
                'is_admin' => $isAdmin,
                'entries_created' => $isAdmin ? 'cash_debit + sales_credit + discount_adjustment' : 'cash_debit + customer_credit'
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Cash sale entries failed - ROLLED BACK', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception("Cash sale entries failed: " . $e->getMessage());
        }
    }


    /**
     * Process credit sale accounting entries with full validation
     */
    private function processCreditSaleEntries($journalTransaction, $transaction, $transactionLines, $currentUser, $isAdmin)
    {
        try {
            // Credit Sale: Debit Customer Ledger
            $customerDebitLine = TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $transaction->ledger_id,
                'debit_amount' => $transaction->grand_total,
                'credit_amount' => 0,
                'narration' => 'Credit Sales Receivable'
            ]);

            if (!$customerDebitLine || !$customerDebitLine->id) {
                throw new \Exception("Failed to create customer debit entry");
            }

            Log::info('Customer debit entry created', [
                'ledger_id' => $transaction->ledger_id,
                'amount' => $transaction->grand_total,
                'transaction_line_id' => $customerDebitLine->id
            ]);

            // Calculate totals by product category with validation
            $categoryTotals = [];
            foreach ($transactionLines as $line) {
                if (!$line->product || !$line->product->category_id) {
                    throw new \Exception("Product or category missing for transaction line ID: {$line->id}");
                }

                $categoryId = $line->product->category_id;
                if (!isset($categoryTotals[$categoryId])) {
                    $categoryTotals[$categoryId] = 0;
                }
                $categoryTotals[$categoryId] += $line->line_total;
            }

            if (empty($categoryTotals)) {
                throw new \Exception("No valid categories found for credit sale processing");
            }

            // Process staff entries for credit sales
            if ($currentUser->roles->pluck('name')->contains('staff')) {
                $this->processStaffCreditSaleEntries($journalTransaction, $transaction, $transactionLines, $categoryTotals, $currentUser);
            }

            Log::info('Credit sale entries processed successfully', [
                'categories_processed' => count($categoryTotals),
                'total_amount' => array_sum($categoryTotals)
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Credit sale entries processing failed: " . $e->getMessage());
        }
    }

    /**
     * Process staff-specific credit sale entries with full validation
     */
    private function processStaffCreditSaleEntries($journalTransaction, $transaction, $transactionLines, $categoryTotals, $currentUser)
    {
        try {
            // Get and validate current staff
            $currentStaff = Staff::where('user_id', $currentUser->id)->first();
            if (!$currentStaff) {
                throw new \Exception("Staff record not found for current user");
            }

            // Get the staff's assigned categories
            $staffCategories = $currentStaff->productCategories->pluck('id')->toArray();
            if (empty($staffCategories)) {
                throw new \Exception("Staff has no assigned categories");
            }

            // Get staff's ledgers that are of type 'Sales Accounts'
            $staffLedgers = $currentStaff->ledgers()
                ->where('ledger_type', 'Sales Accounts')
                ->get();

            if ($staffLedgers->isEmpty()) {
                throw new \Exception("Staff has no Sales Accounts ledger assigned");
            }

            $staffLedger = $staffLedgers->first();

            // Verify ledger exists in database
            $ledgerExists = Ledger::find($staffLedger->id);
            if (!$ledgerExists) {
                throw new \Exception("Staff ledger does not exist in database. Ledger ID: {$staffLedger->id}");
            }

            // Calculate total for staff's categories
            $staffTotal = 0;
            $staffProducts = false;

            foreach ($categoryTotals as $categoryId => $total) {
                if (in_array($categoryId, $staffCategories)) {
                    $staffTotal += $total;
                    $staffProducts = true;
                }
            }

            // Only create entries if staff has products in the transaction
            if (!$staffProducts || $staffTotal <= 0) {
                Log::info('No staff products in transaction', [
                    'staff_id' => $currentStaff->id,
                    'staff_categories' => $staffCategories,
                    'transaction_categories' => array_keys($categoryTotals)
                ]);
                return;
            }

            // Credit staff ledger with total amount for their categories
            $staffCreditLine = TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $staffLedger->id,
                'debit_amount' => 0,
                'credit_amount' => $staffTotal,
                'narration' => 'Credit Sales Invoice #' . $transaction->id
            ]);

            if (!$staffCreditLine || !$staffCreditLine->id) {
                throw new \Exception("Failed to create staff credit entry");
            }

            Log::info('Staff credit entry created', [
                'staff_id' => $currentStaff->id,
                'staff_name' => $currentStaff->user->name,
                'ledger_id' => $staffLedger->id,
                'amount' => $staffTotal,
                'transaction_line_id' => $staffCreditLine->id
            ]);

            // Handle line discounts if any
            $lineDiscountTotal = $transactionLines
                ->filter(function ($line) use ($staffCategories) {
                    return in_array($line->product->category_id, $staffCategories);
                })
                ->sum('line_discount');

            if ($lineDiscountTotal != 0) {
                $discountLine = TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $staffLedger->id,
                    'debit_amount' => $lineDiscountTotal > 0 ? abs($lineDiscountTotal) : 0,
                    'credit_amount' => $lineDiscountTotal < 0 ? abs($lineDiscountTotal) : 0,
                    'narration' => 'Price Adjustment - ' . $staffLedger->name . ' - Invoice #' . $transaction->id
                ]);

                if (!$discountLine || !$discountLine->id) {
                    throw new \Exception("Failed to create discount adjustment entry");
                }

                Log::info('Discount adjustment entry created', [
                    'staff_id' => $currentStaff->id,
                    'discount_amount' => $lineDiscountTotal,
                    'transaction_line_id' => $discountLine->id
                ]);
            }
        } catch (\Exception $e) {
            throw new \Exception("Staff credit sale entries failed: " . $e->getMessage());
        }
    }


    /**
     * Create purchase journal entries with full atomicity
     */
    private function createPurchaseJournalEntries($journalTransaction, $transaction)
    {
        try {
            // Step 1: Validate inputs
            if (!$journalTransaction || !$journalTransaction->id) {
                throw new \Exception("Invalid journal transaction provided");
            }

            if (!$transaction || !$transaction->id) {
                throw new \Exception("Invalid inventory transaction provided");
            }

            // Step 2: Find required ledgers
            $purchaseLedger = Ledger::where('business_id', $transaction->business_id)
                ->where('ledger_type', 'Purchase Accounts')
                ->first();

            if (!$purchaseLedger) {
                throw new \Exception('Purchase Accounts ledger not found for this business');
            }

            Log::info('Purchase journal entries validation completed', [
                'transaction_id' => $transaction->id,
                'journal_id' => $journalTransaction->id,
                'payment_method' => $transaction->payment_method,
                'purchase_ledger_id' => $purchaseLedger->id
            ]);

            // Step 3: Process based on payment method
            if ($transaction->payment_method === 'cash') {
                $this->processCashPurchaseEntries($journalTransaction, $transaction, $purchaseLedger);
            } elseif ($transaction->payment_method === 'credit') {
                $this->processCreditPurchaseEntries($journalTransaction, $transaction, $purchaseLedger);
            } else {
                throw new \Exception("Invalid payment method: {$transaction->payment_method}");
            }

            // Step 4: Recalculate affected ledger balances
            $this->recalculateAffectedLedgerBalancesForPurchase($transaction, $purchaseLedger);

            Log::info('Purchase journal entries completed successfully', [
                'transaction_id' => $transaction->id,
                'journal_id' => $journalTransaction->id,
                'payment_method' => $transaction->payment_method
            ]);
        } catch (\Exception $e) {
            Log::error('Purchase journal entries creation failed', [
                'transaction_id' => $transaction->id ?? 'unknown',
                'journal_id' => $journalTransaction->id ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw new \Exception("Purchase journal entries failed: " . $e->getMessage());
        }
    }

    /**
     * Process cash purchase accounting entries
     */
    private function processCashPurchaseEntries($journalTransaction, $transaction, $purchaseLedger)
    {
        try {
            // Find Cash Ledger
            $cashLedger = Ledger::where([
                'business_id' => $transaction->business_id,
                'ledger_type' => 'Cash-in-Hand'
            ])->first();

            if (!$cashLedger) {
                throw new \Exception('Cash ledger not found for this business');
            }

            // Debit Purchase Account
            $purchaseDebitLine = TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $purchaseLedger->id,
                'debit_amount' => $transaction->grand_total,
                'credit_amount' => 0,
                'narration' => 'Cash Purchase',
                'created_at' => $transaction->transaction_date
            ]);

            if (!$purchaseDebitLine || !$purchaseDebitLine->id) {
                throw new \Exception('Failed to create purchase debit entry');
            }

            // Credit Cash
            $cashCreditLine = TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $cashLedger->id,
                'debit_amount' => 0,
                'credit_amount' => $transaction->grand_total,
                'narration' => 'Cash Purchase',
                'created_at' => $transaction->transaction_date
            ]);

            if (!$cashCreditLine || !$cashCreditLine->id) {
                throw new \Exception('Failed to create cash credit entry');
            }

            Log::info('Cash purchase entries created', [
                'transaction_id' => $transaction->id,
                'purchase_debit_line_id' => $purchaseDebitLine->id,
                'cash_credit_line_id' => $cashCreditLine->id,
                'amount' => $transaction->grand_total
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Cash purchase entries failed: " . $e->getMessage());
        }
    }

    /**
     * Process credit purchase accounting entries
     */
    private function processCreditPurchaseEntries($journalTransaction, $transaction, $purchaseLedger)
    {
        try {
            // Get transaction lines with product categories
            $transactionLines = InventoryTransactionLine::where('inventory_transaction_id', $transaction->id)
                ->with(['product.category'])
                ->get();

            if ($transactionLines->isEmpty()) {
                throw new \Exception('No transaction lines found for credit purchase entries');
            }

            // Debit Purchase Account
            $purchaseDebitLine = TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $purchaseLedger->id,
                'debit_amount' => $transaction->grand_total,
                'credit_amount' => 0,
                'narration' => 'Credit Purchase',
                'created_at' => $transaction->transaction_date
            ]);

            if (!$purchaseDebitLine || !$purchaseDebitLine->id) {
                throw new \Exception('Failed to create purchase debit entry');
            }

            // Group by category and create supplier entries
            $categoryTotals = [];
            foreach ($transactionLines as $line) {
                if (!$line->product || !$line->product->category) {
                    throw new \Exception("Product or category not found for transaction line ID: {$line->id}");
                }

                $categoryId = $line->product->category_id;
                if (!isset($categoryTotals[$categoryId])) {
                    $categoryTotals[$categoryId] = [
                        'total' => 0,
                        'category' => $line->product->category
                    ];
                }
                $categoryTotals[$categoryId]['total'] += $line->line_total;
            }

            // Credit Supplier Ledgers based on category
            $supplierEntries = [];
            foreach ($categoryTotals as $categoryId => $data) {
                $category = $data['category'];

                if (!$category || !$category->ledger_id) {
                    throw new \Exception("Category '{$category->name}' does not have an associated supplier ledger");
                }

                // Verify supplier ledger exists
                $supplierLedger = Ledger::find($category->ledger_id);
                if (!$supplierLedger) {
                    throw new \Exception("Supplier ledger not found for category '{$category->name}'");
                }

                $supplierCreditLine = TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $category->ledger_id,
                    'debit_amount' => 0,
                    'credit_amount' => $data['total'],
                    'narration' => "Credit Purchase: {$category->name}",
                    'created_at' => $transaction->transaction_date
                ]);

                if (!$supplierCreditLine || !$supplierCreditLine->id) {
                    throw new \Exception("Failed to create supplier credit entry for category '{$category->name}'");
                }

                $supplierEntries[] = [
                    'category_id' => $categoryId,
                    'category_name' => $category->name,
                    'ledger_id' => $category->ledger_id,
                    'amount' => $data['total'],
                    'transaction_line_id' => $supplierCreditLine->id
                ];
            }

            Log::info('Credit purchase entries created', [
                'transaction_id' => $transaction->id,
                'purchase_debit_line_id' => $purchaseDebitLine->id,
                'supplier_entries' => $supplierEntries,
                'total_amount' => $transaction->grand_total
            ]);
        } catch (\Exception $e) {
            throw new \Exception("Credit purchase entries failed: " . $e->getMessage());
        }
    }

    /**
     * Recalculate affected ledger balances for purchase transactions
     */
    private function recalculateAffectedLedgerBalancesForPurchase($transaction, $purchaseLedger)
    {
        try {
            $ledgersToRecalculate = [];

            // Always recalculate purchase ledger
            $ledgersToRecalculate[] = [
                'ledger' => $purchaseLedger,
                'description' => 'Purchase accounts ledger'
            ];

            if ($transaction->payment_method === 'cash') {
                // Recalculate cash ledger for cash purchases
                $cashLedger = Ledger::where([
                    'business_id' => $transaction->business_id,
                    'ledger_type' => 'Cash-in-Hand'
                ])->first();

                if (!$cashLedger) {
                    throw new \Exception('Cash ledger not found for balance recalculation');
                }

                $ledgersToRecalculate[] = [
                    'ledger' => $cashLedger,
                    'description' => 'Cash ledger'
                ];
            } else {
                // Recalculate supplier ledgers for credit purchases
                $transactionLines = InventoryTransactionLine::where('inventory_transaction_id', $transaction->id)
                    ->with(['product.category'])
                    ->get();

                $processedLedgers = [];
                foreach ($transactionLines as $line) {
                    $category = $line->product->category;
                    if ($category && $category->ledger_id && !in_array($category->ledger_id, $processedLedgers)) {
                        $supplierLedger = Ledger::find($category->ledger_id);
                        if ($supplierLedger) {
                            $ledgersToRecalculate[] = [
                                'ledger' => $supplierLedger,
                                'description' => "Supplier ledger for category '{$category->name}'"
                            ];
                            $processedLedgers[] = $category->ledger_id;
                        }
                    }
                }
            }

            // Recalculate all identified ledgers
            foreach ($ledgersToRecalculate as $ledgerInfo) {
                try {
                    $this->recalcLedgerBalance($ledgerInfo['ledger']);

                    Log::info('Purchase ledger balance recalculated', [
                        'ledger_id' => $ledgerInfo['ledger']->id,
                        'ledger_name' => $ledgerInfo['ledger']->name,
                        'description' => $ledgerInfo['description'],
                        'new_balance' => $ledgerInfo['ledger']->current_balance,
                        'transaction_id' => $transaction->id
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception("Failed to recalculate {$ledgerInfo['description']} (ID: {$ledgerInfo['ledger']->id}): " . $e->getMessage());
                }
            }

            Log::info('All purchase ledger balances recalculated successfully', [
                'transaction_id' => $transaction->id,
                'payment_method' => $transaction->payment_method,
                'ledgers_recalculated' => count($ledgersToRecalculate)
            ]);
        } catch (\Exception $e) {
            Log::error('Purchase ledger balance recalculation failed', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Purchase ledger balance recalculation failed: " . $e->getMessage());
        }
    }


    /**
     * Find payment ledger with enhanced validation
     */
    private function findPaymentLedger($transaction)
    {
        try {
            // Validate transaction
            if (!$transaction || !$transaction->business_id) {
                throw new \Exception('Invalid transaction provided for payment ledger lookup');
            }

            $ledger = null;

            // For cash transactions
            if ($transaction->payment_method === 'cash') {
                $ledger = Ledger::where([
                    'business_id' => $transaction->business_id,
                    'ledger_type' => 'Cash-in-Hand'
                ])->first();

                if (!$ledger) {
                    throw new \Exception('Cash ledger not found for this business');
                }
            }
            // For credit transactions  
            elseif ($transaction->payment_method === 'credit') {
                $ledger = Ledger::where([
                    'business_id' => $transaction->business_id,
                    'ledger_type' => 'Sundry Debtors (Customer)'
                ])->first();

                if (!$ledger) {
                    throw new \Exception('Customer ledger not found for this business');
                }
            } else {
                throw new \Exception('Invalid payment method: ' . $transaction->payment_method);
            }

            Log::info('Payment ledger found', [
                'transaction_id' => $transaction->id,
                'payment_method' => $transaction->payment_method,
                'ledger_id' => $ledger->id,
                'ledger_type' => $ledger->ledger_type
            ]);

            return $ledger;
        } catch (\Exception $e) {
            Log::error('Payment ledger lookup failed', [
                'transaction_id' => $transaction->id ?? 'unknown',
                'payment_method' => $transaction->payment_method ?? 'unknown',
                'business_id' => $transaction->business_id ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Payment ledger lookup failed: " . $e->getMessage());
        }
    }

    /**
     * Enhanced business ID retrieval with comprehensive validation
     */
    private function getBusinessId()
    {
        try {
            $currentUser = Auth::user();
            if (!$currentUser) {
                throw new \Exception('No authenticated user found');
            }

            $businessId = null;

            // Check if user is staff
            if ($currentUser->roles->contains('name', 'staff')) {
                $staff = Staff::where('user_id', $currentUser->id)->first();
                if (!$staff) {
                    throw new \Exception('Staff record not found for current user');
                }
                $businessId = $staff->business_id;
            }
            // Check if user is admin
            elseif ($currentUser->roles->contains('name', 'admin')) {
                $admin = BusinessAdmin::where('user_id', $currentUser->id)->first();
                if (!$admin) {
                    throw new \Exception('Business admin record not found for current user');
                }
                $businessId = $admin->business_id;
            } else {
                throw new \Exception('User does not have staff or admin role');
            }

            if (!$businessId) {
                throw new \Exception('User is not associated with any business');
            }

            // Verify business exists and is active
            $business = DB::table('businesses')->where('id', $businessId)->first();
            if (!$business) {
                throw new \Exception('Associated business does not exist');
            }

            // Optional: Check if business is active
            if (isset($business->status) && $business->status !== 'active') {
                throw new \Exception('Associated business is not active');
            }

            return $businessId;
        } catch (\Exception $e) {
            Log::error('Enhanced business ID retrieval failed', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception("Business ID retrieval failed: " . $e->getMessage());
        }
    }

    /**
     * Find the default Sales Account ledger for a business with validation
     * 
     * @param int $businessId
     * @return \App\Models\Ledger
     * @throws \Exception
     */
    private function findDefaultSalesLedger($businessId)
    {
        try {
            // Validate business ID
            if (!$businessId) {
                throw new \Exception('Invalid business ID provided');
            }

            // First try to find a Sales Account ledger with status = 'default'
            $salesLedger = Ledger::where('business_id', $businessId)
                ->where('ledger_type', 'Sales Accounts')
                ->where('status', 'default')
                ->first();

            if (!$salesLedger) {
                // If no default status ledger, get the first Sales Account ledger
                $salesLedger = Ledger::where('business_id', $businessId)
                    ->where('ledger_type', 'Sales Accounts')
                    ->first();
            }

            if (!$salesLedger) {
                throw new \Exception('No Sales Account ledger found for this business');
            }

            Log::info('Default sales ledger found', [
                'business_id' => $businessId,
                'ledger_id' => $salesLedger->id,
                'ledger_name' => $salesLedger->name
            ]);

            return $salesLedger;
        } catch (\Exception $e) {
            Log::error('Failed to find default sales ledger', [
                'business_id' => $businessId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Sales ledger lookup failed: " . $e->getMessage());
        }
    }


    // REPORT STARTS HERE 
    public function salesSummary(Request $request)
    {
        try {
            $currentAdmin = BusinessAdmin::where('user_id', Auth::id())
                ->with('business')
                ->first();

            if (!$currentAdmin) {
                return redirect()->back()->with('error', 'Business admin record not found.');
            }

            $business = $currentAdmin->business;

            // Enhanced date handling with automatic selection
            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');

            // Auto-select dates if not provided
            if (!$startDate || !$endDate) {
                // Default to current month
                $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
            }

            // Validate date format and range
            try {
                $startDateCarbon = Carbon::createFromFormat('Y-m-d', $startDate);
                $endDateCarbon = Carbon::createFromFormat('Y-m-d', $endDate);

                // Ensure end date is not before start date
                if ($endDateCarbon->lt($startDateCarbon)) {
                    $endDate = $startDate;
                }

                // Limit date range to prevent performance issues (max 1 year)
                if ($startDateCarbon->diffInDays($endDateCarbon) > 365) {
                    $endDate = $startDateCarbon->addYear()->format('Y-m-d');
                }
            } catch (\Exception $e) {
                // Fallback to current month if date parsing fails
                $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
                $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
            }

            // Optimized query with eager loading and caching
            $cacheKey = "sales_summary_{$currentAdmin->business_id}_{$startDate}_{$endDate}";

            $categories = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($currentAdmin, $startDate, $endDate) {
                return ProductCategory::with(['products' => function ($query) use ($startDate, $endDate) {
                    $query->whereHas('inventoryTransactionLines', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                            ->whereHas('inventoryTransaction', function ($t) {
                                $t->where('entry_type', 'sale')
                                    ->where('payment_method', 'credit');
                            });
                    });
                }, 'products.inventoryTransactionLines' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->whereHas('inventoryTransaction', function ($t) {
                            $t->where('entry_type', 'sale')
                                ->where('payment_method', 'credit');
                        })
                        ->with('inventoryTransaction');
                }])
                    ->where('business_id', $currentAdmin->business_id)
                    ->get()
                    ->map(function ($category) {
                        // Only process categories that have products with sales
                        $productsWithSales = $category->products->filter(function ($product) {
                            return $product->inventoryTransactionLines->isNotEmpty();
                        });

                        if ($productsWithSales->isEmpty()) {
                            return null;
                        }

                        $invoices = $productsWithSales->flatMap(function ($product) {
                            return $product->inventoryTransactionLines->pluck('inventory_transaction_id');
                        })->unique()->count();

                        $totalSales = $productsWithSales->sum(function ($product) {
                            return $product->inventoryTransactionLines->sum('line_total');
                        });

                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'products_count' => $productsWithSales->count(),
                            'total_sales' => $totalSales,
                            'invoices_count' => $invoices
                        ];
                    })
                    ->filter() // Remove null entries
                    ->values(); // Reset array keys
            });

            $totalSales = $categories->sum('total_sales');
            $totalInvoices = $categories->sum('invoices_count');

            // Add date range info for better UX
            $dateRangeInfo = [
                'start_formatted' => Carbon::parse($startDate)->format('M d, Y'),
                'end_formatted' => Carbon::parse($endDate)->format('M d, Y'),
                'days_count' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1,
                'is_today' => $startDate === $endDate && $startDate === Carbon::today()->format('Y-m-d'),
                'is_current_month' => $startDate === Carbon::now()->startOfMonth()->format('Y-m-d') &&
                    $endDate === Carbon::now()->endOfMonth()->format('Y-m-d')
            ];

            return view('admin.inventory.sales-summary.index', compact(
                'categories',
                'business',
                'totalSales',
                'totalInvoices',
                'startDate',
                'endDate',
                'dateRangeInfo'
            ));
        } catch (\Exception $e) {
            Log::error('Sales Summary Error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'An error occurred while loading sales summary. Please try again.');
        }
    }


    public function categorySalesSummary(Request $request, ProductCategory $category)
    {
        try {
            $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->with('business')->first();
            if (!$currentAdmin || $category->business_id !== $currentAdmin->business_id) {
                return response()->json(['error' => 'Category not found'], 404);
            }

            $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->get('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
            $staffFilter = $request->get('staff_id');

            // Get assigned staff with 'staff' role only
            $assignedStaff = Staff::whereHas('productCategories', function ($query) use ($category) {
                $query->where('product_categories.id', $category->id);
            })
                ->whereHas('user.roles', function ($query) {
                    $query->where('name', 'staff');
                })
                ->where('business_id', $currentAdmin->business_id)
                ->with(['user.roles'])
                ->get();

            // Get products sold in this category with contributor-based filtering
            $query = Product::where('category_id', $category->id)
                ->with(['inventoryTransactionLines' => function ($query) use ($startDate, $endDate, $staffFilter) {
                    $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->whereHas('inventoryTransaction', function ($q) {
                            $q->where('entry_type', 'sale')->where('payment_method', 'credit');
                        })
                        ->when($staffFilter, function ($query) use ($staffFilter) {
                            // Filter by actual product contributors, not transaction creators
                            $query->whereExists(function ($subQuery) use ($staffFilter) {
                                $subQuery->select(DB::raw(1))
                                    ->from('inventory_transaction_contributors')
                                    ->whereColumn('inventory_transaction_contributors.transaction_id', 'inventory_transaction_lines.inventory_transaction_id')
                                    ->whereColumn('inventory_transaction_contributors.product_id', 'inventory_transaction_lines.product_id')
                                    ->where('inventory_transaction_contributors.staff_id', $staffFilter);
                            });
                        })
                        ->with(['batch', 'inventoryTransaction']);
                }]);

            $products = $query->get()
                ->filter(function ($product) {
                    return $product->inventoryTransactionLines->isNotEmpty();
                })
                ->map(function ($product) use ($staffFilter) {
                    // Get all relevant transaction lines for this product
                    $relevantLines = $product->inventoryTransactionLines;

                    // If staff filter is applied, ensure we only get lines from products contributed by this staff
                    if ($staffFilter) {
                        $relevantLines = $product->inventoryTransactionLines->filter(function ($line) use ($staffFilter) {
                            // Check if this specific staff contributed to this specific product in this transaction
                            return DB::table('inventory_transaction_contributors')
                                ->where('transaction_id', $line->inventory_transaction_id)
                                ->where('product_id', $line->product_id)
                                ->where('staff_id', $staffFilter)
                                ->exists();
                        });
                    }

                    $totalQty = $relevantLines->sum('quantity');
                    $totalAmount = $relevantLines->sum('line_total');
                    $avgPrice = 0;

                    if ($totalQty > 0) {
                        $avgPrice = $relevantLines->sum(function ($line) {
                            return ($line->batch ? $line->batch->trade_price : $line->unit_price) * $line->quantity;
                        }) / $totalQty;
                    }

                    return [
                        'name' => $product->name,
                        'quantity' => $totalQty,
                        'unit_price' => round($avgPrice, 2),
                        'total' => $totalAmount
                    ];
                })
                ->filter(function ($product) {
                    return $product['quantity'] > 0; // Only include products with actual sales
                })
                ->sortByDesc('quantity')
                ->values();

            // Get selected staff if filter applied
            $selectedStaff = null;
            if ($staffFilter) {
                $selectedStaff = $assignedStaff->where('id', $staffFilter)->first();
                if (!$selectedStaff || !$selectedStaff->user->roles->contains('name', 'staff')) {
                    return redirect()->route('admin.inventory.category-sales-summary', $category->id)
                        ->with('error', 'Selected user does not have staff role or is not assigned to this category.')
                        ->withInput(['start_date' => $startDate, 'end_date' => $endDate]);
                }
            }

            Log::info('Category sales summary with contributor-based filtering', [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'staff_filter' => $staffFilter,
                'selected_staff' => $selectedStaff ? $selectedStaff->user->name : 'All',
                'total_assigned_staff' => $assignedStaff->count(),
                'products_found' => $products->count(),
                'date_range' => ['start' => $startDate, 'end' => $endDate],
                'filtering_method' => 'contributor_based'
            ]);

            return view('admin.inventory.sales-summary.category', compact(
                'products',
                'category',
                'startDate',
                'endDate',
                'assignedStaff',
                'selectedStaff',
                'staffFilter'
            ) + ['business' => $currentAdmin->business]);
        } catch (\Exception $e) {
            Log::error('Category sales summary error', [
                'error' => $e->getMessage(),
                'category_id' => $category->id ?? null,
                'staff_filter' => $staffFilter ?? null,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }




    public function categoryDamageSummary(Request $request, ProductCategory $category)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())
            ->with('business')
            ->first();

        $business = $currentAdmin->business;

        $startDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        $products = Product::with(['damageTransactionLines' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        }])
            ->where('category_id', $category->id)
            ->whereHas('damageTransactionLines', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
            })
            ->get()
            ->map(function ($product) {
                $totalQty = $product->damageTransactionLines->sum('quantity');
                $totalAmount = $product->damageTransactionLines->sum('total_value');

                return [
                    'name' => $product->name,
                    'quantity' => $totalQty,
                    'unit_price' => $product->trade_price,
                    'total' => $totalAmount
                ];
            });

        return view('admin.inventory.damage-summary.category', compact('products', 'category', 'business', 'startDate', 'endDate'));
    }


    public function salesReturnSummary(Request $request)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())
            ->with('business')
            ->first();

        $business = $currentAdmin->business;

        $startDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        // Query ReturnedProduct table using return_date instead of created_at
        $categories = ProductCategory::with(['products.returnedProducts' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('return_date', [$startDate, $endDate]);
        }])
            ->where('business_id', $currentAdmin->business_id)
            ->get()
            ->map(function ($category) {
                $invoices = $category->products->flatMap(function ($product) {
                    return $product->returnedProducts->pluck('inventory_transaction_id');
                })->unique()->count();

                $totalReturn = $category->products->sum(function ($product) {
                    return $product->returnedProducts->sum('total_amount');
                });

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'products_count' => $category->products->count(),
                    'total_return' => $totalReturn,
                    'invoices_count' => $invoices
                ];
            });

        $totalReturn = $categories->sum('total_return');
        $totalInvoices = $categories->sum('invoices_count');

        return view('admin.inventory.sales-return.index', compact(
            'categories',
            'business',
            'totalReturn',
            'totalInvoices',
            'startDate',
            'endDate'
        ));
    }

    public function categorySalesReturnSummary(Request $request, ProductCategory $category)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())
            ->with('business')
            ->first();

        $business = $currentAdmin->business;

        $startDate = $request->get('start_date', Carbon::today()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->format('Y-m-d'));

        $products = Product::with(['returnedProducts' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('return_date', [$startDate, $endDate]);
        }])
            ->where('category_id', $category->id)
            ->whereHas('returnedProducts', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('return_date', [$startDate, $endDate]);
            })
            ->get()
            ->map(function ($product) {
                $totalQty = $product->returnedProducts->sum('quantity');
                $totalAmount = $product->returnedProducts->sum('total_amount');

                return [
                    'name' => $product->name,
                    'quantity' => $totalQty,
                    'unit_price' => $product->trade_price,
                    'total' => $totalAmount
                ];
            });

        return view('admin.inventory.sales-return.category', compact('products', 'category', 'business', 'startDate', 'endDate'));
    }


    public function stockSummary(Request $request)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())
            ->with('business')
            ->first();
        $business = $currentAdmin->business;
        $startDate = $request->get('start_date', Carbon::today()->startOfYear()->format('Y-m-d'));
        $endDate = $request->get('end_date', Carbon::today()->endOfYear()->format('Y-m-d'));

        // Get all shopfront orders for this business within the date range
        $shopfrontOrders = DB::table('shopfront_orders')
            ->where('business_id', $currentAdmin->business_id)
            ->where('status', 'completed') // Only count completed orders
            ->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
            ->pluck('id');

        // Get all shopfront order lines grouped by product
        $shopfrontOrderLines = collect();
        if ($shopfrontOrders->isNotEmpty()) {
            $shopfrontOrderLines = DB::table('shopfront_order_lines')
                ->whereIn('order_id', $shopfrontOrders)
                ->select(
                    'product_id',
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('SUM(line_total) as total_value')
                )
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id');
        }

        $categorizedProducts = Product::where('business_id', $currentAdmin->business_id)
            ->with([
                'category',
                'inventoryTransactionLines' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('created_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
                        ->with('inventoryTransaction', 'batch');
                },
                'returnedProducts' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('return_date', [$startDate, $endDate])
                        ->with('batch');
                },
                // Add relationship for damage transactions
                'damageTransactionLines' => function ($query) use ($startDate, $endDate) {
                    $query->whereHas('damageTransaction', function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('transaction_date', [$startDate, $endDate]);
                    });
                }
            ])
            ->get()
            ->groupBy('category.name')
            ->map(function ($categoryProducts) use ($shopfrontOrderLines) {
                $categoryData = [
                    'products' => [],
                    'category_totals' => [
                        'purchase_qty' => 0,
                        'purchase_value' => 0,
                        'sales_qty' => 0,
                        'sales_value' => 0,
                        'online_sales_qty' => 0,  // Add online sales quantity to totals
                        'online_sales_value' => 0, // Add online sales value to totals
                        'return_qty' => 0,
                        'return_value' => 0,
                        'damage_qty' => 0,
                        'damage_value' => 0,
                        'gross_profit_amount' => 0,
                        'closing_qty' => 0,
                        'closing_value' => 0
                    ]
                ];

                foreach ($categoryProducts as $product) {
                    // Calculate beginning inventory
                    $beginningInventory = $product->opening_stock * $product->trade_price;

                    // Calculate purchases
                    $purchaseLines = $product->inventoryTransactionLines
                        ->where('inventoryTransaction.entry_type', 'purchase');
                    $purchaseQty = $purchaseLines->sum('quantity') + $product->opening_stock;
                    $purchases = $purchaseLines->sum('line_total');

                    // Calculate sales and revenue
                    $salesLines = $product->inventoryTransactionLines
                        ->where('inventoryTransaction.entry_type', 'sale');
                    $salesQty = $salesLines->sum('quantity');
                    $salesValue = $salesLines->sum(function ($line) {
                        // Use the trade price from the batch for this line
                        return $line->quantity * ($line->batch ? $line->batch->trade_price : 0);
                    });

                    // Get online sales from shopfront orders
                    $onlineSalesQty = 0;
                    $onlineSalesValue = 0;
                    if ($shopfrontOrderLines->has($product->id)) {
                        $onlineSalesQty = $shopfrontOrderLines[$product->id]->total_quantity;
                        $onlineSalesValue = $shopfrontOrderLines[$product->id]->total_value;
                    }

                    // Calculate COGS
                    $cogs = $salesLines->sum(function ($line) {
                        return $line->quantity * $line->dealer_price;
                    });

                    // Calculate initial gross profit (before returns)
                    $initialGrossProfit = $salesValue - $cogs;

                    // Get return data for this product
                    $returnedProducts = $product->returnedProducts;
                    $returnQty = $returnedProducts->sum('quantity');
                    $returnValue = $returnedProducts->sum('total_amount');

                    // Calculate profit adjustment for returns
                    // For each returned product, calculate (trade_price - dealer_price) * quantity
                    $returnProfitAdjustment = 0;
                    foreach ($returnedProducts as $returnedProduct) {
                        $batch = $returnedProduct->batch;
                        if ($batch) {
                            $tradePriceForReturn = $batch->trade_price;
                            $dealerPriceForReturn = $batch->dealer_price;
                            $priceDifference = $tradePriceForReturn - $dealerPriceForReturn;
                            $returnProfitAdjustment += $priceDifference * $returnedProduct->quantity;
                        } else {
                            // If batch not found, use the returned product's unit price as trade price
                            // and estimate dealer price based on average margin
                            $tradePriceForReturn = $returnedProduct->unit_price;
                            // Estimate dealer price as 80% of trade price if not available
                            $dealerPriceForReturn = $product->dealer_price ?? ($tradePriceForReturn * 0.8);
                            $priceDifference = $tradePriceForReturn - $dealerPriceForReturn;
                            $returnProfitAdjustment += $priceDifference * $returnedProduct->quantity;
                        }
                    }

                    // Get damage data for this product
                    $damageLines = $product->damageTransactionLines ?? collect();
                    $damageQty = $damageLines->sum('quantity');
                    $damageValue = 0;
                    foreach ($damageLines as $damageLine) {
                        // Try to find associated batch (need to add relation to your model)
                        // You'll need to modify your damageTransactionLines relationship to include batch information
                        $productItem = Product::find($damageLine->product_id);
                        if ($productItem) {
                            // Use dealer price from product if available
                            $dealerPrice = $productItem->dealer_price;
                            // If product has batches with dealer price, try to get an average or recent one
                            $batch = $productItem->batches()
                                ->whereNotNull('dealer_price')
                                ->orderBy('created_at', 'desc')
                                ->first();
                            if ($batch) {
                                $dealerPrice = $batch->dealer_price;
                            }
                            // If we have a dealer price, calculate the damage value
                            if ($dealerPrice) {
                                $damageValue += $damageLine->quantity * $dealerPrice;
                            } else {
                                // Fallback to the stored total_value if no dealer price is available
                                $damageValue += $damageLine->total_value;
                            }
                        } else {
                            // Fallback to the stored total_value if product not found
                            $damageValue += $damageLine->total_value;
                        }
                    }

                    // Adjust gross profit by subtracting the return profit adjustment
                    $adjustedGrossProfit = $initialGrossProfit;

                    // Calculate closing value using the formula
                    // Now also accounting for damage value
                    $closingValue = $beginningInventory + $purchases - $cogs;

                    $productData = [
                        'name' => $product->name,
                        'purchase_qty' => $purchaseQty,
                        'purchase_value' => $purchases + $beginningInventory,
                        'sales_qty' => $salesQty,
                        'sales_value' => $salesValue,
                        'online_sales_qty' => $onlineSalesQty,  // Add online sales quantity
                        'online_sales_value' => $onlineSalesValue, // Add online sales value
                        'return_qty' => $returnQty,
                        'return_value' => $returnValue,
                        'damage_qty' => $damageQty,
                        'damage_value' => $damageValue,
                        'gross_profit_amount' => $adjustedGrossProfit,
                        'closing_qty' => $product->current_stock,
                        'closing_value' => $closingValue
                    ];

                    $categoryData['products'][] = $productData;

                    // Update category totals
                    $categoryData['category_totals']['purchase_qty'] += $productData['purchase_qty'];
                    $categoryData['category_totals']['purchase_value'] += $productData['purchase_value'];
                    $categoryData['category_totals']['sales_qty'] += $productData['sales_qty'];
                    $categoryData['category_totals']['sales_value'] += $productData['sales_value'];
                    $categoryData['category_totals']['online_sales_qty'] += $productData['online_sales_qty']; // Add to category totals
                    $categoryData['category_totals']['online_sales_value'] += $productData['online_sales_value']; // Add to category totals
                    $categoryData['category_totals']['return_qty'] += $productData['return_qty'];
                    $categoryData['category_totals']['return_value'] += $productData['return_value'];
                    $categoryData['category_totals']['damage_qty'] += $productData['damage_qty'];
                    $categoryData['category_totals']['damage_value'] += $productData['damage_value'];
                    $categoryData['category_totals']['gross_profit_amount'] += $productData['gross_profit_amount'];
                    $categoryData['category_totals']['closing_qty'] += $productData['closing_qty'];
                    $categoryData['category_totals']['closing_value'] += $productData['closing_value'];
                }

                return $categoryData;
            });

        return view('admin.inventory.stock-summary.index', [
            'categorizedProducts' => $categorizedProducts,
            'business' => $business,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
    }


    // Unique invoice code 

    public function checkExistingTransactions(Request $request, $ledgerId)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');
            $verifyCustomerId = $request->query('verify_customer_id');

            Log::info('Checking existing transactions', [
                'ledger_id' => $ledgerId,
                'verify_customer_id' => $verifyCustomerId,
                'date' => $today
            ]);

            // Get the ledger information
            $ledger = Ledger::find($ledgerId);
            if (!$ledger) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ledger not found',
                    'exists' => false
                ], 404);
            }

            // Check for existing transaction
            $existingTransaction = InventoryTransaction::where('ledger_id', $ledgerId)
                ->whereDate('transaction_date', $today)
                ->first();

            // FIXED: Enhanced customer verification logic
            $customerVerified = true;
            if ($verifyCustomerId) {
                // Convert both IDs to integers for comparison
                $verifyId = (int)$verifyCustomerId;
                $ledgerIdInt = (int)$ledgerId;

                Log::info('Customer verification details', [
                    'verify_customer_id' => $verifyId,
                    'ledger_id' => $ledgerIdInt,
                    'ledger_common_customer_id' => $ledger->common_customer_id,
                    'ledger_name' => $ledger->name,
                    'ledger_type' => $ledger->ledger_type
                ]);

                // CASE 1: Direct local ledger match (verify_id matches ledger_id)
                if ($verifyId === $ledgerIdInt) {
                    $customerVerified = true;
                    Log::info('✅ Customer verification: Direct local ledger match', [
                        'verify_id' => $verifyId,
                        'ledger_id' => $ledgerIdInt
                    ]);
                }
                // CASE 2: Common customer verification (verify_id matches common_customer_id)
                elseif ($ledger->common_customer_id && $verifyId === (int)$ledger->common_customer_id) {
                    $customerVerified = true;
                    Log::info('✅ Customer verification: Common customer ID match', [
                        'verify_id' => $verifyId,
                        'common_customer_id' => $ledger->common_customer_id,
                        'local_ledger_id' => $ledgerIdInt
                    ]);
                }
                // CASE 3: Reverse lookup - if verify_id is a local ledger that points to the same common customer
                else {
                    $verifyLedger = Ledger::find($verifyId);
                    if (
                        $verifyLedger &&
                        $verifyLedger->common_customer_id &&
                        $ledger->common_customer_id &&
                        $verifyLedger->common_customer_id === $ledger->common_customer_id
                    ) {

                        $customerVerified = true;
                        Log::info('✅ Customer verification: Reverse common customer match', [
                            'verify_id' => $verifyId,
                            'verify_ledger_common_id' => $verifyLedger->common_customer_id,
                            'target_ledger_common_id' => $ledger->common_customer_id
                        ]);
                    } else {
                        $customerVerified = false;
                        Log::error('❌ Customer verification failed: No valid relationship found', [
                            'verify_customer_id' => $verifyId,
                            'target_ledger_id' => $ledgerIdInt,
                            'target_common_customer_id' => $ledger->common_customer_id,
                            'verify_ledger_exists' => !!$verifyLedger,
                            'verify_ledger_common_id' => $verifyLedger->common_customer_id ?? null
                        ]);
                    }
                }
            }

            $response = [
                'success' => true,
                'exists' => (bool)$existingTransaction,
                'transaction_id' => $existingTransaction ? $existingTransaction->id : null,
                'customer_verified' => $customerVerified,
                'ledger_info' => [
                    'id' => $ledger->id,
                    'name' => $ledger->name,
                    'ledger_type' => $ledger->ledger_type,
                    'common_customer_id' => $ledger->common_customer_id
                ]
            ];

            // Add verification failure message if needed
            if (!$customerVerified) {
                $response['message'] = "Customer verification failed: ID {$verifyCustomerId} does not match ledger {$ledgerId} or its common customer relationship";
            }

            Log::info('Transaction check completed', $response);

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Transaction check failed', [
                'ledger_id' => $ledgerId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Transaction check failed: ' . $e->getMessage(),
                'exists' => false,
                'customer_verified' => false
            ], 500);
        }
    }





    public function appendTransaction(Request $request)
    {
        DB::beginTransaction();
        try {
            // Step 1: Enhanced validation with customer verification
            $validated = $request->validate([
                'transaction_id' => 'required|exists:inventory_transactions,id',
                'customer_id' => 'required|integer',
                'original_customer_id' => 'nullable|integer',
                'lines' => 'required|array',
                'subtotal' => 'required|numeric',
                'grand_total' => 'required|numeric',
                'customer_verification' => 'nullable|array',
                'customer_verification.selection_timestamp' => 'nullable|integer',
                'customer_verification.customer_type' => 'nullable|in:local,common',
                'customer_verification.ledger_type' => 'nullable|string'
            ]);

            $existingTransaction = InventoryTransaction::findOrFail($validated['transaction_id']);
            $businessId = $this->getBusinessId();

            Log::info('Enhanced append transaction started', [
                'transaction_id' => $validated['transaction_id'],
                'customer_id' => $validated['customer_id'],
                'original_customer_id' => $validated['original_customer_id'],
                'business_id' => $this->getBusinessId(),
                'user_id' => Auth::id(),
                'customer_verification' => $validated['customer_verification'] ?? null
            ]);

            // Step 2: Enhanced customer verification
            $this->verifyCustomerForAppend($validated, $existingTransaction, $this->getBusinessId());

            // Step 3: Authorization check
            $user = Auth::user();
            $isAdmin = $user->roles->pluck('name')->contains('admin');
            $isStaff = $user->roles->pluck('name')->contains('staff');

            if (!$isAdmin && !$isStaff) {
                throw new \Exception('Only staff or admin can append to transactions');
            }

            // Step 4: Get staff record if needed
            $currentStaff = null;
            $staffCategories = collect();

            if ($isStaff) {
                $currentStaff = Staff::where('user_id', $user->id)->first();
                if (!$currentStaff) {
                    throw new \Exception('Staff record not found for current user');
                }
                $staffCategories = $currentStaff->productCategories->pluck('id')->toArray();
            }

            // Step 5: PRE-VALIDATE ALL LINES (Critical for atomicity)
            $this->preValidateAllLines($validated['lines'], $existingTransaction, $staffCategories, $isStaff);

            // Step 6: Process ALL inventory updates (only after all validations pass)
            $processedData = $this->processAllAppendedLines($validated['lines'], $existingTransaction);

            // Step 7: Update transaction totals
            $existingTransaction->subtotal += $validated['subtotal'];
            $existingTransaction->grand_total += $validated['grand_total'];
            $existingTransaction->save();

            // Step 8: Create journal transaction
            $journalTransaction = Transaction::create([
                'business_id' => $existingTransaction->business_id,
                'transaction_type' => 'Journal',
                'transaction_date' => now(),
                'amount' => $validated['grand_total'],
                'narration' => "Appended to Invoice #{$existingTransaction->id}"
            ]);

            // Step 9: Create ALL accounting entries (if this fails, everything rolls back)
            $this->createAppendedAccountingEntries(
                $journalTransaction,
                $existingTransaction,
                $validated['grand_total'],
                $validated['lines'],
                $isAdmin
            );

            // Step 10: Record staff contributors with quantities (CRITICAL - Must succeed or fail entire transaction)
            if ($isStaff && $currentStaff) {
                foreach ($validated['lines'] as $line) {
                    $product = Product::findOrFail($line['product_id']);

                    try {
                        // Check if contributor record already exists for this staff-product combination
                        $existingContributor = DB::table('inventory_transaction_contributors')
                            ->where('transaction_id', $existingTransaction->id)
                            ->where('staff_id', $currentStaff->id)
                            ->where('product_id', $product->id)
                            ->first();

                        if ($existingContributor) {
                            // Update existing contributor record with additional quantities
                            $updateResult = DB::table('inventory_transaction_contributors')
                                ->where('transaction_id', $existingTransaction->id)
                                ->where('staff_id', $currentStaff->id)
                                ->where('product_id', $product->id)
                                ->update([
                                    'contributed_quantity' => $existingContributor->contributed_quantity + $line['quantity'],
                                    'contributed_amount' => $existingContributor->contributed_amount + $line['line_total'],
                                    'updated_at' => now()
                                ]);

                            if (!$updateResult) {
                                throw new \Exception("Failed to update contributor record for product: {$product->name}");
                            }

                            Log::info('Updated existing contributor record', [
                                'transaction_id' => $existingTransaction->id,
                                'staff_id' => $currentStaff->id,
                                'product_id' => $product->id,
                                'added_quantity' => $line['quantity'],
                                'added_amount' => $line['line_total'],
                                'new_total_quantity' => $existingContributor->contributed_quantity + $line['quantity'],
                                'new_total_amount' => $existingContributor->contributed_amount + $line['line_total']
                            ]);
                        } else {
                            // Create new contributor record
                            $contributorId = DB::table('inventory_transaction_contributors')->insertGetId([
                                'transaction_id' => $existingTransaction->id,
                                'staff_id' => $currentStaff->id,
                                'product_category_id' => $product->category_id,
                                'product_id' => $product->id,
                                'contributed_quantity' => $line['quantity'],
                                'contributed_amount' => $line['line_total'],
                                'created_at' => now(),
                                'updated_at' => now()
                            ]);

                            if (!$contributorId) {
                                throw new \Exception("Failed to create contributor record for product: {$product->name}");
                            }

                            Log::info('Created new contributor record', [
                                'contributor_id' => $contributorId,
                                'transaction_id' => $existingTransaction->id,
                                'staff_id' => $currentStaff->id,
                                'product_id' => $product->id,
                                'product_name' => $product->name,
                                'contributed_quantity' => $line['quantity'],
                                'contributed_amount' => $line['line_total']
                            ]);
                        }
                    } catch (\Exception $e) {
                        // ANY contributor record failure must fail the entire transaction
                        Log::error('Contributor record creation/update failed - Rolling back entire transaction', [
                            'error' => $e->getMessage(),
                            'transaction_id' => $existingTransaction->id,
                            'staff_id' => $currentStaff->id,
                            'product_id' => $product->id,
                            'product_name' => $product->name
                        ]);

                        throw new \Exception("Critical failure: Unable to record staff contribution for product '{$product->name}'. Transaction aborted: " . $e->getMessage());
                    }
                }
            }

            // If we reach here, everything succeeded
            DB::commit();

            Log::info('Enhanced append transaction completed successfully', [
                'transaction_id' => $existingTransaction->id,
                'customer_id' => $validated['customer_id'],
                'lines_processed' => count($validated['lines']),
                'grand_total_added' => $validated['grand_total'],
                'new_grand_total' => $existingTransaction->grand_total,
                'contributor_records_processed' => $isStaff ? count($validated['lines']) : 0
            ]);

            return response()->json([
                'success' => true,
                'transaction_id' => $existingTransaction->id,
                'message' => 'Transaction appended successfully',
                'data' => [
                    'lines_processed' => count($validated['lines']),
                    'amount_added' => $validated['grand_total'],
                    'new_total' => $existingTransaction->grand_total,
                    'contributor_records' => $isStaff ? count($validated['lines']) : 0
                ]
            ]);
        } catch (\Exception $e) {
            // ANY failure rolls back EVERYTHING
            DB::rollBack();

            Log::error('Enhanced append transaction failed - Full rollback executed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'transaction_id' => $validated['transaction_id'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'user_id' => Auth::id()
            ]);

            // Return appropriate status code based on error type
            $statusCode = 500;
            if (strpos($e->getMessage(), 'permission') !== false) {
                $statusCode = 403;
            } elseif (strpos($e->getMessage(), 'Validation failed') !== false) {
                $statusCode = 422;
            } elseif (strpos($e->getMessage(), 'Customer verification failed') !== false) {
                $statusCode = 422;
            } elseif (strpos($e->getMessage(), 'not found') !== false) {
                $statusCode = 404;
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], $statusCode);
        }
    }


    /**
     * Enhanced customer verification for append transactions
     */
    private function verifyCustomerForAppend($validated, $existingTransaction, $businessId)
    {
        try {
            $customerId = $validated['customer_id'];
            $originalCustomerId = $validated['original_customer_id'];

            Log::info('Verifying customer for append transaction', [
                'customer_id' => $customerId,
                'original_customer_id' => $originalCustomerId,
                'existing_transaction_ledger_id' => $existingTransaction->ledger_id,
                'business_id' => $businessId
            ]);

            // Get the existing transaction's ledger
            $existingLedger = Ledger::where('id', $existingTransaction->ledger_id)
                ->where('business_id', $businessId)
                ->first();

            if (!$existingLedger) {
                throw new \Exception('Existing transaction ledger not found');
            }

            // Get the current request's ledger
            $currentLedger = Ledger::where('id', $customerId)
                ->where('business_id', $businessId)
                ->first();

            if (!$currentLedger) {
                throw new \Exception('Current customer ledger not found');
            }

            Log::info('Ledger information for verification', [
                'existing_ledger' => [
                    'id' => $existingLedger->id,
                    'name' => $existingLedger->name,
                    'common_customer_id' => $existingLedger->common_customer_id
                ],
                'current_ledger' => [
                    'id' => $currentLedger->id,
                    'name' => $currentLedger->name,
                    'common_customer_id' => $currentLedger->common_customer_id
                ]
            ]);

            // CASE 1: Direct ledger ID match (same local customer)
            if ($existingTransaction->ledger_id == $customerId) {
                Log::info('✅ Customer verification: Direct ledger ID match');
                return true;
            }

            // CASE 2: Both ledgers point to the same common customer
            if (
                $existingLedger->common_customer_id &&
                $currentLedger->common_customer_id &&
                $existingLedger->common_customer_id == $currentLedger->common_customer_id
            ) {

                Log::info('✅ Customer verification: Common customer ID match', [
                    'common_customer_id' => $existingLedger->common_customer_id
                ]);
                return true;
            }

            // CASE 3: Original customer ID verification for common customers
            if ($originalCustomerId != $customerId) {
                // This means we're dealing with a common customer that was converted to local

                // Check if original customer ID matches existing ledger's common_customer_id
                if (
                    $existingLedger->common_customer_id &&
                    $existingLedger->common_customer_id == $originalCustomerId
                ) {

                    Log::info('✅ Customer verification: Original common customer ID match', [
                        'original_customer_id' => $originalCustomerId,
                        'existing_ledger_common_id' => $existingLedger->common_customer_id
                    ]);
                    return true;
                }

                // Check if current ledger's common_customer_id matches original
                if (
                    $currentLedger->common_customer_id &&
                    $currentLedger->common_customer_id == $originalCustomerId
                ) {

                    Log::info('✅ Customer verification: Current ledger common customer ID match', [
                        'original_customer_id' => $originalCustomerId,
                        'current_ledger_common_id' => $currentLedger->common_customer_id
                    ]);
                    return true;
                }
            }

            // If we reach here, verification failed
            Log::error('❌ Customer verification failed for append transaction', [
                'customer_id' => $customerId,
                'original_customer_id' => $originalCustomerId,
                'existing_transaction_id' => $existingTransaction->id,
                'existing_ledger_id' => $existingTransaction->ledger_id,
                'existing_common_customer_id' => $existingLedger->common_customer_id,
                'current_common_customer_id' => $currentLedger->common_customer_id
            ]);

            throw new \Exception("Customer verification failed: Cannot append to transaction for different customer");
        } catch (\Exception $e) {
            Log::error('Customer verification failed for append transaction', [
                'customer_id' => $validated['customer_id'] ?? 'unknown',
                'original_customer_id' => $validated['original_customer_id'] ?? 'unknown',
                'existing_transaction_id' => $existingTransaction->id ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            throw new \Exception("Customer verification failed: " . $e->getMessage());
        }
    }


    private function preValidateAllLines($lines, $transaction, $staffCategories, $isStaff)
    {
        $errors = [];

        // Get current staff if this is a staff user
        $currentStaff = null;
        if ($isStaff) {
            $currentStaff = Staff::where('user_id', Auth::id())->first();
            if (!$currentStaff) {
                throw new \Exception('Staff record not found for current user');
            }
        }

        foreach ($lines as $index => $line) {
            try {
                // Validate product exists
                $product = Product::findOrFail($line['product_id']);
                $batch = ProductBatch::findOrFail($line['batch_id']);

                // Validate staff category permissions
                if ($isStaff && !in_array($product->category_id, $staffCategories)) {
                    $errors[] = "Line " . ($index + 1) . ": You don't have permission to sell products in the {$product->category->name} category";
                    continue;
                }

                // Validate sufficient stock
                if ($batch->remaining_quantity < $line['quantity']) {
                    $errors[] = "Line " . ($index + 1) . ": Insufficient stock for {$product->name}. Available: {$batch->remaining_quantity}, Requested: {$line['quantity']}";
                    continue;
                }

                // CRITICAL: Validate unit price conflicts
                $existingLine = InventoryTransactionLine::where([
                    'inventory_transaction_id' => $transaction->id,
                    'product_id' => $line['product_id']
                ])->first();

                if ($existingLine && $existingLine->unit_price != $line['unit_price']) {
                    $errors[] = "Line " . ($index + 1) . ": Product '{$product->name}' already exists with unit price {$existingLine->unit_price}, cannot add with different unit price {$line['unit_price']}";
                }

                // NEW: First Contributor Validation for Staff Users
                if ($isStaff && $currentStaff) {
                    $firstContributorError = $this->validateFirstContributorRule(
                        $currentStaff,
                        $product,
                        $transaction,
                        $index + 1
                    );

                    if ($firstContributorError) {
                        $errors[] = $firstContributorError;
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Line " . ($index + 1) . ": " . $e->getMessage();
            }
        }

        // If ANY validation fails, throw exception to prevent processing
        if (!empty($errors)) {
            throw new \Exception("Validation failed: " . implode('; ', $errors));
        }
    }

    /**
     * Validate the First Contributor Rule for append transactions
     * Only the staff member who first contributed a product to a customer can append more of that product
     */
    private function validateFirstContributorRule($currentStaff, $product, $transaction, $lineNumber)
    {
        try {
            // Get the transaction date for same-day comparison
            $transactionDate = Carbon::parse($transaction->created_at)->format('Y-m-d');

            Log::info('Validating first contributor rule', [
                'current_staff_id' => $currentStaff->id,
                'current_staff_name' => $currentStaff->user->name,
                'product_id' => $product->id,
                'product_name' => $product->name,
                'customer_ledger_id' => $transaction->ledger_id,
                'transaction_date' => $transactionDate,
                'line_number' => $lineNumber
            ]);

            // Find all transactions for this customer on the same date
            $customerTransactionsToday = InventoryTransaction::where('ledger_id', $transaction->ledger_id)
                ->where('business_id', $transaction->business_id)
                ->whereDate('created_at', $transactionDate)
                ->pluck('id')
                ->toArray();

            if (empty($customerTransactionsToday)) {
                Log::info('No transactions found for customer today - validation passed');
                return null; // No existing transactions, so no conflict
            }

            // Check if any staff has already contributed this product to this customer today
            $existingContributor = DB::table('inventory_transaction_contributors')
                ->select('staff_id', 'created_at', 'contributed_quantity', 'contributed_amount')
                ->whereIn('transaction_id', $customerTransactionsToday)
                ->where('product_id', $product->id)
                ->orderBy('created_at', 'asc') // Get the first contributor
                ->first();

            if (!$existingContributor) {
                Log::info('No existing contributor found for this product - validation passed');
                return null; // No existing contributor, current staff can proceed
            }

            // Check if the existing contributor is the current staff
            if ($existingContributor->staff_id == $currentStaff->id) {
                Log::info('Current staff is the first contributor - validation passed', [
                    'first_contribution_time' => $existingContributor->created_at,
                    'existing_quantity' => $existingContributor->contributed_quantity,
                    'existing_amount' => $existingContributor->contributed_amount
                ]);
                return null; // Current staff is the first contributor, can append more
            }

            // Get the first contributor's details for error message
            $firstContributorStaff = Staff::with('user')->find($existingContributor->staff_id);
            $firstContributorName = $firstContributorStaff ? $firstContributorStaff->user->name : 'Unknown Staff';

            Log::warning('First contributor rule violation detected', [
                'current_staff_id' => $currentStaff->id,
                'current_staff_name' => $currentStaff->user->name,
                'first_contributor_id' => $existingContributor->staff_id,
                'first_contributor_name' => $firstContributorName,
                'first_contribution_time' => $existingContributor->created_at,
                'product_name' => $product->name,
                'customer_ledger_id' => $transaction->ledger_id
            ]);

            // Return validation error
            return "PRODUCT '{$product->name}' IS ALREADY BY {$firstContributorName}.\nPLEASE CONTACT TO {$firstContributorName}.\nONLY {$firstContributorName} CAN ADD THIS PRODUCT HERE.";
        } catch (\Exception $e) {
            Log::error('Error in first contributor validation', [
                'error' => $e->getMessage(),
                'current_staff_id' => $currentStaff->id,
                'product_id' => $product->id,
                'transaction_id' => $transaction->id,
                'trace' => $e->getTraceAsString()
            ]);

            // In case of validation error, be restrictive and block the action
            return "Line {$lineNumber}: Unable to validate contributor permissions for product '{$product->name}'. Please try again or contact administrator.";
        }
    }


    private function processAllAppendedLines($lines, $transaction)
    {
        $processedProducts = [];

        foreach ($lines as $line) {
            $product = Product::findOrFail($line['product_id']);
            $batch = ProductBatch::findOrFail($line['batch_id']);

            // Find existing line by product_id and unit_price
            $existingLine = InventoryTransactionLine::where([
                'inventory_transaction_id' => $transaction->id,
                'product_id' => $line['product_id']
            ])->where('unit_price', $line['unit_price'])->first();

            if ($existingLine) {
                // Combine with existing line
                $existingLine->quantity += $line['quantity'];
                $existingLine->line_total += $line['line_total'];
                $existingLine->line_discount += ($line['line_discount'] ?? 0);
                $existingLine->save();
            } else {
                // Create new line
                InventoryTransactionLine::create([
                    'inventory_transaction_id' => $transaction->id,
                    'product_id' => $line['product_id'],
                    'batch_id' => $batch->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'dealer_price' => $batch->dealer_price,
                    'line_total' => $line['line_total'],
                    'line_discount' => $line['line_discount'] ?? 0
                ]);
            }

            // Update inventory (these operations must succeed or rollback)
            $batch->remaining_quantity -= $line['quantity'];
            if ($batch->remaining_quantity < 0) {
                throw new \Exception("Batch quantity would become negative for {$product->name}");
            }
            $batch->save();

            $product->current_stock -= $line['quantity'];
            if ($product->current_stock < 0) {
                throw new \Exception("Product stock would become negative for {$product->name}");
            }
            $product->save();

            $processedProducts[] = $product;
        }

        return ['products' => $processedProducts];
    }


    private function createAppendedAccountingEntries($journalTransaction, $inventoryTransaction, $grandTotal, $appendedLines, $isAdmin = false)
    {
        try {
            if ($inventoryTransaction->payment_method === 'cash') {
                // Get payment ledger for cash transactions
                $paymentLedger = $this->findPaymentLedger($inventoryTransaction);

                // Cash Sale: Debit Cash Ledger
                TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $paymentLedger->id,
                    'debit_amount' => $grandTotal,
                    'credit_amount' => 0,
                    'narration' => 'Cash Sales (Appended)'
                ]);

                // For admin, credit the default Sales Account ledger
                if ($isAdmin) {
                    $salesLedger = $this->findDefaultSalesLedger($inventoryTransaction->business_id);

                    TransactionLine::create([
                        'transaction_id' => $journalTransaction->id,
                        'ledger_id' => $salesLedger->id,
                        'debit_amount' => 0,
                        'credit_amount' => $grandTotal,
                        'narration' => 'Sales Revenue (Appended)'
                    ]);

                    // Recalculate sales ledger balance
                    $this->recalcLedgerBalance($salesLedger);
                } else {
                    // For staff, credit Customer Ledger (existing behavior)
                    TransactionLine::create([
                        'transaction_id' => $journalTransaction->id,
                        'ledger_id' => $inventoryTransaction->ledger_id,
                        'debit_amount' => 0,
                        'credit_amount' => $grandTotal,
                        'narration' => 'Sales Receivable (Appended)'
                    ]);
                }

                // Recalculate ledger balances
                $this->recalcLedgerBalance($paymentLedger);
                if (!$isAdmin) {
                    $this->recalcLedgerBalance(Ledger::find($inventoryTransaction->ledger_id));
                }
            }

            if ($inventoryTransaction->payment_method === 'credit') {
                // Credit Sale: Debit Customer Ledger
                TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $inventoryTransaction->ledger_id,
                    'debit_amount' => $grandTotal,
                    'credit_amount' => 0,
                    'narration' => 'Credit Sales Receivable (Appended)'
                ]);

                // Get current user (who is appending to the transaction)
                $currentUser = Auth::user();

                // Check if user has the 'staff' role
                if ($currentUser->roles->pluck('name')->contains('staff')) {
                    $currentStaff = Staff::where('user_id', $currentUser->id)->first();

                    if ($currentStaff) {
                        // Get staff's assigned categories
                        $staffCategories = $currentStaff->productCategories->pluck('id')->toArray();

                        // Get staff's ledgers that are of type 'Sales Accounts'
                        $staffLedgers = $currentStaff->ledgers()
                            ->where('ledger_type', 'Sales Accounts')
                            ->get();

                        if ($staffLedgers->isNotEmpty()) {
                            $staffLedger = $staffLedgers->first();

                            // Get all product IDs from appended lines
                            $productIds = collect($appendedLines)->pluck('product_id')->toArray();

                            // Eager load products with their categories
                            $products = Product::with('category')->whereIn('id', $productIds)->get()->keyBy('id');

                            // Calculate total for appended products in staff's categories
                            $staffTotal = 0;
                            $staffProducts = false;
                            $lineDiscountTotal = 0;
                            $processedProducts = [];

                            // Process each appended line
                            foreach ($appendedLines as $line) {
                                $product = $products[$line['product_id']] ?? null;

                                if (!$product) {
                                    Log::warning('Product not found in appended transaction', [
                                        'product_id' => $line['product_id'],
                                        'transaction_id' => $inventoryTransaction->id
                                    ]);
                                    continue;
                                }

                                $processedProducts[] = [
                                    'id' => $product->id,
                                    'name' => $product->name,
                                    'category_id' => $product->category_id,
                                    'in_staff_categories' => in_array($product->category_id, $staffCategories)
                                ];

                                // Check if product is in staff's assigned categories
                                if (in_array($product->category_id, $staffCategories)) {
                                    $staffTotal += $line['line_total'];
                                    $staffProducts = true;

                                    // Add line discount if present
                                    if (isset($line['line_discount'])) {
                                        $lineDiscountTotal += $line['line_discount'];
                                    }
                                }
                            }

                            Log::info('Staff products in appended transaction', [
                                'staff_id' => $currentStaff->id,
                                'staff_name' => $currentStaff->user->name,
                                'transaction_id' => $inventoryTransaction->id,
                                'staff_categories' => $staffCategories,
                                'processed_products' => $processedProducts,
                                'staff_total' => $staffTotal,
                                'has_staff_products' => $staffProducts
                            ]);

                            // Only create entries if staff has products in the appended lines
                            if ($staffProducts && $staffTotal > 0) {
                                // Credit staff ledger with total amount for their category products
                                TransactionLine::create([
                                    'transaction_id' => $journalTransaction->id,
                                    'ledger_id' => $staffLedger->id,
                                    'debit_amount' => 0,
                                    'credit_amount' => $staffTotal,
                                    'narration' => 'Credit Sales Invoice #' . $inventoryTransaction->id . ' (Appended)'
                                ]);

                                if ($lineDiscountTotal != 0) {
                                    TransactionLine::create([
                                        'transaction_id' => $journalTransaction->id,
                                        'ledger_id' => $staffLedger->id,
                                        'debit_amount' => $lineDiscountTotal > 0 ? abs($lineDiscountTotal) : 0,
                                        'credit_amount' => $lineDiscountTotal < 0 ? abs($lineDiscountTotal) : 0,
                                        'narration' => 'Price Adjustment - ' . $staffLedger->name . ' - Invoice #' . $inventoryTransaction->id . ' (Appended)'
                                    ]);
                                }

                                // Log the staff ledger entry
                                Log::info('Created staff ledger entry for appended transaction', [
                                    'staff_id' => $currentStaff->id,
                                    'staff_name' => $currentStaff->user->name,
                                    'ledger_id' => $staffLedger->id,
                                    'amount' => $staffTotal,
                                    'transaction_id' => $inventoryTransaction->id,
                                    'line_discount_total' => $lineDiscountTotal
                                ]);
                            } else {
                                Log::info('No staff products in appended transaction', [
                                    'staff_id' => $currentStaff->id,
                                    'transaction_id' => $inventoryTransaction->id,
                                    'staff_categories' => $staffCategories,
                                    'processed_products' => $processedProducts
                                ]);
                            }
                        } else {
                            Log::warning('Staff has no Sales Accounts ledger', [
                                'staff_id' => $currentStaff->id,
                                'staff_name' => $currentStaff->user->name
                            ]);
                        }
                    }
                }

                // Recalculate ledger balances
                $this->recalcLedgerBalance(Ledger::find($inventoryTransaction->ledger_id));

                if (isset($staffLedger)) {
                    $this->recalcLedgerBalance($staffLedger);
                }
            }
        } catch (\Exception $e) {
            // Log the specific accounting error
            Log::error('Accounting entry creation failed in appendTransaction', [
                'error' => $e->getMessage(),
                'journal_transaction_id' => $journalTransaction->id ?? null,
                'inventory_transaction_id' => $inventoryTransaction->id ?? null,
                'grand_total' => $grandTotal,
                'payment_method' => $inventoryTransaction->payment_method ?? null,
                'is_admin' => $isAdmin,
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw to trigger full transaction rollback
            throw new \Exception("Accounting entry failed: " . $e->getMessage());
        }
    }
}
