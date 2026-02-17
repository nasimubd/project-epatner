<?php

namespace App\Http\Controllers;


use App\Models\TransactionLine;
use App\Models\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\BusinessAdmin;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\InventoryTransaction;
use App\Models\Staff;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\InventoryTransactionLine;

class LedgerController extends Controller
{
    /**
     * Show the list of ledgers for the authenticated user's business.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $query = Ledger::query();

        // Get the authenticated user's business ID through staff or admin relationship
        $businessId = null;

        if (Auth::user()->roles->contains('name', 'staff')) {
            $staff = Staff::where('user_id', Auth::id())->first();
            $businessId = $staff->business_id;
        } elseif (Auth::user()->roles->contains('name', 'admin')) {
            $admin = BusinessAdmin::where('user_id', Auth::id())->first();
            $businessId = $admin->business_id;
        }

        // Check if we need to refresh ledger balances
        if ($request->has('refresh_ledgers')) {
            // Get all ledgers for the current business
            $ledgers = Ledger::where('business_id', $businessId)->get();

            // Recalculate balance for each ledger
            foreach ($ledgers as $ledger) {
                $this->recalcLedgerBalance($ledger);
            }

            return redirect()->route('admin.accounting.ledgers.index')
                ->with('success', 'All ledger balances have been refreshed successfully!');
        }

        // Apply search filters if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Apply type filter if provided
        if ($request->filled('type')) {
            $query->where('ledger_type', $request->type);
        }

        // Filter ledgers by business_id
        $query->where('business_id', $businessId);

        $ledgers = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('admin.accounting.ledgers.index', compact('ledgers'));
    }

    /**
     * Recalculate and update the given Ledger's current_balance
     * by combining its opening_balance plus/minus all debits/credits
     * from its transaction lines.
     */
    /**
     * Recalculate the current_balance = opening_balance + all (Dr/Cr) 
     * from transaction_lines for this ledger.
     */
    private function recalcLedgerBalance(Ledger $ledger): void
    {
        $drLedgers = [
            'Bank Accounts',
            'Cash-in-Hand',
            'Expenses',
            'Fixed Assets',
            'Investments',
            'Loans & Advances (Asset)',
            'Purchase Accounts',
            'Sundry Debtors (Customer)'
        ];

        // Start with opening balance
        $currentBalance = $ledger->opening_balance ?? 0;

        // Get all transaction lines for this ledger
        $transactionLines = TransactionLine::where('ledger_id', $ledger->id)->get();

        // Calculate running balance based on transaction lines
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


    /**
     * Show the form for creating a new ledger.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.accounting.ledgers.create');
    }

    public function store(Request $request)
    {
        // Step 1: Debug the authenticated user and admin
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        $data = $request->validate([
            'name' => 'required|string',
            'ledger_type' => 'required|string',
            'opening_balance' => 'numeric',
            'contact' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'required|in:active,inactive,default',
        ]);

        $drLedgers = [
            'Bank Accounts',
            'Cash-in-Hand',
            'Stock-in-Hand',
            'Expenses',
            'Fixed Assets',
            'Investments',
            'Loans & Advances (Asset)',
            'Purchase Accounts',
            'Sundry Debtors (Customer)'
        ];

        // Automatically set balance_type based on ledger_type
        $data['balance_type'] = in_array($data['ledger_type'], $drLedgers) ? 'Dr' : 'Cr';
        $data['business_id'] = $currentAdmin->business_id;
        $data['current_balance'] = $data['opening_balance'] ?? 0;



        // Handle default status
        if ($data['status'] === 'default') {
            // Step 6: Debug the query that will update other ledgers
            $query = Ledger::where('business_id', $currentAdmin->business_id)
                ->where('ledger_type', $data['ledger_type'])
                ->where('status', 'default');

            // Set all other ledgers of the same type to active
            $query->update(['status' => 'active']);
        }

        // Create the ledger
        $ledger = Ledger::create($data);

        // Step 8: Debug the created ledger
        Log::info('Created Ledger:', $ledger->toArray());

        return redirect()->route('admin.accounting.ledgers.index')
            ->with('success', 'Ledger created successfully!');
    }

    /**
     * Show the specified ledger with monthly transactions and balance calculations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Ledger  $ledger
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, Ledger $ledger)
    {
        // Get the month from the request or use current month
        $month = $request->input('month', Carbon::now()->format('Y-m'));

        // Parse the month to get start and end dates
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        // Calculate previous and next months for navigation
        $previousMonth = Carbon::createFromFormat('Y-m', $month)->subMonth()->format('Y-m');
        $nextMonth = Carbon::createFromFormat('Y-m', $month)->addMonth()->format('Y-m');

        // Calculate opening balance (all transactions before start date)
        $openingBalance = $this->calculateOpeningBalanceForMonth($ledger, $startDate);

        // Get transaction lines for the current month
        $transactionLines = $ledger->transactionLines()
            ->with('transaction')
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('transaction_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                $query->with('inventoryTransaction');
            })
            ->join('transactions', 'transaction_lines.transaction_id', '=', 'transactions.id')
            ->select('transaction_lines.*')
            ->orderBy('transactions.transaction_date', 'asc')
            ->orderBy('transactions.id', 'asc') // Add this line to order by transaction ID
            ->get();

        // Calculate totals for the month
        $totalDebits = 0;
        $totalCredits = 0;
        $runningBalance = $openingBalance;

        foreach ($transactionLines as $line) {
            $totalDebits += $line->debit_amount;
            $totalCredits += $line->credit_amount;
            $runningBalance += ($line->debit_amount - $line->credit_amount);
        }

        // Determine if closing balance is Dr or Cr
        $isDr = $runningBalance >= 0;
        $closingBalance = [
            'amount' => abs($runningBalance),
            'type' => $isDr ? 'Dr' : 'Cr'
        ];

        // Additional calculations for sales type ledgers
        $salesBreakdown = null;
        if ($ledger->ledger_type === 'Sales Accounts') {
            // Get current date
            $today = Carbon::now()->format('Y-m-d');



            // Calculate today's sales
            $todaySales = TransactionLine::where('ledger_id', $ledger->id)
                ->whereHas('transaction', function ($query) use ($today) {
                    $query->whereDate('transaction_date', $today);
                })
                ->where(function ($query) {
                    $query->where('narration', 'like', '%sale%')
                        ->orWhere('narration', 'like', '%Sale%')
                        ->orWhere('narration', 'like', '%SALE%')
                        ->orWhere('narration', 'like', '%Credit Sale%');
                })
                ->sum('credit_amount');

            // Calculate monthly adjustments - look for adjustment-related narrations
            // Get both credit and debit sums for monthly adjustments
            $monthlyAdjustmentLines = TransactionLine::where('ledger_id', $ledger->id)
                ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('transaction_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                })
                ->where(function ($query) {
                    $query->where('narration', 'like', '%adjust%')
                        ->orWhere('narration', 'like', '%Adjust%')
                        ->orWhere('narration', 'like', '%ADJUST%')
                        ->orWhere('narration', 'like', '%over%')
                        ->orWhere('narration', 'like', '%under%')
                        ->orWhere('narration', 'like', '%Over%')
                        ->orWhere('narration', 'like', '%Under%');
                })
                ->get();

            $monthlyAdjustmentCredits = $monthlyAdjustmentLines->sum('credit_amount');
            $monthlyAdjustmentDebits = $monthlyAdjustmentLines->sum('debit_amount');
            $priceAdjustments = $monthlyAdjustmentDebits - $monthlyAdjustmentCredits;

            // Calculate today's adjustments - get both credit and debit sums
            $todayAdjustmentLines = TransactionLine::where('ledger_id', $ledger->id)
                ->whereHas('transaction', function ($query) use ($today) {
                    $query->whereDate('transaction_date', $today);
                })
                ->where(function ($query) {
                    $query->where('narration', 'like', '%adjust%')
                        ->orWhere('narration', 'like', '%Adjust%')
                        ->orWhere('narration', 'like', '%ADJUST%')
                        ->orWhere('narration', 'like', '%over%')
                        ->orWhere('narration', 'like', '%under%')
                        ->orWhere('narration', 'like', '%Over%')
                        ->orWhere('narration', 'like', '%Under%');
                })
                ->get();

            $todayAdjustmentCredits = $todayAdjustmentLines->sum('credit_amount');
            $todayAdjustmentDebits = $todayAdjustmentLines->sum('debit_amount');
            $todayAdjustments = $todayAdjustmentDebits - $todayAdjustmentCredits;

            // Determine if adjustments are Over or Under
            $monthlyAdjustmentType = $priceAdjustments >= 0 ? 'Over' : 'Under';
            $todayAdjustmentType = $todayAdjustments >= 0 ? 'Over' : 'Under';

            $salesBreakdown = [
                'priceAdjustments' => $priceAdjustments,
                'priceAdjustmentType' => $monthlyAdjustmentType,
                'todayAdjustments' => $todayAdjustments,
                'todayAdjustmentType' => $todayAdjustmentType,
                'todaySales' => $todaySales,
                //'monthlySales' => $monthlySales,
                'monthlyAdjustmentCredits' => $monthlyAdjustmentCredits,
                'monthlyAdjustmentDebits' => $monthlyAdjustmentDebits,
                'todayAdjustmentCredits' => $todayAdjustmentCredits,
                'todayAdjustmentDebits' => $todayAdjustmentDebits
            ];
        }

        // For pagination in the view
        $paginatedLines = $ledger->transactionLines()
            ->with('transaction')
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('transaction_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
            })
            ->join('transactions', 'transaction_lines.transaction_id', '=', 'transactions.id')
            ->select('transaction_lines.*')
            ->orderBy('transactions.transaction_date', 'desc')
            ->orderBy('transactions.id', 'desc') // Add this line to order by transaction ID in reverse
            ->paginate(15);

        return view('admin.accounting.ledgers.show', compact(
            'ledger',
            'transactionLines',
            'paginatedLines',
            'month',
            'previousMonth',
            'nextMonth',
            'openingBalance',
            'closingBalance',
            'totalDebits',
            'totalCredits',
            'salesBreakdown'
        ));
    }

    /**
     * Calculate the opening balance for a ledger at the start of a given month.
     *
     * @param  \App\Models\Ledger  $ledger
     * @param  \Carbon\Carbon  $startDate
     * @return float
     */
    private function calculateOpeningBalanceForMonth(Ledger $ledger, Carbon $startDate)
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

        // Start with opening balance
        $openingBalance = $ledger->opening_balance ?? 0;

        // Get all transaction lines before the start date
        $previousTransactions = TransactionLine::where('ledger_id', $ledger->id)
            ->whereHas('transaction', function ($query) use ($startDate) {
                $query->where('transaction_date', '<', $startDate->format('Y-m-d'));
            })
            ->get();

        // Calculate running balance based on transaction lines
        foreach ($previousTransactions as $line) {
            if (in_array($ledger->ledger_type, $drLedgers)) {
                $openingBalance += $line->debit_amount;
                $openingBalance -= $line->credit_amount;
            } else {
                $openingBalance -= $line->debit_amount;
                $openingBalance += $line->credit_amount;
            }
        }

        return $openingBalance;
    }



    /**
     * Recalculate the current_balance of the given Ledger
     * by iterating over all its transaction_lines and
     * summing up the debit and credit amounts.
     * 
     * @param  \App\Models\Ledger  $ledger
     * @return \Illuminate\Http\Response
     */
    public function recalculateBalance(Ledger $ledger)
    {
        DB::beginTransaction();
        try {
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

            // Start with opening balance
            $currentBalance = $ledger->opening_balance ?? 0;

            // Get all transaction lines for this ledger
            $transactionLines = TransactionLine::where('ledger_id', $ledger->id)
                ->orderBy('created_at', 'asc')
                ->get();

            // Calculate running balance based on transaction lines
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

            DB::commit();
            return redirect()->back()->with('success', 'Ledger balance recalculated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to recalculate ledger balance: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified ledger.
     *
     * @param  \App\Models\Ledger  $ledger
     * @return \Illuminate\Http\Response
     */
    public function edit(Ledger $ledger)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        if ($ledger->business_id !== $currentAdmin->business_id) {
            abort(403);
        }

        return view('admin.accounting.ledgers.edit', compact('ledger'));
    }


    /**
     * Update the specified ledger's details.
     *
     * This method validates and updates the ledger's attributes, ensuring
     * that the ledger belongs to the current admin's business. It automatically
     * sets the balance type based on the ledger type and persists the changes.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Ledger $ledger
     * @return \Illuminate\Http\RedirectResponse
     * 
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */

    public function update(Request $request, Ledger $ledger)
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        if ($ledger->business_id !== $currentAdmin->business_id) {
            abort(403);
        }

        // Debug the incoming request data
        Log::info('Update Request Data:', $request->all());

        $validated = $request->validate([
            'name' => 'required|string',
            'ledger_type' => 'required|string',
            'contact' => 'nullable|string',
            'location' => 'nullable|string',
            'status' => 'required|in:active,inactive,default',
        ]);

        // Debug the validated data
        Log::info('Validated Update Data:', $validated);

        $drLedgers = [
            'Bank Accounts',
            'Cash-in-Hand',
            'Stock-in-Hand',
            'Expenses',
            'Fixed Assets',
            'Investments',
            'Loans & Advances (Asset)',
            'Purchase Accounts',
            'Sundry Debtors (Customer)'
        ];

        $validated['balance_type'] = in_array($validated['ledger_type'], $drLedgers) ? 'Dr' : 'Cr';

        // Handle default status
        if ($validated['status'] === 'default') {
            // Set all other ledgers of the same type to active
            Ledger::where('business_id', $currentAdmin->business_id)
                ->where('ledger_type', $validated['ledger_type'])
                ->where('status', 'default')
                ->where('id', '!=', $ledger->id)
                ->update(['status' => 'active']);
        }

        // Update the ledger
        $ledger->update($validated);

        // Debug the updated ledger
        Log::info('Updated Ledger:', $ledger->fresh()->toArray());

        return redirect()->route('admin.accounting.ledgers.index')
            ->with('success', 'Ledger updated successfully!');
    }



    /**
     * Remove the specified ledger from storage.
     * 
     * This method first deletes all associated transaction lines and
     * inventory transactions, then finally deletes the ledger itself.
     * 
     * @param  \App\Models\Ledger  $ledger
     * @return \Illuminate\Http\Response
     */
    public function destroy(Ledger $ledger)
    {
        DB::beginTransaction();
        try {
            // Delete all associated transaction lines
            TransactionLine::where('ledger_id', $ledger->id)->delete();

            // Delete all associated inventory transactions
            InventoryTransaction::where('ledger_id', $ledger->id)->delete();

            // Finally delete the ledger
            $ledger->delete();

            DB::commit();
            return redirect()->route('admin.accounting.ledgers.index')
                ->with('success', 'Ledger and all associated transactions deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('admin.accounting.ledgers.index')
                ->with('error', 'Failed to delete ledger: ' . $e->getMessage());
        }
    }

    /**
     * Generate a Profit and Loss report for the current business.
     *
     * This method calculates and compiles financial data including opening stock,
     * total purchases, sales, expenses, closing stock, and income to determine the 
     * net profit or loss for the current business. It handles returns in the same way
     * as the balance sheet method for consistency between financial reports.
     *
     * @return \Illuminate\Contracts\View\View The Profit and Loss report view with
     * financial data and product details.
     */
    public function profitAndLossReport()
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())
            ->with('business')
            ->first();

        // Get all products with their batches and transaction lines
        $products = Product::where('business_id', $currentAdmin->business_id)
            ->with([
                'category',
                'batches',
                'inventoryTransactionLines.inventoryTransaction',
                'inventoryTransactionLines.batch',
                'returnedProducts.batch' // Ensure returned products are loaded with their batches
            ])
            ->get();

        // Calculate Opening Stock Value
        $openingStock = $products->sum(function ($product) {
            return $product->opening_stock * $product->trade_price;
        });

        // Initialize variables for total calculations
        $totalSalesValue = 0;
        $totalCOGS = 0;
        $closingStock = 0;
        $totalPurchases = 0;
        $totalReturnsInventoryValue = 0; // Track returns at dealer price (inventory value)
        $totalReturnsSalesValue = 0;     // Track returns at trade price (sales value)

        // Calculate product-level metrics
        foreach ($products as $product) {
            // Calculate beginning inventory
            $beginningInventory = $product->opening_stock * $product->trade_price;

            // Calculate purchases
            $purchaseLines = $product->inventoryTransactionLines
                ->where('inventoryTransaction.entry_type', 'purchase');
            $purchaseQty = $purchaseLines->sum('quantity') + $product->opening_stock;
            $purchases = $purchaseLines->sum('line_total');

            // Add purchase value to total purchases
            $totalPurchases += $purchases;

            // Calculate sales and revenue
            $salesLines = $product->inventoryTransactionLines
                ->where('inventoryTransaction.entry_type', 'sale');
            $salesQty = $salesLines->sum('quantity');
            $salesValue = $salesLines->sum(function ($line) {
                // Use the trade price from the batch for this line
                return $line->quantity * ($line->batch ? $line->batch->trade_price : 0);
            });

            // Calculate COGS
            $cogs = $salesLines->sum(function ($line) {
                return $line->quantity * $line->dealer_price;
            });

            // Calculate returns - using dealer price for inventory valuation
            $returnedLines = $product->returnedProducts ?? collect();
            $returnsQty = $returnedLines->sum('quantity');

            // Calculate returns value using dealer price for inventory valuation
            $returnsInventoryValue = $returnedLines->sum(function ($return) {
                if ($return->batch) {
                    return $return->quantity * $return->batch->dealer_price;
                } else {
                    $batch = ProductBatch::find($return->batch_id);
                    if ($batch) {
                        return $return->quantity * $batch->dealer_price;
                    }
                    $inventoryLine = InventoryTransactionLine::where([
                        'inventory_transaction_id' => $return->inventory_transaction_id,
                        'product_id' => $return->product_id,
                        'batch_id' => $return->batch_id
                    ])->first();

                    if ($inventoryLine) {
                        return $return->quantity * $inventoryLine->dealer_price;
                    }
                    return $return->quantity * ($return->unit_price * 0.9); // Fallback
                }
            });

            // Calculate returns at trade price for sales adjustment
            $returnSalesValue = $returnedLines->sum(function ($return) {
                if ($return->batch) {
                    return $return->quantity * $return->batch->trade_price;
                } else {
                    $batch = ProductBatch::find($return->batch_id);
                    if ($batch) {
                        return $return->quantity * $batch->trade_price;
                    }
                    // Use the unit_price from the return which should be the trade price
                    return $return->quantity * $return->unit_price;
                }
            });

            // Add to totals
            $totalReturnsInventoryValue += $returnsInventoryValue;
            $totalReturnsSalesValue += $returnSalesValue;
            $totalSalesValue += $salesValue;
            $totalCOGS += $cogs;

            // Calculate closing value using the formula: beginning + purchases - cogs + returns
            $productClosingValue = $beginningInventory + $purchases - $cogs + $returnsInventoryValue;
            $closingStock += $productClosingValue;
        }

        // Get expense and income totals
        $totalExpenses = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('ledger_type', 'Expenses')
            ->sum('current_balance');

        $totalIncome = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('ledger_type', 'Incomes')
            ->sum('current_balance');

        // Adjust total sales by subtracting returns at trade price
        $adjustedSales = $totalSalesValue - $totalReturnsSalesValue;

        // Calculate Profit/Loss with proper handling of returns
        // Debit side: Opening Stock + Purchases + Expenses
        $debitSide = $openingStock + $totalPurchases + $totalExpenses;

        // Credit side: Closing Stock + Adjusted Sales + Income
        $creditSide = $closingStock + $adjustedSales + $totalIncome;

        // Calculate net profit - MATCHING BALANCE SHEET APPROACH
        $netProfit = $creditSide - $debitSide;
        $isProfit = $netProfit >= 0;

        // Add detailed logging to help debug the calculations
        Log::info('Profit and Loss Calculation with Balance Sheet Method', [
            'opening_stock' => $openingStock,
            'total_purchases' => $totalPurchases,
            'total_sales_before_returns' => $totalSalesValue,
            'total_returns_sales_value' => $totalReturnsSalesValue,
            'adjusted_sales' => $adjustedSales,
            'total_returns_inventory_value' => $totalReturnsInventoryValue,
            'closing_stock' => $closingStock,
            'total_expenses' => $totalExpenses,
            'total_income' => $totalIncome,
            'debit_side' => $debitSide,
            'credit_side' => $creditSide,
            'net_profit' => $netProfit,
            'is_profit' => $isProfit
        ]);

        // Return view with updated calculations
        return view('admin.accounting.reports.profit-loss', [
            'business' => $currentAdmin->business,
            'openingStock' => $openingStock,
            'totalPurchases' => $totalPurchases,
            'totalSales' => $adjustedSales, // Use adjusted sales that accounts for returns
            'totalExpenses' => $totalExpenses,
            'closingStock' => $closingStock,
            'totalIncome' => $totalIncome,
            'netProfit' => abs($netProfit),
            'isProfit' => $isProfit,
            'debitSide' => $debitSide,
            'creditSide' => $creditSide,
            'totalReturns' => $totalReturnsSalesValue // Add this to display total returns in the view
        ]);
    }



    public function balanceSheet()
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())
            ->with('business')
            ->first();
        $business = $currentAdmin->business;

        // Get all products with their batches and transaction lines for stock calculation
        $products = Product::where('business_id', $currentAdmin->business_id)
            ->with([
                'category',
                'batches',
                'inventoryTransactionLines.inventoryTransaction',
                'inventoryTransactionLines.batch',
                'returnedProducts.batch',
                'damageTransactionLines' => function ($query) {
                    $query->whereHas('damageTransaction');
                }
            ])
            ->get();

        // Initialize calculation variables
        $closingStock = 0;
        $totalPurchases = 0;
        $openingStock = 0;
        $totalSales = 0;
        $totalCOGS = 0;
        $totalReturnSalesValue = 0;

        // Calculate Opening Stock Value
        $openingStock = $products->sum(function ($product) {
            return $product->opening_stock * $product->trade_price;
        });

        // Calculate product-level metrics
        foreach ($products as $product) {
            // Calculate beginning inventory
            $beginningInventory = $product->opening_stock * $product->trade_price;

            // Calculate purchases
            $purchaseLines = $product->inventoryTransactionLines
                ->where('inventoryTransaction.entry_type', 'purchase');
            $purchases = $purchaseLines->sum('line_total');

            // Add purchase value to total purchases
            $totalPurchases += $purchases;

            // Calculate sales and revenue
            $salesLines = $product->inventoryTransactionLines
                ->where('inventoryTransaction.entry_type', 'sale');

            // Calculate sales value using batch trade price
            $salesValue = $salesLines->sum(function ($line) {
                return $line->quantity * ($line->batch ? $line->batch->trade_price : 0);
            });

            // Calculate COGS
            $cogs = $salesLines->sum(function ($line) {
                return $line->quantity * $line->dealer_price;
            });

            // Calculate returns value for sales adjustment only
            $returnedLines = $product->returnedProducts ?? collect();
            $returnSalesValue = $returnedLines->sum(function ($return) {
                if ($return->batch) {
                    return $return->quantity * $return->batch->trade_price;
                } else {
                    $batch = ProductBatch::find($return->batch_id);
                    if ($batch) {
                        return $return->quantity * $batch->trade_price;
                    }
                    return $return->quantity * $return->unit_price;
                }
            });

            // Calculate closing value using the formula: beginningInventory + purchases - cogs
            $productClosingValue = $beginningInventory + $purchases - $cogs;

            // Add to totals
            $totalSales += $salesValue;
            $totalReturnSalesValue += $returnSalesValue;
            $totalCOGS += $cogs;
            $closingStock += $productClosingValue;
        }

        // Adjust total sales by subtracting returns at trade price
        $adjustedTotalSales = $totalSales - $totalReturnSalesValue;

        // Get expense and income totals
        $totalExpenses = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('ledger_type', 'Expenses')
            ->sum('current_balance');

        $totalIncome = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('ledger_type', 'Incomes')
            ->sum('current_balance');

        // Get P&L account balances from ledgers
        $purchaseAccountBalance = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('ledger_type', 'Purchase Accounts')
            ->sum('current_balance');

        $salesAccountBalance = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('ledger_type', 'Sales Accounts')
            ->sum('current_balance');

        // Use ledger balances for P&L calculation
        $debitSide = $openingStock + $purchaseAccountBalance + $totalExpenses;
        $creditSide = $closingStock + $salesAccountBalance + $totalIncome;
        $netProfitLoss = $creditSide - $debitSide;

        // Get only Balance Sheet ledgers (exclude P&L accounts)
        $ledgers = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('current_balance', '!=', 0)
            ->whereNotIn('ledger_type', [
                'Stock-in-Hand',
                'Purchase Accounts',
                'Sales Accounts',
                'Expenses',
                'Incomes'
            ])
            ->select('id', 'name', 'ledger_type', 'current_balance', 'balance_type')
            ->get();

        // DIAGNOSTIC: Check for any unaccounted ledger types
        $allLedgers = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('current_balance', '!=', 0)
            ->get();

        $accountedTypes = [
            'Stock-in-Hand',
            'Purchase Accounts',
            'Sales Accounts',
            'Expenses',
            'Incomes',
            'Bank Accounts',
            'Cash-in-Hand',
            'Fixed Assets',
            'Investments',
            'Sundry Debtors (Customer)',
            'Loans & Advances (Asset)',
            'Bank OD A/c',
            'Capital Accounts',
            'Duties & Taxes',
            'Loans A/c',
            'Sundry Creditors (Supplier)'
        ];

        $unaccountedLedgers = $allLedgers->whereNotIn('ledger_type', $accountedTypes);

        // Define Balance Sheet account types
        $assetTypes = [
            'Bank Accounts',
            'Cash-in-Hand',
            'Fixed Assets',
            'Investments',
            'Sundry Debtors (Customer)',
            'Loans & Advances (Asset)'
        ];

        $liabilityTypes = [
            'Bank OD A/c',
            'Capital Accounts',
            'Duties & Taxes',
            'Loans A/c',
            'Sundry Creditors (Supplier)'
        ];

        // Group asset ledgers
        $assetLedgers = $ledgers->whereIn('ledger_type', $assetTypes)
            ->groupBy('ledger_type')
            ->map(function ($group) {
                return [
                    'ledgers' => $group,
                    'subtotal' => $group->sum('current_balance')
                ];
            });

        // Group liability ledgers
        $liabilityLedgers = $ledgers->whereIn('ledger_type', $liabilityTypes)
            ->groupBy('ledger_type')
            ->map(function ($group) {
                return [
                    'ledgers' => $group,
                    'subtotal' => $group->sum('current_balance')
                ];
            });

        // Add closing stock to assets
        $assetLedgers->put('Stock-in-Hand', [
            'ledgers' => collect([]),
            'subtotal' => $closingStock
        ]);

        // Calculate totals
        $totalAssets = $assetLedgers->sum('subtotal');
        $totalLiabilities = $liabilityLedgers->sum('subtotal');

        // Add net profit to equity (liability side)
        if ($netProfitLoss != 0) {
            $totalLiabilities += $netProfitLoss;
        }

        // Final balance check
        $difference = $totalAssets - $totalLiabilities;

        // DIAGNOSTIC: Calculate total Dr and Cr balances to verify accounting equation
        $totalDrBalances = $allLedgers->where('balance_type', 'Dr')->sum('current_balance');
        $totalCrBalances = $allLedgers->where('balance_type', 'Cr')->sum('current_balance');
        $accountingEquationDifference = $totalDrBalances - $totalCrBalances;

        // Enhanced diagnostic logging
        Log::info('Balance Sheet Diagnostic - Finding ৳280 Difference', [
            'business_id' => $currentAdmin->business_id,
            'closing_stock_calculated' => $closingStock,
            'net_profit_loss' => $netProfitLoss,
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'final_difference' => $difference,
            'accounting_equation_check' => [
                'total_dr_balances' => $totalDrBalances,
                'total_cr_balances' => $totalCrBalances,
                'dr_minus_cr_difference' => $accountingEquationDifference,
                'should_equal_closing_stock' => $closingStock
            ],
            'unaccounted_ledgers' => $unaccountedLedgers->map(function ($ledger) {
                return [
                    'name' => $ledger->name,
                    'type' => $ledger->ledger_type,
                    'balance' => $ledger->current_balance,
                    'balance_type' => $ledger->balance_type
                ];
            }),
            'stock_vs_equation_check' => [
                'closing_stock' => $closingStock,
                'accounting_equation_difference' => $accountingEquationDifference,
                'variance' => $closingStock - $accountingEquationDifference
            ]
        ]);

        // If the accounting equation difference equals closing stock, we need to adjust
        if (abs($accountingEquationDifference - $closingStock) <= 0.01) {
            Log::info('FOUND THE ISSUE: Accounting equation difference matches closing stock');

            // The difference might be that closing stock should not be added separately
            // if it's already reflected in the ledger balances
            $adjustedTotalAssets = $totalAssets - $closingStock;
            $adjustedDifference = $adjustedTotalAssets - ($totalLiabilities - $netProfitLoss);

            Log::info('Testing adjustment by removing duplicate stock', [
                'original_total_assets' => $totalAssets,
                'adjusted_total_assets' => $adjustedTotalAssets,
                'total_liabilities_without_profit' => $totalLiabilities - $netProfitLoss,
                'adjusted_difference' => $adjustedDifference
            ]);
        }

        return view('admin.accounting.reports.balance-sheet', compact(
            'assetLedgers',
            'liabilityLedgers',
            'totalAssets',
            'totalLiabilities',
            'netProfitLoss',
            'business',
            'closingStock'
        ));
    }



    /**
     * Diagnose balance sheet issues
     */
    public function diagnoseBalanceSheet()
    {
        $currentAdmin = BusinessAdmin::where('user_id', Auth::id())->first();

        // Get ALL ledgers with balances
        $allLedgers = Ledger::where('business_id', $currentAdmin->business_id)
            ->where('current_balance', '!=', 0)
            ->get();

        // Group by ledger type
        $ledgersByType = $allLedgers->groupBy('ledger_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total_balance' => $group->sum('current_balance'),
                'balance_types' => $group->groupBy('balance_type')->map(function ($subGroup) {
                    return [
                        'count' => $subGroup->count(),
                        'total' => $subGroup->sum('current_balance'),
                        'ledgers' => $subGroup->map(function ($ledger) {
                            return [
                                'name' => $ledger->name,
                                'balance' => $ledger->current_balance
                            ];
                        })
                    ];
                })
            ];
        });

        // Calculate total Dr and Cr balances
        $totalDrBalances = $allLedgers->where('balance_type', 'Dr')->sum('current_balance');
        $totalCrBalances = $allLedgers->where('balance_type', 'Cr')->sum('current_balance');

        return response()->json([
            'total_dr_balances' => $totalDrBalances,
            'total_cr_balances' => $totalCrBalances,
            'difference' => $totalDrBalances - $totalCrBalances,
            'ledgers_by_type' => $ledgersByType,
            'balance_sheet_equation_check' => [
                'assets_should_equal_liabilities_plus_equity' => true,
                'current_difference' => $totalDrBalances - $totalCrBalances
            ]
        ]);
    }
}
