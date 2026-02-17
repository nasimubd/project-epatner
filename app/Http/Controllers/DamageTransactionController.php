<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessAdmin;
use Illuminate\Support\Facades\Log;
use App\Models\TransactionLine;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;
use App\Models\DamageTransaction;
use App\Models\DamageTransactionLine;
use App\Models\Staff;
use App\Models\Product;
use App\Models\Ledger;
use Illuminate\Support\Facades\Response;

class DamageTransactionController extends Controller
{
    public function store(Request $request)
    {
        // Add detailed logging at the start
        Log::info('Damage Transaction Request Received', [
            'request_data' => $request->all()
        ]);

        if (!$request->has('damage_lines') || empty($request->input('damage_lines'))) {
            return response()->json([
                'message' => 'No damage products to process',
                'status' => 'skipped'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'transaction_date' => 'required|date',
            'customer_ledger_id' => 'required|exists:ledgers,id',
            'inventory_transaction_id' => 'required|exists:inventory_transactions,id',
            'damage_lines' => 'sometimes|array',
            'damage_lines.*.product_id' => 'required|exists:products,id',
            'damage_lines.*.quantity' => 'required|numeric|min:0.001',
            'damage_lines.*.unit_price' => 'required|numeric|min:0',
            'damage_lines.*.damage_reason' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            Log::error('Validation Errors', [
                'errors' => $validator->errors(),
                'input' => $request->all()
            ]);
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $currentUser = Auth::user();
            $currentAdmin = BusinessAdmin::where('user_id', $currentUser->id)->first();
            $currentStaff = Staff::where('user_id', $currentUser->id)->first();

            $businessId = $currentAdmin ? $currentAdmin->business_id : $currentStaff->business_id;

            // Log business and user information
            Log::info('Business and User Details', [
                'business_id' => $businessId,
                'user_id' => $currentUser->id,
                'is_admin' => (bool)$currentAdmin,
                'is_staff' => (bool)$currentStaff
            ]);

            // Find a default supplier ledger for the business
            $defaultSupplierLedger = Ledger::where('business_id', $businessId)
                ->where('ledger_type', 'Sundry Creditors (Supplier)')
                ->first();

            Log::info('Default Supplier Ledger', [
                'default_ledger_id' => $defaultSupplierLedger ? $defaultSupplierLedger->id : 'No default ledger found'
            ]);

            $totalDamageValue = 0;

            // Calculate total damage value first
            foreach ($request->damage_lines as $line) {
                $totalDamageValue += $line['quantity'] * $line['unit_price'];
            }

            // Update the inventory transaction's grand total
            $inventoryTransaction = \App\Models\InventoryTransaction::findOrFail($request->inventory_transaction_id);
            $inventoryTransaction->grand_total -= $totalDamageValue;
            $inventoryTransaction->save();

            // First, create the damage transaction
            $damageTransaction = DamageTransaction::create([
                'business_id' => $businessId,
                'inventory_transaction_id' => $request->inventory_transaction_id,
                'customer_ledger_id' => $request->customer_ledger_id,
                'supplier_ledger_id' => $defaultSupplierLedger ? $defaultSupplierLedger->id : null,
                'created_by' => $currentUser->id,
                'transaction_date' => $request->transaction_date,
                'status' => 'pending'
            ]);

            Log::info('Damage Transaction Created', [
                'transaction_id' => $damageTransaction->id
            ]);

            $totalDamageValue = 0;
            $supplierLedgerIds = [];

            // Process damage lines
            foreach ($request->damage_lines as $line) {
                // Find the product and its category
                $product = Product::with('category')->findOrFail($line['product_id']);

                Log::info('Product Details', [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'category_id' => $product->category ? $product->category->id : 'No Category',
                    'category_ledger_id' => $product->category ? $product->category->ledger_id : 'No Ledger'
                ]);

                // Find supplier ledger associated with the category
                $supplierLedgerId = $product->category && $product->category->ledger_id
                    ? $product->category->ledger_id
                    : ($defaultSupplierLedger ? $defaultSupplierLedger->id : null);

                Log::info('Supplier Ledger for Product', [
                    'product_id' => $product->id,
                    'supplier_ledger_id' => $supplierLedgerId
                ]);

                if ($supplierLedgerId) {
                    $supplierLedgerIds[] = $supplierLedgerId;
                }

                // Create damage transaction line
                $damageTransactionLine = DamageTransactionLine::create([
                    'damage_transaction_id' => $damageTransaction->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total_value' => $line['quantity'] * $line['unit_price'],
                    'damage_reason' => $line['damage_reason'] ?? null
                ]);

                $totalDamageValue += $damageTransactionLine->total_value;
            }

            // Remove duplicate supplier ledgers
            $supplierLedgerIds = array_unique($supplierLedgerIds);

            Log::info('Unique Supplier Ledgers', [
                'ledger_ids' => $supplierLedgerIds
            ]);

            // Update the damage transaction with a specific supplier ledger if found
            if (!empty($supplierLedgerIds)) {
                $damageTransaction->update([
                    'supplier_ledger_id' => $supplierLedgerIds[0]
                ]);
            } elseif (!$defaultSupplierLedger) {
                throw new \Exception('No supplier ledgers found for the damaged products');
            }

            // Create Accounting Transaction
            $this->createAccountingTransaction($damageTransaction, $totalDamageValue);

            DB::commit();

            return response()->json([
                'message' => 'Damage transaction saved successfully',
                'transaction_id' => $damageTransaction->id,
                'supplier_ledgers' => $supplierLedgerIds
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Damage Transaction Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to process damage transaction',
                'details' => $e->getMessage()
            ], 500);
        }
    }



    private function createAccountingTransaction(DamageTransaction $damageTransaction, float $totalDamageValue)
    {
        $transaction = Transaction::create([
            'business_id' => $damageTransaction->business_id,
            'transaction_type' => 'Journal',
            'transaction_date' => $damageTransaction->transaction_date,
            'amount' => $totalDamageValue,
            'narration' => "Damage Goods Transaction - ID {$damageTransaction->id}"
        ]);

        // Get damage lines with products and categories
        $damageLines = DamageTransactionLine::where('damage_transaction_id', $damageTransaction->id)
            ->with(['product.category'])
            ->get();

        // Group products by staff categories
        $staffCategoryProducts = [];

        foreach ($damageLines as $line) {
            $categoryId = $line->product->category_id;

            // Group by staff category
            $staffWithCategory = Staff::whereHas('productCategories', function ($query) use ($categoryId) {
                $query->where('product_category_id', $categoryId);
            })->first();

            if ($staffWithCategory) {
                if (!isset($staffCategoryProducts[$staffWithCategory->id])) {
                    $staffCategoryProducts[$staffWithCategory->id] = [
                        'staff' => $staffWithCategory,
                        'total' => 0
                    ];
                }
                $staffCategoryProducts[$staffWithCategory->id]['total'] += $line->total_value;
            }
        }

        // Credit Customer Ledger
        TransactionLine::create([
            'transaction_id' => $transaction->id,
            'ledger_id' => $damageTransaction->customer_ledger_id,
            'debit_amount' => 0,
            'credit_amount' => $totalDamageValue,
            'narration' => 'Damage Goods Return'
        ]);

        // Create staff ledger entries for their specific products
        foreach ($staffCategoryProducts as $staffData) {
            $staffLedger = $staffData['staff']->ledgers()->first();
            if ($staffLedger) {
                TransactionLine::create([
                    'transaction_id' => $transaction->id,
                    'ledger_id' => $staffLedger->id,
                    'debit_amount' =>  $staffData['total'],
                    'credit_amount' => 0,
                    'narration' => 'Damage Goods Processing - Category Products'
                ]);

                // Recalculate staff ledger balance
                $this->recalcLedgerBalance($staffLedger);
            }
        }

        // Recalculate customer ledger balance
        $this->recalcLedgerBalance(Ledger::find($damageTransaction->customer_ledger_id));
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


    public function index()
    {
        $currentUser = Auth::user();
        $currentAdmin = BusinessAdmin::where('user_id', $currentUser->id)->first();
        $currentStaff = Staff::where('user_id', $currentUser->id)->first();
        $businessId = $currentAdmin ? $currentAdmin->business_id : $currentStaff->business_id;

        $damages = DamageTransaction::with(['customer', 'supplier', 'creator', 'lines.product'])
            ->where('business_id', $businessId)
            ->latest()
            ->paginate(10);

        return view('admin.damage.index', compact('damages'));
    }


    public function create()
    {
        $currentUser = Auth::user();
        $currentAdmin = BusinessAdmin::where('user_id', $currentUser->id)->first();
        $currentStaff = Staff::where('user_id', $currentUser->id)->first();
        $businessId = $currentAdmin ? $currentAdmin->business_id : $currentStaff->business_id;

        $products = Product::where('business_id', $businessId)->get();
        $customers = Ledger::where('business_id', $businessId)
            ->where('ledger_type', 'Sundry Debtors (Customer)')
            ->get();

        return view('admin.damage.create', compact('products', 'customers'));
    }


    public function show(DamageTransaction $damage)
    {
        $damage->load(['customer', 'supplier', 'creator', 'lines.product']);
        return view('admin.damage.show', compact('damage'));
    }
    public function edit(DamageTransaction $damage)
    {
        $currentUser = Auth::user();
        $currentAdmin = BusinessAdmin::where('user_id', $currentUser->id)->first();
        $currentStaff = Staff::where('user_id', $currentUser->id)->first();
        $businessId = $currentAdmin ? $currentAdmin->business_id : $currentStaff->business_id;

        $products = Product::where('business_id', $businessId)->get();
        $customers = Ledger::where('business_id', $businessId)
            ->where('ledger_type', 'Sundry Debtors (Customer)')
            ->get();

        $damage->load(['lines.product']);

        return view('admin.damage.edit', compact('damage', 'products', 'customers'));
    }

    public function update(Request $request, DamageTransaction $damage)
    {
        DB::beginTransaction();
        try {
            $damage->update([
                'customer_ledger_id' => $request->customer_ledger_id,
                'transaction_date' => $request->transaction_date,
                'status' => $request->status ?? 'pending'
            ]);

            // Update damage lines
            $damage->lines()->delete();
            foreach ($request->damage_lines as $line) {
                DamageTransactionLine::create([
                    'damage_transaction_id' => $damage->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total_value' => $line['quantity'] * $line['unit_price'],
                    'damage_reason' => $line['damage_reason'] ?? null
                ]);
            }

            $this->createAccountingTransaction($damage, $damage->lines->sum('total_value'));
            DB::commit();

            return redirect()->route('admin.damage.index')->with('success', 'Damage transaction updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Damage Update Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Failed to update damage transaction');
        }
    }

    public function destroy(DamageTransaction $damage)
    {
        DB::beginTransaction();
        try {
            // Delete related transaction lines first
            $damage->lines()->delete();

            // Delete the main damage transaction
            $damage->delete();

            DB::commit();
            return redirect()->route('admin.damage.index')
                ->with('success', 'Damage transaction deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.damage.index')
                ->with('error', 'Failed to delete damage transaction');
        }
    }
    public function getProducts(DamageTransaction $damage)
    {
        $products = DamageTransactionLine::where('damage_transaction_id', $damage->id)
            ->with('product')
            ->get()
            ->map(function ($line) {
                return [
                    'id' => $line->product_id,
                    'name' => $line->product->name ?? 'Unknown Product',
                    'quantity' => $line->quantity,
                    'unit_price' => $line->unit_price,
                    'total_value' => $line->total_value,
                    'damage_reason' => $line->damage_reason
                ];
            });

        return response()->json([
            'success' => true,
            'products' => $products
        ]);
    }

    public function getBatches($productId)
    {
        $product = Product::findOrFail($productId);
        $batches = $product->batches()
            ->where('remaining_quantity', '>', 0)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($batch) {
                // Make sure we're getting the dp_price correctly
                // If it's not available directly, we need to find where it's stored

                // Option 1: If dp_price is a direct field on the batch model
                $dpPrice = $batch->dealer_price ?? 0;

                // Option 2: If dp_price is stored in a different field
                if (empty($dpPrice) && isset($batch->purchase_price)) {
                    $dpPrice = $batch->purchase_price;
                }

                // Option 3: If dp_price needs to be calculated or retrieved from a related model
                if (empty($dpPrice) && $batch->product) {
                    $dpPrice = $batch->product->purchase_price;
                }

                // Ensure we have a numeric value
                $dpPrice = is_numeric($dpPrice) ? $dpPrice : 0;

                // Log the value for debugging
                Log::info('Batch DP Price', [
                    'batch_id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'dp_price' => $dpPrice,
                    'raw_dp_price' => $batch->dp_price ?? null,
                    'batch_data' => $batch->toArray()
                ]);

                return [
                    'id' => $batch->id,
                    'batch_number' => $batch->batch_number,
                    'dp_price' => $dpPrice,
                    'remaining_quantity' => $batch->remaining_quantity,
                    'expiry_date' => $batch->expiry_date ? $batch->expiry_date->format('d M Y') : 'N/A'
                ];
            });

        return response()->json([
            'success' => true,
            'product_name' => $product->name,
            'batches' => $batches
        ]);
    }
    public function toggleStatus(Request $request, DamageTransaction $damage)
    {
        // Validate the request
        $request->validate([
            'status' => 'required|in:pending,approved,rejected'
        ]);

        $newStatus = $request->status;

        DB::beginTransaction();
        try {
            // Only process supplier ledger entries when approving a transaction that wasn't already approved
            if ($newStatus === 'approved' && $damage->status !== 'approved') {
                // Get batch selections from request
                $batchSelections = json_decode($request->batch_selections, true);
                $calculatedTotal = floatval($request->calculated_total);

                if (empty($batchSelections)) {
                    return redirect()->route('admin.damage.index')
                        ->with('error', 'No batch selections provided. Please select batches for all damaged products.');
                }

                // Create journal transaction
                $journalTransaction = Transaction::create([
                    'business_id' => $damage->business_id,
                    'transaction_type' => 'Journal',
                    'transaction_date' => $damage->transaction_date,
                    'amount' => $calculatedTotal,
                    'narration' => "Damage Goods Supplier Compensation - ID {$damage->id}"
                ]);

                // Group products by supplier ledgers
                $supplierProducts = [];
                $totalPriceAdjustment = 0;

                foreach ($batchSelections as $productId => $selection) {
                    $product = Product::with('category')->findOrFail($productId);

                    // Explicitly fetch the batch to get trade price
                    $batchId = $selection['batch_id'];
                    $batch = DB::table('product_batches')->where('id', $batchId)->first();

                    $dpPrice = floatval($selection['dp_price']);
                    $quantity = floatval($selection['quantity']);
                    $totalValue = $quantity * $dpPrice;

                    // Try to find trade price from batch
                    $tradePrice = 0;
                    if ($batch) {
                        // Check various possible column names for trade price
                        if (property_exists($batch, 'trade_price')) {
                            $tradePrice = $batch->trade_price;
                        } elseif (property_exists($batch, 'selling_price')) {
                            $tradePrice = $batch->selling_price;
                        } elseif (property_exists($batch, 'mrp')) {
                            $tradePrice = $batch->mrp;
                        } elseif (property_exists($batch, 'retail_price')) {
                            $tradePrice = $batch->retail_price;
                        }
                    }

                    // If trade price is still 0, try to get it from product
                    if ($tradePrice == 0) {
                        $tradePrice = $product->trade_price ?? 0;
                    }



                    // Calculate price adjustment (trade_price - dealer_price) * quantity
                    $priceAdjustment = ($tradePrice - $dpPrice) * $quantity;
                    $totalPriceAdjustment += $priceAdjustment;



                    // Get supplier ledger from product category
                    $supplierLedgerId = $product->category->ledger_id ?? $damage->supplier_ledger_id;

                    if (!$supplierLedgerId) {
                        throw new \Exception("No supplier ledger found for product ID {$productId}");
                    }


                    if (!isset($supplierProducts[$supplierLedgerId])) {
                        $supplierProducts[$supplierLedgerId] = [
                            'total' => 0,
                            'products' => [],
                            'price_adjustment' => 0
                        ];
                    }
                    $supplierProducts[$supplierLedgerId]['total'] += $totalValue;
                    $supplierProducts[$supplierLedgerId]['price_adjustment'] += $priceAdjustment;
                    $supplierProducts[$supplierLedgerId]['products'][] = [
                        'name' => $product->name,
                        'quantity' => $quantity,
                        'dp_price' => $dpPrice,
                        'trade_price' => $tradePrice,
                        'total' => $totalValue,
                        'price_adjustment' => $priceAdjustment
                    ];
                }

                // Create supplier ledger entries - debit suppliers for damage value
                foreach ($supplierProducts as $supplierLedgerId => $data) {
                    $productDetails = implode(', ', array_map(function ($p) {
                        return "{$p['name']} ({$p['quantity']} x {$p['dp_price']})";
                    }, $data['products']));

                    // Debit supplier for the damage value (dealer price * quantity)
                    $damageValueLine = TransactionLine::create([
                        'transaction_id' => $journalTransaction->id,
                        'ledger_id' => $supplierLedgerId,
                        'debit_amount' => $data['total'],
                        'credit_amount' => 0,
                        'narration' => 'Damage Goods Supplier Compensation: ' . $productDetails
                    ]);



                    // Also debit supplier for the price adjustment (if non-zero)
                    if ($data['price_adjustment'] != 0) {
                        $adjustmentLine = TransactionLine::create([
                            'transaction_id' => $journalTransaction->id,
                            'ledger_id' => $supplierLedgerId,
                            'debit_amount' => $data['price_adjustment'] > 0 ? $data['price_adjustment'] : 0,
                            'credit_amount' => $data['price_adjustment'] < 0 ? abs($data['price_adjustment']) : 0,
                            'narration' => 'Damage Price Adjustment (TP-DP): ' . $productDetails
                        ]);
                    }

                    // Recalculate supplier ledger balance
                    $this->recalcLedgerBalance(Ledger::find($supplierLedgerId));
                }

                // Log the successful transaction
                Log::info('Damage Transaction Approved', [
                    'damage_id' => $damage->id,
                    'calculated_total' => $calculatedTotal,
                    'supplier_ledgers' => array_keys($supplierProducts),
                    'total_price_adjustment' => $totalPriceAdjustment
                ]);
            }

            // Update the status - use a direct query to ensure proper quoting
            DB::table('damage_transactions')
                ->where('id', $damage->id)
                ->update(['status' => $newStatus]);

            // Refresh the model to get the updated status
            $damage->refresh();

            DB::commit();

            return redirect()
                ->route('admin.damage.index')
                ->with('success', "Damage transaction status updated to " . ucfirst($newStatus));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Damage Transaction Status Update Error', [
                'damage_id' => $damage->id,
                'requested_status' => $newStatus,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()
                ->route('admin.damage.index')
                ->with('error', 'Failed to update damage transaction status: ' . $e->getMessage());
        }
    }
}
