<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Staff;
use App\Models\BusinessAdmin;
use App\Models\Business;
use App\Models\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class InvoicePrintController extends Controller
{
    // Number of records to load per request
    protected $perPage = 15;

    public function index(Request $request)
    {
        // Get the authenticated user's business ID through staff or admin relationship
        $businessId = null;
        $business = null;

        // Check user roles using the roles relationship
        $userRoles = Auth::user()->roles->pluck('name')->toArray();

        if (in_array('staff', $userRoles) || in_array('dsr', $userRoles)) {
            $staff = Staff::where('user_id', Auth::id())->first();
            if ($staff) {
                $businessId = $staff->business_id;
                $business = Business::find($businessId);
            }
        } elseif (in_array('admin', $userRoles)) {
            $admin = BusinessAdmin::where('user_id', Auth::id())->first();
            if ($admin) {
                $businessId = $admin->business_id;
                $business = Business::find($businessId);
            }
        }

        if (!$businessId) {
            return redirect()->back()->with('error', 'Unable to determine your business. Please contact support.');
        }

        // Get the current page from the request, default to 1
        $page = $request->input('page', 1);

        // Check if this is an AJAX request for "Load More"
        if ($request->ajax()) {
            try {
                // Debug the request
                // dd($request->all());

                // Apply filters
                $query = InventoryTransaction::query()->where('business_id', $businessId);

                // Handle date range filtering
                if ($request->filled('start_date')) {
                    $query->whereDate('transaction_date', '>=', $request->start_date);
                } else {
                    // Default to current date if no start date provided
                    $query->whereDate('transaction_date', '>=', now()->startOfDay());
                }

                if ($request->filled('end_date')) {
                    $query->whereDate('transaction_date', '<=', $request->end_date);
                } else {
                    // Default to current date if no end date provided
                    $query->whereDate('transaction_date', '<=', now()->endOfDay());
                }

                // Handle search by customer name (via ledger)
                if ($request->filled('search')) {
                    $search = $request->search;
                    $query->where(function ($q) use ($search) {
                        // Search by invoice ID
                        $q->where('id', 'like', "%{$search}%");

                        // Search by ledger name (customer)
                        $q->orWhereHas('ledger', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });

                        // If you have a direct relationship to a ledger
                        if (Schema::hasColumn('inventory_transactions', 'ledger_id')) {
                            $ledgerIds = Ledger::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
                            if (!empty($ledgerIds)) {
                                $q->orWhereIn('ledger_id', $ledgerIds);
                            }
                        }
                    });
                }

                // Move this outside the search condition
                if ($request->filled('entry_type')) {
                    $query->where('entry_type', $request->entry_type);
                }
                // Order by date descending
                $query->orderBy('transaction_date', 'desc');

                // Get total count for "Load More" functionality
                $total = $query->count();

                // Get records for the current page
                $limit = $this->perPage;
                $offset = ($page - 1) * $limit;
                $transactions = $query->skip($offset)->take($limit)->get();

                // Debug the SQL query
                // dd($query->toSql(), $query->getBindings());

                // Debug the transactions
                // dd($transactions);

                // Calculate if there are more records
                $hasMore = $total > ($offset + $limit);

                // Check if the partial view exists
                if (!view()->exists('admin.invoices.partials.invoice-rows')) {
                    return response()->json([
                        'error' => 'Partial view not found: admin.invoices.partials.invoice-rows'
                    ], 500);
                }

                $html = view('admin.invoices.partials.invoice-rows', [
                    'transactions' => $transactions
                ])->render();

                return response()->json([
                    'html' => $html,
                    'hasMore' => $hasMore
                ]);
            } catch (\Exception $e) {
                // Debug the exception
                return response()->json([
                    'error' => 'Exception: ' . $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ], 500);
            }
        }

        // Apply filters for the initial page load
        $query = InventoryTransaction::query()->where('business_id', $businessId);

        // Handle date range filtering
        if ($request->filled('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        } else {
            // Default to current date if no start date provided
            $query->whereDate('transaction_date', '>=', now()->startOfDay());
        }

        if ($request->filled('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        } else {
            // Default to current date if no end date provided
            $query->whereDate('transaction_date', '<=', now()->endOfDay());
        }

        // Handle search by customer name (via ledger)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                // Search by invoice ID
                $q->where('id', 'like', "%{$search}%");

                // Search by ledger name (customer)
                $q->orWhereHas('ledger', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });

                // If you have a direct relationship to a ledger
                if (Schema::hasColumn('inventory_transactions', 'ledger_id')) {
                    $ledgerIds = Ledger::where('name', 'like', "%{$search}%")->pluck('id')->toArray();
                    if (!empty($ledgerIds)) {
                        $q->orWhereIn('ledger_id', $ledgerIds);
                    }
                }
            });
        }

        if ($request->filled('entry_type')) {
            $query->where('entry_type', $request->entry_type);
        }

        // Order by date descending
        $query->orderBy('transaction_date', 'desc');

        // Get total count for "Load More" functionality
        $total = $query->count();

        // Get records for the current page
        $limit = $this->perPage;
        $offset = ($page - 1) * $limit;
        $transactions = $query->skip($offset)->take($limit)->get();

        // Calculate if there are more records
        $hasMore = $total > ($offset + $limit);

        // If reset button was clicked, redirect to the base URL without query parameters
        if ($request->has('reset')) {
            return redirect()->route('admin.invoices.index');
        }

        return view('admin.invoices.index', [
            'transactions' => $transactions ?? collect(), // Provide an empty collection if null
            'hasMore' => $hasMore ?? false,
            'currentPage' => $page,
            'filters' => $request->only(['start_date', 'end_date', 'search', 'entry_type']), // Remove spaces
            'business' => $business
        ]);
    }


    /**
     * Clear all invoice caches for a specific business and user
     */
    private function clearUserInvoiceCaches($businessId)
    {
        // Get all cache keys that match the pattern
        $cachePattern = 'invoices_' . $businessId . '_*';

        // This is a simplified approach - in a real app you might need a more sophisticated
        // cache clearing mechanism depending on your cache driver
        $keys = Cache::get('invoice_cache_keys_' . $businessId, []);
        foreach ($keys as $key) {
            Cache::forget($key);
        }

        // Clear the keys list itself
        Cache::forget('invoice_cache_keys_' . $businessId);
    }

    // Other methods remain unchanged
    public function print($id)
    {
        // Load the transaction with necessary relationships
        $inventoryTransaction = InventoryTransaction::with(['lines.product.unit'])->findOrFail($id);

        // Get customer information from the ledger
        $customer = null;
        if (isset($inventoryTransaction->ledger_id)) {
            $ledger = Ledger::find($inventoryTransaction->ledger_id);
            if ($ledger) {
                // Create a customer object from ledger data
                $customer = (object)[
                    'name' => $ledger->name, // Add "Store" constant to the name
                    'location' => $ledger->location ?? '',
                    'contact' => $ledger->contact ?? ''
                ];
            }
        }

        // Get the business
        $businessId = $inventoryTransaction->business_id;
        $business = Business::find($businessId);

        // Get staff name if available
        $staffName = null;
        if (isset($inventoryTransaction->staff_id)) {
            $staff = Staff::find($inventoryTransaction->staff_id);
            $staffName = $staff ? $staff->name : null;
        }

        // Get damaged products
        $damagedProducts = $inventoryTransaction->lines->where('is_damaged', true);
        $damagedTotal = $damagedProducts->sum('total_value');

        // Get returned products if any
        $returnedProducts = collect();
        $returnedTotal = 0;
        if (method_exists($inventoryTransaction, 'returns') && $inventoryTransaction->returns) {
            $returnedProducts = $inventoryTransaction->returns;
            $returnedTotal = $returnedProducts->sum('total_amount');
        }

        return view('admin.invoices.print', [
            'inventoryTransaction' => $inventoryTransaction,
            'customer' => $customer,
            'business' => $business,
            'staffName' => $staffName,
            'damagedProducts' => $damagedProducts,
            'damagedTotal' => $damagedTotal,
            'returnedProducts' => $returnedProducts,
            'returnedTotal' => $returnedTotal
        ]);
    }

    public function batchPrint(Request $request)
    {
        $ids = $request->invoice_ids;

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No invoices selected');
        }

        $transactions = InventoryTransaction::with(['lines.product.unit', 'staff'])->whereIn('id', $ids)->get();

        // Get the business ID from the first transaction
        $businessId = $transactions->first()->business_id;
        $business = Business::find($businessId);

        // Process each transaction to ensure customer data is available
        foreach ($transactions as $transaction) {
            // Get customer information from the ledger
            if (isset($transaction->ledger_id)) {
                $ledger = Ledger::find($transaction->ledger_id);
                if ($ledger) {
                    // Create a customer object from ledger data - using the same structure as print() method
                    $transaction->customer = (object)[
                        'name' => $ledger->name,
                        'location' => $ledger->location ?? '',
                        'contact' => $ledger->contact ?? ''
                    ];

                    // Debug: Log the customer data to verify it's being set correctly
                    Log::info('Customer data for transaction ' . $transaction->id, [
                        'name' => $transaction->customer->name,
                        'location' => $transaction->customer->location,
                        'contact' => $transaction->customer->contact
                    ]);
                }
            } else {
                // Create an empty customer object to prevent null errors
                $transaction->customer = (object)[
                    'name' => 'Unknown Customer',
                    'location' => '',
                    'contact' => ''
                ];
            }
        }

        return view('admin.invoices.batch-print', [
            'transactions' => $transactions,
            'business' => $business
        ]);
    }
}
