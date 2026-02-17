<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopfrontOrder;
use App\Models\BusinessAdmin;
use App\Models\Business;
use App\Models\Ledger;
use App\Models\Transaction;
use App\Models\TransactionLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminShopfrontOrderController extends Controller
{
    /**
     * Display a listing of the orders.
     */
    public function index(Request $request)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();
        $business = Business::findOrFail($currentAdmin->business_id);

        $query = ShopfrontOrder::with('orderLines')
            ->where('business_id', $currentAdmin->business_id);

        // Apply phone number filter
        if ($request->filled('phone')) {
            $query->where('customer_phone', 'like', '%' . $request->phone . '%');
        }

        // Apply invoice number filter
        if ($request->filled('invoice')) {
            $query->where('invoice_number', 'like', '%' . $request->invoice . '%');
        }

        // Apply date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $startDate = $request->start_date . ' 00:00:00';
            $endDate = $request->end_date . ' 23:59:59';
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif ($request->filled('start_date')) {
            // If only start date is provided
            $startDate = $request->start_date . ' 00:00:00';
            $query->where('created_at', '>=', $startDate);
        } elseif ($request->filled('end_date')) {
            // If only end date is provided
            $endDate = $request->end_date . ' 23:59:59';
            $query->where('created_at', '<=', $endDate);
        }

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Order by latest and paginate
        $orders = $query->latest()->paginate(15);

        // Append query parameters to pagination links
        $orders->appends($request->query());

        return view('admin.shopfront.orders.index', compact('orders', 'business'));
    }


    /**
     * Display the specified order.
     */
    public function show(ShopfrontOrder $order)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        if ($order->business_id !== $currentAdmin->business_id) {
            abort(403);
        }

        // Get the business details
        $business = Business::findOrFail($currentAdmin->business_id);

        // Load order lines and related products
        $order->load(['orderLines.product', 'orderLines.commonProduct']);

        return view('admin.shopfront.orders.show', compact('order', 'business'));
    }

    /**
     * Update the status of an order with fully atomic accounting entries.
     */
    public function updateStatus(Request $request, ShopfrontOrder $order)
    {
        Log::info('=== ATOMIC STATUS UPDATE REQUEST START ===', [
            'order_id' => $order->id,
            'current_status' => $order->status,
            'request_data' => $request->all(),
            'user_id' => Auth::id()
        ]);

        // Start database transaction immediately for full atomicity
        DB::beginTransaction();

        try {
            // Step 1: Validate request (no DB operations)
            $validated = $request->validate([
                'status' => 'required|in:pending,processing,completed,cancelled,due'
            ]);

            Log::info('Validation passed', ['validated_data' => $validated]);

            // Step 2: Get and validate admin access (single query)
            $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

            if (!$currentAdmin) {
                throw new \Exception('Admin access required - no admin record found for user');
            }

            if ($order->business_id !== $currentAdmin->business_id) {
                throw new \Exception('Unauthorized access - order does not belong to your business');
            }

            $newStatus = $validated['status'];
            $oldStatus = $order->status;

            Log::info('Status change details', [
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'business_id' => $currentAdmin->business_id
            ]);

            // Step 3: Check if status change is needed
            if ($oldStatus === $newStatus) {
                DB::commit();
                return redirect()->back()->with('info', 'Order status is already ' . ucfirst($newStatus));
            }

            // Step 4: Prepare accounting data (no DB operations, just preparation)
            $accountingPlan = $this->prepareAccountingPlan($order, $newStatus, $oldStatus, $currentAdmin->business_id);

            Log::info('Accounting plan prepared', [
                'should_create' => $accountingPlan['shouldCreate'],
                'should_delete' => $accountingPlan['shouldDelete'],
                'ledgers_validated' => $accountingPlan['ledgersValidated']
            ]);

            // Step 5: Execute all database operations atomically
            $this->executeAtomicAccountingOperations($order, $accountingPlan);

            // Step 6: Update order status as final operation
            $updateResult = $order->update(['status' => $newStatus]);

            if (!$updateResult) {
                throw new \Exception('Failed to update order status in database');
            }

            // Step 7: Verify the update was successful
            $order->refresh();
            if ($order->status !== $newStatus) {
                throw new \Exception('Order status update verification failed - status not changed');
            }

            // Commit transaction only if everything succeeded
            DB::commit();

            Log::info('=== ATOMIC STATUS UPDATE SUCCESS ===', [
                'order_id' => $order->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'final_status' => $order->status
            ]);

            return redirect()->back()->with('success', 'Order status updated successfully from ' . ucfirst($oldStatus) . ' to ' . ucfirst($newStatus));
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            Log::error('Validation failed - transaction rolled back', [
                'order_id' => $order->id,
                'errors' => $e->errors()
            ]);

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', 'Invalid status provided');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== ATOMIC STATUS UPDATE FAILED - TRANSACTION ROLLED BACK ===', [
                'order_id' => $order->id,
                'current_status' => $order->status,
                'requested_status' => $request->input('status'),
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to update order status: ' . $e->getMessage());
        }
    }

    /**
     * Prepare accounting plan without any database operations (Updated - removed reference_number)
     */
    private function prepareAccountingPlan(ShopfrontOrder $order, string $newStatus, string $oldStatus, int $businessId): array
    {
        $plan = [
            'shouldCreate' => false,
            'shouldDelete' => false,
            'ledgersValidated' => false,
            'cashLedger' => null,
            'salesLedger' => null,
            'transactionData' => null
        ];

        // Determine what accounting operations are needed
        if ($newStatus === 'completed' && $oldStatus !== 'completed') {
            $plan['shouldCreate'] = true;
        } elseif ($newStatus === 'due' && $oldStatus === 'completed') {
            $plan['shouldDelete'] = true;
        } elseif ($oldStatus === 'completed' && !in_array($newStatus, ['completed', 'due'])) {
            $plan['shouldDelete'] = true;
        }

        // Pre-validate ledgers if we need to create accounting entries
        if ($plan['shouldCreate']) {
            // Get required ledgers in single query
            $ledgers = Ledger::where('business_id', $businessId)
                ->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('ledger_type', 'Cash-in-Hand');
                    })->orWhere(function ($q) {
                        $q->where('ledger_type', 'Sales Accounts')
                            ->where('status', 'default');
                    });
                })
                ->get();

            $cashLedger = $ledgers->where('ledger_type', 'Cash-in-Hand')->first();
            $salesLedger = $ledgers->where('ledger_type', 'Sales Accounts')
                ->where('status', 'default')->first();

            if (!$cashLedger) {
                throw new \Exception('Cash-in-Hand ledger not found for business ID: ' . $businessId);
            }

            if (!$salesLedger) {
                throw new \Exception('Default Sales Accounts ledger not found for business ID: ' . $businessId);
            }

            $plan['cashLedger'] = $cashLedger;
            $plan['salesLedger'] = $salesLedger;
            $plan['ledgersValidated'] = true;

            // Prepare transaction data with ALL required fields (NO reference_number)
            $transactionData = [
                'business_id' => $businessId,
                'transaction_type' => 'Journal',
                'transaction_date' => Carbon::now()->format('Y-m-d'),
                'description' => 'Shopfront Order Sale - Invoice: ' . $order->invoice_number,
                'narration' => 'Shopfront - IID: ' . $order->invoice_number,
                'amount' => $order->total_amount,
                'total_amount' => $order->total_amount,
                'created_by' => Auth::id(),
                'shopfront_order_id' => $order->id, // Only using this for linking
                'created_at' => now(),
                'updated_at' => now()
            ];

            $plan['transactionData'] = $transactionData;
        }

        return $plan;
    }

    /**
     * Execute all accounting operations atomically
     */
    private function executeAtomicAccountingOperations(ShopfrontOrder $order, array $plan): void
    {
        if ($plan['shouldDelete']) {
            $this->deleteAccountingEntriesAtomic($order);
        }

        if ($plan['shouldCreate']) {
            $this->createAccountingEntriesAtomic($order, $plan);
        }
    }

    /**
     * Create accounting entries atomically (Updated - no reference_number)
     */
    private function createAccountingEntriesAtomic(ShopfrontOrder $order, array $plan): void
    {
        Log::info('Creating atomic accounting entries using shopfront_order_id', [
            'order_id' => $order->id,
            'total_amount' => $order->total_amount
        ]);

        try {
            // Step 1: Delete any existing entries first
            $this->deleteAccountingEntriesAtomic($order);

            // Step 2: Create journal transaction using shopfront_order_id
            $transaction = Transaction::create($plan['transactionData']);

            if (!$transaction || !$transaction->id) {
                throw new \Exception('Failed to create journal transaction');
            }

            Log::info('Created journal transaction using shopfront_order_id', [
                'transaction_id' => $transaction->id,
                'transaction_type' => $transaction->transaction_type,
                'shopfront_order_id' => $transaction->shopfront_order_id,
                'amount' => $transaction->amount,
                'narration' => $transaction->narration
            ]);

            // Step 3: Prepare transaction lines data for bulk insert
            $transactionLinesData = [
                [
                    'transaction_id' => $transaction->id,
                    'ledger_id' => $plan['cashLedger']->id,
                    'debit_amount' => $order->total_amount,
                    'credit_amount' => 0,
                    'narration' => 'Cash received from shopfront sale - Order ID: ' . $order->id . ', Invoice: ' . $order->invoice_number,
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'transaction_id' => $transaction->id,
                    'ledger_id' => $plan['salesLedger']->id,
                    'debit_amount' => 0,
                    'credit_amount' => $order->total_amount,
                    'narration' => 'Sales from shopfront - Order ID: ' . $order->id . ', Invoice: ' . $order->invoice_number,
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ];

            // Step 4: Bulk insert transaction lines
            $insertResult = TransactionLine::insert($transactionLinesData);

            if (!$insertResult) {
                throw new \Exception('Failed to create transaction lines');
            }

            Log::info('Created transaction lines via bulk insert', [
                'lines_count' => count($transactionLinesData),
                'amount' => $order->total_amount,
                'cash_ledger_id' => $plan['cashLedger']->id,
                'sales_ledger_id' => $plan['salesLedger']->id
            ]);

            // Step 5: Update ledger balances atomically
            $this->updateLedgerBalancesAtomic([
                $plan['cashLedger'],
                $plan['salesLedger']
            ]);

            Log::info('Atomic accounting entries created successfully using shopfront_order_id', [
                'order_id' => $order->id,
                'transaction_id' => $transaction->id,
                'shopfront_order_id' => $order->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create atomic accounting entries', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }


    /**
     * Delete accounting entries atomically using only shopfront_order_id
     */
    private function deleteAccountingEntriesAtomic(ShopfrontOrder $order): void
    {
        Log::info('Deleting accounting entries using shopfront_order_id', [
            'order_id' => $order->id
        ]);

        try {
            // Step 1: Find transactions using ONLY shopfront_order_id
            $transactionIds = Transaction::where('shopfront_order_id', $order->id)
                ->where('business_id', $order->business_id)
                ->where('transaction_type', 'Journal')
                ->pluck('id')
                ->toArray();

            if (empty($transactionIds)) {
                Log::info('No Journal transactions found to delete', [
                    'order_id' => $order->id,
                    'shopfront_order_id' => $order->id
                ]);
                return;
            }

            // Step 2: Get affected ledger IDs before deletion
            $affectedLedgerIds = TransactionLine::whereIn('transaction_id', $transactionIds)
                ->distinct()
                ->pluck('ledger_id')
                ->toArray();

            Log::info('Found Journal transactions using shopfront_order_id', [
                'transaction_ids' => $transactionIds,
                'affected_ledger_ids' => $affectedLedgerIds,
                'shopfront_order_id' => $order->id
            ]);

            // Step 3: Bulk delete transaction lines
            $deletedLines = TransactionLine::whereIn('transaction_id', $transactionIds)->delete();

            // Step 4: Bulk delete transactions
            $deletedTransactions = Transaction::whereIn('id', $transactionIds)->delete();

            Log::info('Bulk deleted Journal accounting entries using shopfront_order_id', [
                'deleted_lines' => $deletedLines,
                'deleted_transactions' => $deletedTransactions
            ]);

            // Step 5: Update affected ledger balances
            if (!empty($affectedLedgerIds)) {
                $affectedLedgers = Ledger::whereIn('id', $affectedLedgerIds)->get();
                $this->updateLedgerBalancesAtomic($affectedLedgers);
            }

            Log::info('Successfully deleted all Journal accounting entries using shopfront_order_id', [
                'order_id' => $order->id,
                'transactions_deleted' => count($transactionIds)
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete Journal accounting entries using shopfront_order_id', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Update multiple ledger balances atomically using bulk operations
     */
    private function updateLedgerBalancesAtomic($ledgers): void
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

        try {
            // Prepare bulk update data
            $updateCases = [];
            $ledgerIds = [];

            foreach ($ledgers as $ledger) {
                $ledgerIds[] = $ledger->id;

                // Calculate new balance
                $currentBalance = $ledger->opening_balance ?? 0;

                // Get all transaction lines for this ledger in single query
                $transactionLines = TransactionLine::where('ledger_id', $ledger->id)
                    ->select('debit_amount', 'credit_amount')
                    ->get();

                foreach ($transactionLines as $line) {
                    if (in_array($ledger->ledger_type, $drLedgers)) {
                        $currentBalance += $line->debit_amount;
                        $currentBalance -= $line->credit_amount;
                    } else {
                        $currentBalance -= $line->debit_amount;
                        $currentBalance += $line->credit_amount;
                    }
                }

                // Prepare SQL CASE statement for bulk update
                $updateCases[] = "WHEN id = {$ledger->id} THEN {$currentBalance}";

                Log::info('Calculated ledger balance', [
                    'ledger_id' => $ledger->id,
                    'ledger_name' => $ledger->name,
                    'ledger_type' => $ledger->ledger_type,
                    'new_balance' => $currentBalance
                ]);
            }

            // Execute bulk update using raw SQL for atomicity
            if (!empty($updateCases) && !empty($ledgerIds)) {
                $caseSql = implode(' ', $updateCases);
                $ledgerIdsList = implode(',', $ledgerIds);

                $sql = "UPDATE ledgers SET 
                        current_balance = CASE {$caseSql} END,
                        updated_at = NOW()
                        WHERE id IN ({$ledgerIdsList})";

                $affectedRows = DB::update($sql);

                if ($affectedRows !== count($ledgerIds)) {
                    throw new \Exception("Bulk ledger update failed. Expected {count($ledgerIds)} updates, got {$affectedRows}");
                }

                Log::info('Bulk updated ledger balances atomically', [
                    'ledgers_updated' => $affectedRows,
                    'ledger_ids' => $ledgerIds
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update ledger balances atomically', [
                'ledger_ids' => $ledgerIds ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Display the print view for the order.
     */
    public function print(ShopfrontOrder $order)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        if ($order->business_id !== $currentAdmin->business_id) {
            abort(403);
        }

        // Get the business details
        $business = Business::findOrFail($currentAdmin->business_id);

        // Load order lines and related products
        $order->load(['orderLines.product', 'orderLines.commonProduct']);

        return view('admin.shopfront.orders.print', compact('order', 'business'));
    }

    /**
     * Display category-wise report for shopfront orders.
     */
    public function categoryReport(Request $request)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();
        $business = Business::findOrFail($currentAdmin->business_id);

        $query = ShopfrontOrder::with(['orderLines.product', 'orderLines.commonProduct'])
            ->where('business_id', $currentAdmin->business_id);

        // Date range filter
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $orders = $query->get();

        // Group orders by category
        $categoryReport = [];
        foreach ($orders as $order) {
            foreach ($order->orderLines as $line) {
                $categoryName = $line->is_common_product
                    ? ($line->commonProduct->category->category_name ?? 'Uncategorized')
                    : ($line->product->category->name ?? 'Uncategorized');

                if (!isset($categoryReport[$categoryName])) {
                    $categoryReport[$categoryName] = [
                        'total_quantity' => 0,
                        'total_amount' => 0,
                        'order_count' => 0
                    ];
                }

                $categoryReport[$categoryName]['total_quantity'] += $line->quantity;
                $categoryReport[$categoryName]['total_amount'] += $line->line_total;
                $categoryReport[$categoryName]['order_count'] += 1;
            }
        }

        return view('admin.shopfront.orders.category-report', compact('categoryReport', 'business'));
    }

    /**
     * Remove the specified order from storage atomically.
     */
    public function destroy(ShopfrontOrder $order)
    {
        Log::info('=== ATOMIC ORDER DELETE REQUEST START ===', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => Auth::id()
        ]);

        // Start database transaction for atomic deletion
        DB::beginTransaction();

        try {
            // Step 1: Validate admin access
            $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

            if (!$currentAdmin) {
                throw new \Exception('Admin access required');
            }

            if ($order->business_id !== $currentAdmin->business_id) {
                throw new \Exception('Unauthorized access to order');
            }

            // Step 2: Prepare accounting plan for deletion
            $accountingPlan = [
                'shouldDelete' => true,
                'shouldCreate' => false
            ];

            // Step 3: Delete accounting entries atomically
            $this->executeAtomicAccountingOperations($order, $accountingPlan);

            // Step 4: Delete order lines
            $deletedLines = $order->orderLines()->delete();

            // Step 5: Delete the order
            $orderDeleted = $order->delete();

            if (!$orderDeleted) {
                throw new \Exception('Failed to delete order from database');
            }

            // Commit transaction
            DB::commit();

            Log::info('=== ORDER DELETED SUCCESSFULLY ===', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'deleted_lines' => $deletedLines
            ]);

            return redirect()->route('admin.shopfront.orders.index')
                ->with('success', 'Order deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('=== ATOMIC ORDER DELETE FAILED ===', [
                'order_id' => $order->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->back()->with('error', 'Failed to delete order: ' . $e->getMessage());
        }
    }
}
