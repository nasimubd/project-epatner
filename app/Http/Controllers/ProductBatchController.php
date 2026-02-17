<?php

namespace App\Http\Controllers;

use App\Models\ProductBatch;
use App\Models\Product;
use App\Models\Ledger;
use App\Models\Transaction;
use App\Models\TransactionLine;
use Illuminate\Support\Facades\Cache;
use App\Models\InventoryTransactionLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class ProductBatchController extends Controller
{

    public function index(Request $request)
    {
        // Get current user's business ID
        $user = Auth::user();
        $businessId = null;

        if ($user->roles->contains('name', 'admin')) {
            $admin = \App\Models\BusinessAdmin::where('user_id', $user->id)->first();
            $businessId = $admin ? $admin->business_id : null;
        } elseif ($user->roles->contains('name', 'staff')) {
            $staff = \App\Models\Staff::where('user_id', $user->id)->first();
            $businessId = $staff ? $staff->business_id : null;
        }

        if (!$businessId) {
            return redirect()->back()->with('error', 'No business associated with this user.');
        }

        // Get filters from request
        $filters = [
            'search' => $request->input('search', ''),
            'stock_filter' => $request->input('stock_filter', 'all'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_order' => $request->input('sort_order', 'desc'),
            'per_page' => $request->input('per_page', 15)
        ];

        // Handle AJAX requests
        if ($request->ajax()) {
            return $this->getFilteredBatches($request, $businessId);
        }

        // Get batches without caching for now (we'll cache stats only)
        $batches = $this->buildBatchesQuery($businessId, $filters['search'], $filters['stock_filter'], $filters['sort_by'], $filters['sort_order'])->paginate($filters['per_page']);

        // Process images for display
        $this->processProductImages($batches);

        // Get stats (cached)
        $stats = $this->getBatchStats($businessId);

        return view('admin.batches.index', compact('batches', 'filters', 'stats'));
    }

    private function getFilteredBatches(Request $request, $businessId)
    {
        $search = $request->input('search', '');
        $stockFilter = $request->input('stock_filter', 'all');
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $perPage = $request->input('per_page', 15);

        $batches = $this->buildBatchesQuery($businessId, $search, $stockFilter, $sortBy, $sortOrder)->paginate($perPage);

        // Process images for display
        $this->processProductImages($batches);

        $stats = $this->getBatchStats($businessId);

        $html = view('admin.batches.partials.batches-list', compact('batches', 'stats'))->render();

        return response()->json([
            'success' => true,
            'html' => $html,
            'stats' => $stats
        ]);
    }

    /**
     * Process product images to convert binary data to base64 for display
     */
    private function processProductImages($batches)
    {
        foreach ($batches as $batch) {
            if ($batch->product && $batch->product->image) {
                // Convert binary image data to base64 for display
                $batch->product->image_base64 = base64_encode($batch->product->image);
                // Remove the binary data to prevent serialization issues
                unset($batch->product->image);
            }
        }
    }

    private function buildBatchesQuery($businessId, $search, $stockFilter, $sortBy, $sortOrder)
    {
        $query = ProductBatch::with(['product' => function ($query) {
            // Select image as well, but we'll process it separately
            $query->select('id', 'name', 'current_stock', 'image', 'business_id');
        }])
            ->join('products', 'product_batches.product_id', '=', 'products.id')
            ->where('products.business_id', $businessId)
            ->select('product_batches.*'); // Select only product_batches columns to avoid ambiguity

        // Apply search filter
        if (!empty($search)) {
            $query->where('products.name', 'like', "%{$search}%");
        }

        // Apply stock filter
        switch ($stockFilter) {
            case 'zero_stock':
                $query->where('product_batches.remaining_quantity', '<=', 0);
                break;
            case 'low_stock':
                $query->where('product_batches.remaining_quantity', '>', 0)
                    ->where('product_batches.remaining_quantity', '<=', 10);
                break;
            case 'in_stock':
                $query->where('product_batches.remaining_quantity', '>', 10);
                break;
        }

        // Apply sorting
        $validSortColumns = [
            'created_at' => 'product_batches.created_at',
            'batch_number' => 'product_batches.batch_number',
            'remaining_quantity' => 'product_batches.remaining_quantity',
            'dealer_price' => 'product_batches.dealer_price',
            'trade_price' => 'product_batches.trade_price',
            'batch_date' => 'product_batches.batch_date',
            'expiry_date' => 'product_batches.expiry_date',
            'product_name' => 'products.name'
        ];

        if (isset($validSortColumns[$sortBy])) {
            $query->orderBy($validSortColumns[$sortBy], $sortOrder);
        } else {
            $query->orderBy('product_batches.created_at', 'desc');
        }

        return $query;
    }

    private function getBatchStats($businessId)
    {
        $cacheKey = "batch_stats_{$businessId}";

        return Cache::remember($cacheKey, 600, function () use ($businessId) {
            // Fix the ambiguous column issue by specifying table names
            $stats = DB::table('product_batches')
                ->join('products', 'product_batches.product_id', '=', 'products.id')
                ->where('products.business_id', $businessId)
                ->selectRaw('
                    COUNT(*) as total_batches,
                    SUM(CASE WHEN product_batches.remaining_quantity <= 0 THEN 1 ELSE 0 END) as zero_stock_batches,
                    SUM(CASE WHEN product_batches.remaining_quantity > 0 AND product_batches.remaining_quantity <= 10 THEN 1 ELSE 0 END) as low_stock_batches,
                    SUM(CASE WHEN product_batches.remaining_quantity > 10 THEN 1 ELSE 0 END) as in_stock_batches,
                    SUM(product_batches.remaining_quantity * product_batches.dealer_price) as total_value
                ')
                ->first();

            return [
                'total_batches' => $stats->total_batches ?? 0,
                'zero_stock_batches' => $stats->zero_stock_batches ?? 0,
                'low_stock_batches' => $stats->low_stock_batches ?? 0,
                'in_stock_batches' => $stats->in_stock_batches ?? 0,
                'total_value' => $stats->total_value ?? 0,
            ];
        });
    }

    public function bulkDelete(Request $request)
    {
        $request->validate([
            'batch_ids' => 'required|array',
            'batch_ids.*' => 'integer|exists:product_batches,id'
        ]);

        $user = Auth::user();
        $businessId = null;

        if ($user->roles->contains('name', 'admin')) {
            $admin = \App\Models\BusinessAdmin::where('user_id', $user->id)->first();
            $businessId = $admin ? $admin->business_id : null;
        }

        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Get batches that belong to the user's business and have zero stock
            $batches = ProductBatch::whereIn('id', $request->batch_ids)
                ->whereHas('product', function ($query) use ($businessId) {
                    $query->where('business_id', $businessId);
                })
                ->where('remaining_quantity', '<=', 0)
                ->get();

            if ($batches->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No eligible batches found for deletion. Only zero-stock batches can be deleted.'
                ]);
            }

            $deletedCount = 0;
            foreach ($batches as $batch) {
                // Check if batch has any related transactions
                $hasTransactions = InventoryTransactionLine::where('batch_id', $batch->id)->exists();

                if (!$hasTransactions) {
                    $batch->delete();
                    $deletedCount++;
                }
            }

            // Clear cache
            $this->clearBatchCache($businessId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} batch(es)."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete batches. Please try again.'
            ], 500);
        }
    }

    public function refreshCache(Request $request)
    {
        $user = Auth::user();
        $businessId = null;

        if ($user->roles->contains('name', 'admin')) {
            $admin = \App\Models\BusinessAdmin::where('user_id', $user->id)->first();
            $businessId = $admin ? $admin->business_id : null;
        }

        if (!$businessId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access.'
            ], 403);
        }

        try {
            $this->clearBatchCache($businessId);

            return response()->json([
                'success' => true,
                'message' => 'Cache refreshed successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Cache refresh failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh cache.'
            ], 500);
        }
    }

    private function clearBatchCache($businessId)
    {
        // Clear stats cache
        Cache::forget("batch_stats_{$businessId}");

        // For now, we're not caching the batch list due to image serialization issues
        // If you want to implement caching for batch lists, consider:
        // 1. Storing images separately (file system or CDN)
        // 2. Using Redis with proper serialization
        // 3. Caching only the data without images and loading images separately
    }



    public function create()
    {
        $products = Product::all();
        return view('admin.batches.create', compact('products'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'batch_number' => 'required|string|unique:product_batches',
            'dealer_price' => 'required|numeric|min:0',
            'trade_price' => 'required|numeric|min:0',
            'remaining_quantity' => 'required|integer|min:0',
            'batch_date' => 'required|date',
            'expiry_date' => 'required|date|after:batch_date',
            'is_opening_batch' => 'boolean'
        ]);

        ProductBatch::create($validated);
        return redirect()->route('admin.batches.index')->with('success', 'Batch created successfully');
    }

    public function edit(ProductBatch $batch)
    {
        $products = Product::all();
        return view('admin.batches.edit', compact('batch', 'products'));
    }

    public function update(Request $request, ProductBatch $batch)
    {
        // Only validate the adjustable fields, keep others as they are
        $validated = $request->validate([
            'dealer_price' => 'required|numeric|min:0',
            'trade_price' => 'required|numeric|min:0',
            'remaining_quantity' => 'required|numeric|min:0',
        ]);

        // Store original values for calculations
        $originalDealerPrice = $batch->dealer_price;
        $originalTradePrice = $batch->trade_price;
        $originalQuantity = $batch->remaining_quantity;

        // Calculate differences
        $dealerPriceDifference = $validated['dealer_price'] - $originalDealerPrice;
        $tradePriceDifference = $validated['trade_price'] - $originalTradePrice;
        $quantityDifference = $validated['remaining_quantity'] - $originalQuantity;

        // Check if any adjustable field has changed
        $hasChanges = $dealerPriceDifference != 0 || $tradePriceDifference != 0 || $quantityDifference != 0;

        // Begin transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Update the batch with only the fields we want to change
            $batch->dealer_price = $validated['dealer_price'];
            $batch->trade_price = $validated['trade_price'];
            $batch->remaining_quantity = $validated['remaining_quantity'];
            $batch->save();

            // Update the product's current stock if quantity changed
            if ($quantityDifference != 0) {
                $product = Product::findOrFail($batch->product_id);
                $product->current_stock += $quantityDifference;

                // Ensure stock doesn't go negative
                if ($product->current_stock < 0) {
                    throw new \Exception('Cannot reduce batch quantity below zero. Current stock would become negative.');
                }

                $product->save();
            }

            // Update the original inventory transaction if it exists
            if ($hasChanges) {
                $this->updateOriginalInventoryTransaction($batch, $originalDealerPrice, $originalTradePrice, $originalQuantity, $validated);

                // Create accounting adjustment entries
                $this->createBatchAdjustmentEntries($batch, $originalDealerPrice, $originalTradePrice, $originalQuantity, $validated);
            }

            DB::commit();

            return redirect()->route('admin.batches.index')->with('success', 'Batch updated successfully with inventory and accounting adjustments');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch update failed: ' . $e->getMessage(), [
                'batch_id' => $batch->id,
                'error' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Failed to update batch: ' . $e->getMessage());
        }
    }

    /**
     * Update the original inventory transaction for this batch
     */
    private function updateOriginalInventoryTransaction($batch, $originalDealerPrice, $originalTradePrice, $originalQuantity, $newValues)
    {
        // Find the original inventory transaction line for this batch
        $inventoryTransactionLine = InventoryTransactionLine::where('batch_id', $batch->id)
            ->whereHas('inventoryTransaction', function ($query) {
                $query->where('entry_type', 'purchase');
            })
            ->with('inventoryTransaction')
            ->first();

        if (!$inventoryTransactionLine) {
            Log::info('No original inventory transaction found for batch', [
                'batch_id' => $batch->id
            ]);
            return;
        }

        $inventoryTransaction = $inventoryTransactionLine->inventoryTransaction;

        // Calculate the differences
        $quantityDifference = $newValues['remaining_quantity'] - $originalQuantity;
        $priceDifference = $newValues['dealer_price'] - $originalDealerPrice;

        // Calculate the old and new line totals
        $oldLineTotal = $inventoryTransactionLine->line_total;
        $newLineTotal = $newValues['dealer_price'] * $newValues['remaining_quantity'];
        $lineTotalDifference = $newLineTotal - $oldLineTotal;

        Log::info('Updating inventory transaction line', [
            'batch_id' => $batch->id,
            'transaction_line_id' => $inventoryTransactionLine->id,
            'old_quantity' => $originalQuantity,
            'new_quantity' => $newValues['remaining_quantity'],
            'old_price' => $originalDealerPrice,
            'new_price' => $newValues['dealer_price'],
            'old_line_total' => $oldLineTotal,
            'new_line_total' => $newLineTotal,
            'line_total_difference' => $lineTotalDifference
        ]);

        // Update the inventory transaction line
        $inventoryTransactionLine->quantity = $newValues['remaining_quantity'];
        $inventoryTransactionLine->unit_price = $newValues['dealer_price'];
        $inventoryTransactionLine->dealer_price = $newValues['dealer_price'];
        $inventoryTransactionLine->line_total = $newLineTotal;
        $inventoryTransactionLine->save();

        // Update the main inventory transaction totals
        // Recalculate subtotal from all lines
        $newSubtotal = InventoryTransactionLine::where('inventory_transaction_id', $inventoryTransaction->id)
            ->sum('line_total');

        // Calculate the difference in grand total
        $grandTotalDifference = $newSubtotal - $inventoryTransaction->subtotal;

        Log::info('Updating inventory transaction totals', [
            'transaction_id' => $inventoryTransaction->id,
            'old_subtotal' => $inventoryTransaction->subtotal,
            'new_subtotal' => $newSubtotal,
            'old_grand_total' => $inventoryTransaction->grand_total,
            'grand_total_difference' => $grandTotalDifference
        ]);

        // Update the transaction totals
        $inventoryTransaction->subtotal = $newSubtotal;
        $inventoryTransaction->grand_total += $grandTotalDifference;
        $inventoryTransaction->save();

        Log::info('Successfully updated inventory transaction', [
            'transaction_id' => $inventoryTransaction->id,
            'new_subtotal' => $inventoryTransaction->subtotal,
            'new_grand_total' => $inventoryTransaction->grand_total
        ]);
    }


    /**
     * Create accounting entries for batch adjustments
     */
    private function createBatchAdjustmentEntries($batch, $originalDealerPrice, $originalTradePrice, $originalQuantity, $newValues)
    {
        $product = Product::findOrFail($batch->product_id);
        $businessId = $product->business_id;

        // Calculate total adjustment amount using dealer price (cost price)
        $originalValue = $originalDealerPrice * $originalQuantity;
        $newValue = $newValues['dealer_price'] * $newValues['remaining_quantity'];
        $adjustmentAmount = $newValue - $originalValue;

        // Only create entries if there's a monetary adjustment
        if ($adjustmentAmount == 0) {
            return;
        }

        // Find the original purchase transaction for this batch
        $originalTransaction = $this->findOriginalPurchaseTransaction($batch);

        if (!$originalTransaction) {
            // If no original transaction found, create a general adjustment
            $this->createGeneralAdjustmentEntry($batch, $adjustmentAmount, $businessId);
            return;
        }

        // Determine if it was a cash or credit purchase
        $isCashPurchase = $originalTransaction->payment_method === 'cash';

        // Create adjustment transaction
        $adjustmentTransaction = Transaction::create([
            'business_id' => $businessId,
            'transaction_type' => 'Journal',
            'transaction_date' => now(),
            'amount' => abs($adjustmentAmount),
            'narration' => "Batch Adjustment - Batch #{$batch->batch_number} - " .
                ($adjustmentAmount > 0 ? 'Increase' : 'Decrease') . " of ৳" . number_format(abs($adjustmentAmount), 2)
        ]);

        // Get required ledgers
        $purchaseAccountLedger = Ledger::where('business_id', $businessId)
            ->where('ledger_type', 'Purchase Accounts')
            ->first();

        if (!$purchaseAccountLedger) {
            throw new \Exception('Purchase Accounts ledger not found');
        }

        if ($isCashPurchase) {
            // Cash Purchase Adjustment
            $cashLedger = Ledger::where('business_id', $businessId)
                ->where('ledger_type', 'Cash-in-Hand')
                ->first();

            if (!$cashLedger) {
                throw new \Exception('Cash-in-Hand ledger not found');
            }

            if ($adjustmentAmount > 0) {
                // Increase in value: Debit Purchase Account, Credit Cash
                TransactionLine::create([
                    'transaction_id' => $adjustmentTransaction->id,
                    'ledger_id' => $purchaseAccountLedger->id,
                    'debit_amount' => $adjustmentAmount,
                    'credit_amount' => 0,
                    'narration' => 'Batch Value Increase - Purchase Account'
                ]);

                TransactionLine::create([
                    'transaction_id' => $adjustmentTransaction->id,
                    'ledger_id' => $cashLedger->id,
                    'debit_amount' => 0,
                    'credit_amount' => $adjustmentAmount,
                    'narration' => 'Batch Value Increase - Cash Payment'
                ]);
            } else {
                // Decrease in value: Credit Purchase Account, Debit Cash
                TransactionLine::create([
                    'transaction_id' => $adjustmentTransaction->id,
                    'ledger_id' => $purchaseAccountLedger->id,
                    'debit_amount' => 0,
                    'credit_amount' => abs($adjustmentAmount),
                    'narration' => 'Batch Value Decrease - Purchase Account'
                ]);

                TransactionLine::create([
                    'transaction_id' => $adjustmentTransaction->id,
                    'ledger_id' => $cashLedger->id,
                    'debit_amount' => abs($adjustmentAmount),
                    'credit_amount' => 0,
                    'narration' => 'Batch Value Decrease - Cash Refund'
                ]);
            }

            // Recalculate ledger balances
            $this->recalcLedgerBalance($cashLedger);
        } else {
            // Credit Purchase Adjustment
            $supplierLedger = $this->findSupplierLedger($originalTransaction, $product);

            if (!$supplierLedger) {
                throw new \Exception('Supplier ledger not found for credit purchase adjustment');
            }

            if ($adjustmentAmount > 0) {
                // Increase in value: Debit Purchase Account, Credit Supplier Account
                TransactionLine::create([
                    'transaction_id' => $adjustmentTransaction->id,
                    'ledger_id' => $purchaseAccountLedger->id,
                    'debit_amount' => $adjustmentAmount,
                    'credit_amount' => 0,
                    'narration' => 'Batch Value Increase - Purchase Account'
                ]);

                TransactionLine::create([
                    'transaction_id' => $adjustmentTransaction->id,
                    'ledger_id' => $supplierLedger->id,
                    'debit_amount' => 0,
                    'credit_amount' => $adjustmentAmount,
                    'narration' => 'Batch Value Increase - Supplier Account'
                ]);
            } else {
                // Decrease in value: Credit Purchase Account, Debit Supplier Account
                TransactionLine::create([
                    'transaction_id' => $adjustmentTransaction->id,
                    'ledger_id' => $purchaseAccountLedger->id,
                    'debit_amount' => 0,
                    'credit_amount' => abs($adjustmentAmount),
                    'narration' => 'Batch Value Decrease - Purchase Account'
                ]);

                TransactionLine::create([
                    'transaction_id' => $adjustmentTransaction->id,
                    'ledger_id' => $supplierLedger->id,
                    'debit_amount' => abs($adjustmentAmount),
                    'credit_amount' => 0,
                    'narration' => 'Batch Value Decrease - Supplier Account'
                ]);
            }

            // Recalculate ledger balances
            $this->recalcLedgerBalance($supplierLedger);
        }

        // Recalculate purchase account balance
        $this->recalcLedgerBalance($purchaseAccountLedger);

        Log::info('Batch adjustment accounting entries created', [
            'batch_id' => $batch->id,
            'adjustment_amount' => $adjustmentAmount,
            'transaction_id' => $adjustmentTransaction->id,
            'purchase_type' => $isCashPurchase ? 'cash' : 'credit'
        ]);
    }

    /**
     * Find the original purchase transaction for this batch
     */
    private function findOriginalPurchaseTransaction($batch)
    {
        // Look for inventory transaction line with this batch
        $inventoryTransactionLine = InventoryTransactionLine::where('batch_id', $batch->id)
            ->whereHas('inventoryTransaction', function ($query) {
                $query->where('entry_type', 'purchase');
            })
            ->with('inventoryTransaction')
            ->first();

        return $inventoryTransactionLine ? $inventoryTransactionLine->inventoryTransaction : null;
    }

    /**
     * Find supplier ledger for credit purchase
     */
    private function findSupplierLedger($originalTransaction, $product)
    {
        // First try to find from the original transaction's ledger
        if ($originalTransaction->ledger_id) {
            $ledger = Ledger::find($originalTransaction->ledger_id);
            if ($ledger && $ledger->ledger_type === 'Sundry Creditors (Supplier)') {
                return $ledger;
            }
        }

        // If not found, try to find from product category
        if ($product->category && $product->category->ledger_id) {
            $ledger = Ledger::find($product->category->ledger_id);
            if ($ledger && $ledger->ledger_type === 'Sundry Creditors (Supplier)') {
                return $ledger;
            }
        }

        // Fallback: find any supplier ledger for this business
        return Ledger::where('business_id', $product->business_id)
            ->where('ledger_type', 'Sundry Creditors (Supplier)')
            ->first();
    }

    /**
     * Create general adjustment entry when original transaction is not found
     */
    private function createGeneralAdjustmentEntry($batch, $adjustmentAmount, $businessId)
    {
        $adjustmentTransaction = Transaction::create([
            'business_id' => $businessId,
            'transaction_type' => 'Journal',
            'transaction_date' => now(),
            'amount' => abs($adjustmentAmount),
            'narration' => "General Batch Adjustment - Batch #{$batch->batch_number}"
        ]);

        // Get required ledgers
        $purchaseAccountLedger = Ledger::where('business_id', $businessId)
            ->where('ledger_type', 'Purchase Accounts')
            ->first();

        $adjustmentLedger = Ledger::where('business_id', $businessId)
            ->where('ledger_type', 'Expenses')
            ->first();

        if (!$purchaseAccountLedger || !$adjustmentLedger) {
            throw new \Exception('Required ledgers not found for general adjustment');
        }

        if ($adjustmentAmount > 0) {
            // Increase: Debit Purchase Account, Credit Adjustment Account
            TransactionLine::create([
                'transaction_id' => $adjustmentTransaction->id,
                'ledger_id' => $purchaseAccountLedger->id,
                'debit_amount' => $adjustmentAmount,
                'credit_amount' => 0,
                'narration' => 'Batch Value Increase'
            ]);

            TransactionLine::create([
                'transaction_id' => $adjustmentTransaction->id,
                'ledger_id' => $adjustmentLedger->id,
                'debit_amount' => 0,
                'credit_amount' => $adjustmentAmount,
                'narration' => 'Batch Adjustment'
            ]);
        } else {
            // Decrease: Credit Purchase Account, Debit Adjustment Account
            TransactionLine::create([
                'transaction_id' => $adjustmentTransaction->id,
                'ledger_id' => $purchaseAccountLedger->id,
                'debit_amount' => 0,
                'credit_amount' => abs($adjustmentAmount),
                'narration' => 'Batch Value Decrease'
            ]);

            TransactionLine::create([
                'transaction_id' => $adjustmentTransaction->id,
                'ledger_id' => $adjustmentLedger->id,
                'debit_amount' => abs($adjustmentAmount),
                'credit_amount' => 0,
                'narration' => 'Batch Adjustment'
            ]);
        }

        // Recalculate ledger balances
        $this->recalcLedgerBalance($purchaseAccountLedger);
        $this->recalcLedgerBalance($adjustmentLedger);
    }

    /**
     * Recalculate ledger balance
     */
    private function recalcLedgerBalance($ledger)
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

    public function destroy(ProductBatch $batch)
    {
        $batch->delete();
        return redirect()->route('admin.batches.index')->with('success', 'Batch deleted successfully');
    }
}
