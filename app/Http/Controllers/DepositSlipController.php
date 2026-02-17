<?php

namespace App\Http\Controllers;

use App\Models\DepositSlip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Staff;
use App\Models\BusinessAdmin;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Ledger;
use App\Models\TransactionLine;
use Illuminate\Support\Facades\Log;

class DepositSlipController extends Controller
{
    public function index()
    {
        // Get the current user
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name');

        // Query builder for deposit slips
        $query = DepositSlip::with('user');

        // If user is admin, show all deposit slips for their business
        if ($userRoles->contains('admin')) {
            $admin = BusinessAdmin::where('user_id', $user->id)->first();
            if ($admin) {
                $query->where('business_id', $admin->business_id);
            }
        }
        // If user is staff or DSR, only show their own deposit slips
        else if ($userRoles->contains('staff') || $userRoles->contains('dsr')) {
            $query->where('user_id', $user->id);
        }

        // Get the deposit slips with pagination
        $deposits = $query->latest()->paginate(10);

        return view('admin.accounting.deposit.index', compact('deposits'));
    }


    public function create()
    {
        // Get the current user
        $user = Auth::user();
        $userRoles = $user->roles->pluck('name');
        $ledgers = collect();
        $supplierLedgers = collect();

        if ($userRoles->contains('staff') || $userRoles->contains('dsr')) {
            // For staff and DSR, get their ledgers
            $staff = Staff::where('user_id', $user->id)->first();

            if ($staff) {
                $ledgers = $staff->ledgers()->get();

                // Get supplier ledgers for the same business
                $supplierLedgers = Ledger::where([
                    'business_id' => $staff->business_id,
                    'ledger_type' => 'Sundry Creditors (Supplier)'
                ])->get();
            }
        } elseif ($userRoles->contains('admin')) {
            // For admin, get all ledgers for their business
            $admin = BusinessAdmin::where('user_id', $user->id)->first();

            if ($admin) {
                $ledgers = Ledger::where('business_id', $admin->business_id)->get();

                // Get supplier ledgers for the same business
                $supplierLedgers = Ledger::where([
                    'business_id' => $admin->business_id,
                    'ledger_type' => 'Sundry Creditors (Supplier)'
                ])->get();
            }
        }

        return view('admin.accounting.deposit.create', compact('ledgers', 'supplierLedgers'));
    }




    // DepositSlipController.php
    public function store(Request $request)
    {
        $businessId = null;

        // Get the current user
        $user = Auth::user();

        // Check user roles using the contains method
        $userRoles = $user->roles->pluck('name');

        if ($userRoles->contains('staff') || $userRoles->contains('dsr')) {
            // For both staff and DSR, get the business_id from the Staff model
            $staff = Staff::where('user_id', Auth::id())->first();

            if (!$staff) {
                return redirect()->back()->with('error', 'Staff record not found for your account.');
            }

            $businessId = $staff->business_id;
        } elseif ($userRoles->contains('admin')) {
            // For admin, get the business_id from the BusinessAdmin model
            $admin = BusinessAdmin::where('user_id', Auth::id())->first();

            if (!$admin) {
                return redirect()->back()->with('error', 'Admin record not found for your account.');
            }

            $businessId = $admin->business_id;
        }

        // Check if business ID was found
        if (!$businessId) {
            return redirect()->back()->with('error', 'Could not determine your business. Please contact support.');
        }

        $validated = $request->validate([
            'count' => 'nullable|array',
            'count.*' => 'nullable|integer|min:0',
            'total' => 'required|numeric|min:0', // Cash amount must be positive
            'net_total' => 'nullable|numeric', // Allow any value for net_total, including negative
            'expense_description' => 'nullable|array',
            'expense_amount' => 'nullable|array',
            'ledger_id' => 'required|exists:ledgers,id',
            'total_collection' => 'nullable|numeric',
            'due_collection' => 'nullable|numeric|min:0', // Add validation for due collection
            'damage_amount' => 'nullable|array',
            'damage_amount.*' => 'nullable|numeric|min:0',
            'damage_supplier_ledger' => 'nullable|array',
            'damage_supplier_ledger.*' => 'nullable|exists:ledgers,id',
            'market_short' => 'nullable|numeric|min:0',
            'godown_short' => 'nullable|numeric|min:0'
        ]);

        // Create expenses array
        $expenses = array_map(function ($description, $amount) {
            return [
                'description' => $description,
                'amount' => $amount
            ];
        }, $request->expense_description ?? [], $request->expense_amount ?? []);

        // Create damage lines array
        $damageLines = [];
        if (!empty($request->damage_amount) && !empty($request->damage_supplier_ledger)) {
            $damageLines = array_map(function ($amount, $supplierLedgerId) {
                return [
                    'amount' => $amount,
                    'supplier_ledger_id' => $supplierLedgerId
                ];
            }, $request->damage_amount, $request->damage_supplier_ledger);

            // Filter out empty damage lines
            $damageLines = array_filter($damageLines, function ($line) {
                return !empty($line['amount']) && !empty($line['supplier_ledger_id']);
            });
        }

        // Log the values for debugging
        Log::info('Creating deposit slip', [
            'user_id' => Auth::id(),
            'business_id' => $businessId,
            'total_amount' => $validated['total'],
            'user_roles' => $userRoles,
            'ledger_id' => $validated['ledger_id'],
            'total_collection' => $validated['total_collection'] ?? 0,
            'due_collection' => $validated['due_collection'] ?? 0,
            'net_total' => $validated['net_total'] ?? 0,
            'damage_lines_count' => count($damageLines),
            'market_short' => $validated['market_short'] ?? 0,
            'godown_short' => $validated['godown_short'] ?? 0
        ]);

        // Round values to ensure no fractions
        $totalAmount = round($validated['total']);
        $totalCollection = round($validated['total_collection'] ?? 0);
        $dueCollection = round($validated['due_collection'] ?? 0);
        $netTotal = round($validated['net_total'] ?? 0);
        $marketShort = round($validated['market_short'] ?? 0);
        $godownShort = round($validated['godown_short'] ?? 0);

        $depositSlip = DepositSlip::create([
            'user_id' => Auth::id(),
            'business_id' => $businessId,
            'ledger_id' => $validated['ledger_id'],
            'total_amount' => $totalAmount,
            'total_collection' => $totalCollection,
            'due_collection' => $dueCollection,
            'net_total' => $netTotal,
            'note_denominations' => $validated['count'],
            'damage_lines' => $damageLines,
            'market_short' => $marketShort,
            'godown_short' => $godownShort,
            'remarks' => json_encode($expenses),
            'status' => 'pending'
        ]);

        return redirect()
            ->route('admin.accounting.deposit.index')
            ->with('success', 'Deposit slip created successfully');
    }

    public function show(DepositSlip $depositSlip)
    {
        // Load the necessary relationships
        $depositSlip->load(['user', 'business']);

        return view('admin.accounting.deposit.show', compact('depositSlip'));
    }

    public function updateStatus(Request $request, DepositSlip $depositSlip)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,approved,rejected'
        ]);

        // Refresh the deposit slip to ensure we have the latest data
        $depositSlip->refresh();

        Log::info('Starting deposit slip status update', [
            'deposit_slip_id' => $depositSlip->id,
            'current_status' => $depositSlip->status,
            'new_status' => $validated['status'],
            'user_id' => $depositSlip->user_id,
            'business_id' => $depositSlip->business_id,
            'ledger_id' => $depositSlip->ledger_id,
            'total_amount' => $depositSlip->total_amount,
            'total_collection' => $depositSlip->total_collection,
            'due_collection' => $depositSlip->due_collection,
            'deposit_slip_data' => $depositSlip->toArray()
        ]);

        DB::beginTransaction();
        try {
            if ($validated['status'] === 'approved') {
                Log::info('Processing approval for deposit slip', [
                    'deposit_slip_id' => $depositSlip->id,
                    'total_amount' => $depositSlip->total_amount,
                    'total_collection' => $depositSlip->total_collection,
                    'due_collection' => $depositSlip->due_collection,
                    'market_short' => $depositSlip->market_short,
                    'godown_short' => $depositSlip->godown_short,
                    'damage_lines' => $depositSlip->damage_lines
                ]);

                // Create journal transaction
                $journalTransaction = Transaction::create([
                    'business_id' => $depositSlip->business_id,
                    'transaction_type' => 'Journal',
                    'transaction_date' => now(),
                    'amount' => $depositSlip->total_amount,
                    'narration' => "Deposit Slip #{$depositSlip->id} Approval"
                ]);

                Log::info('Journal transaction created', [
                    'transaction_id' => $journalTransaction->id,
                    'amount' => $journalTransaction->amount
                ]);

                // Get Cash-in-Hand ledger
                $cashLedger = Ledger::where([
                    'business_id' => $depositSlip->business_id,
                    'ledger_type' => 'Cash-in-Hand'
                ])->firstOrFail();

                Log::info('Cash ledger found', [
                    'ledger_id' => $cashLedger->id,
                    'ledger_name' => $cashLedger->name,
                    'current_balance' => $cashLedger->current_balance
                ]);

                // Get staff and verify ledger assignment
                $staff = Staff::where('user_id', $depositSlip->user_id)->first();

                if (!$staff) {
                    throw new \Exception('Staff record not found for user_id: ' . $depositSlip->user_id);
                }

                Log::info('Staff found', [
                    'staff_id' => $staff->id,
                    'user_id' => $staff->user_id
                ]);

                // Get the ledger that was selected in the deposit slip
                $selectedLedgerId = $depositSlip->ledger_id;

                if (!$selectedLedgerId) {
                    throw new \Exception('No ledger selected in deposit slip');
                }

                // Verify this ledger exists and belongs to the business
                $staffLedgerModel = Ledger::where('id', $selectedLedgerId)
                    ->where('business_id', $depositSlip->business_id)
                    ->first();

                if (!$staffLedgerModel) {
                    throw new \Exception('Selected ledger not found or does not belong to this business. Ledger ID: ' . $selectedLedgerId);
                }

                Log::info('Staff ledger model found', [
                    'ledger_id' => $staffLedgerModel->id,
                    'ledger_name' => $staffLedgerModel->name,
                    'ledger_type' => $staffLedgerModel->ledger_type,
                    'current_balance' => $staffLedgerModel->current_balance
                ]);

                // Get expense ledger
                $expenseLedger = Ledger::where([
                    'business_id' => $depositSlip->business_id,
                    'ledger_type' => 'Expenses'
                ])->first();

                if (!$expenseLedger) {
                    throw new \Exception('Expense ledger not found');
                }

                Log::info('Expense ledger found', [
                    'ledger_id' => $expenseLedger->id,
                    'ledger_name' => $expenseLedger->name
                ]);

                // Debit Cash-in-Hand with the total cash amount
                $cashTransactionLine = TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $cashLedger->id,
                    'debit_amount' => $depositSlip->total_amount,
                    'credit_amount' => 0,
                    'narration' => 'Cash Deposit'
                ]);

                Log::info('Cash transaction line created', [
                    'transaction_line_id' => $cashTransactionLine->id,
                    'debit_amount' => $cashTransactionLine->debit_amount
                ]);

                // Credit Staff Ledger with the total collection amount (including due collection)
                $totalCollectionAmount = $depositSlip->total_collection + $depositSlip->due_collection;
                $staffTransactionLine = TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $selectedLedgerId,
                    'debit_amount' => 0,
                    'credit_amount' => $totalCollectionAmount,
                    'narration' => 'Deposit Collection (Including Due: ৳' . number_format($depositSlip->due_collection) . ')'
                ]);

                Log::info('Staff transaction line created', [
                    'transaction_line_id' => $staffTransactionLine->id,
                    'credit_amount' => $staffTransactionLine->credit_amount,
                    'total_collection_amount' => $totalCollectionAmount
                ]);

                // Handle expenses if any
                $expenseAmount = $totalCollectionAmount - $depositSlip->total_amount;
                Log::info('Calculated expense amount', [
                    'expense_amount' => $expenseAmount,
                    'total_collection' => $totalCollectionAmount,
                    'total_cash' => $depositSlip->total_amount
                ]);

                // Handle expenses if any
                $expenses = json_decode($depositSlip->remarks, true);
                $actualExpenseAmount = 0;

                // Calculate actual expense amount from JSON
                if (!empty($expenses)) {
                    foreach ($expenses as $expense) {
                        if (isset($expense['amount']) && is_numeric($expense['amount'])) {
                            $actualExpenseAmount += $expense['amount'];
                        }
                    }
                }

                Log::info('Calculated expense amount', [
                    'actual_expense_amount' => $actualExpenseAmount,
                    'total_collection' => $totalCollectionAmount,
                    'total_cash' => $depositSlip->total_amount,
                    'expenses_from_json' => $expenses
                ]);

                if ($actualExpenseAmount > 0) {
                    Log::info('Processing expenses', [
                        'expenses' => $expenses,
                        'actual_expense_amount' => $actualExpenseAmount
                    ]);

                    // Debit Expense Ledger with actual expense amount
                    $expenseTransactionLine = TransactionLine::create([
                        'transaction_id' => $journalTransaction->id,
                        'ledger_id' => $expenseLedger->id,
                        'debit_amount' => $actualExpenseAmount,  // Use actual expense amount
                        'credit_amount' => 0,
                        'narration' => 'Deposit Expenses: ' . implode(', ', array_column($expenses ?: [], 'description'))
                    ]);

                    Log::info('Expense transaction line created', [
                        'transaction_line_id' => $expenseTransactionLine->id,
                        'debit_amount' => $expenseTransactionLine->debit_amount
                    ]);
                }

                // Also update the balance recalculation condition
                if ($actualExpenseAmount > 0) {  // Change this condition too
                    $this->recalcLedgerBalance($expenseLedger);
                    $expenseLedger->refresh();
                    Log::info('Expense ledger balance recalculated', [
                        'new_balance' => $expenseLedger->current_balance
                    ]);
                }

                // Handle Damage Lines
                if (!empty($depositSlip->damage_lines)) {
                    $damageLines = is_string($depositSlip->damage_lines) ?
                        json_decode($depositSlip->damage_lines, true) :
                        $depositSlip->damage_lines;

                    Log::info('Processing damage lines', [
                        'damage_lines_count' => count($damageLines),
                        'damage_lines' => $damageLines
                    ]);

                    foreach ($damageLines as $index => $damageLine) {
                        Log::info('Processing damage line', [
                            'index' => $index,
                            'damage_line' => $damageLine
                        ]);

                        if (isset($damageLine['amount']) && $damageLine['amount'] > 0 && isset($damageLine['supplier_ledger_id'])) {
                            // Verify supplier ledger exists
                            $supplierLedger = Ledger::find($damageLine['supplier_ledger_id']);
                            if (!$supplierLedger) {
                                Log::warning('Supplier ledger not found', [
                                    'supplier_ledger_id' => $damageLine['supplier_ledger_id']
                                ]);
                                continue;
                            }

                            // Debit Supplier Ledger for damage amount
                            $damageTransactionLine = TransactionLine::create([
                                'transaction_id' => $journalTransaction->id,
                                'ledger_id' => $damageLine['supplier_ledger_id'],
                                'debit_amount' => $damageLine['amount'],
                                'credit_amount' => 0,
                                'narration' => 'Damage Amount'
                            ]);

                            Log::info('Damage transaction line created', [
                                'transaction_line_id' => $damageTransactionLine->id,
                                'supplier_ledger_id' => $damageLine['supplier_ledger_id'],
                                'supplier_name' => $supplierLedger->name,
                                'debit_amount' => $damageLine['amount']
                            ]);
                        } else {
                            Log::warning('Skipping invalid damage line', [
                                'index' => $index,
                                'damage_line' => $damageLine
                            ]);
                        }
                    }
                }

                // Handle Market Short
                if ($depositSlip->market_short > 0) {
                    Log::info('Processing market short', [
                        'market_short_amount' => $depositSlip->market_short,
                        'user_id' => $depositSlip->user_id
                    ]);

                    // Find user's salary payable ledger from salary_heads table
                    $salaryHead = DB::table('salary_heads')
                        ->where('user_id', $depositSlip->user_id)
                        ->first();

                    if ($salaryHead) {
                        Log::info('Salary head found', [
                            'salary_head_id' => $salaryHead->id ?? 'N/A',
                            'salary_account_ledger_id' => $salaryHead->salary_account_ledger_id
                        ]);

                        // Verify salary ledger exists
                        $salaryLedger = Ledger::find($salaryHead->salary_account_ledger_id);
                        if (!$salaryLedger) {
                            Log::warning('Salary ledger not found', [
                                'salary_account_ledger_id' => $salaryHead->salary_account_ledger_id
                            ]);
                        } else {
                            // Debit Salary Payable Ledger for market short
                            $marketShortTransactionLine = TransactionLine::create([
                                'transaction_id' => $journalTransaction->id,
                                'ledger_id' => $salaryHead->salary_account_ledger_id,
                                'debit_amount' => $depositSlip->market_short,
                                'credit_amount' => 0,
                                'narration' => 'Market Short'
                            ]);

                            Log::info('Market short transaction line created', [
                                'transaction_line_id' => $marketShortTransactionLine->id,
                                'salary_ledger_name' => $salaryLedger->name,
                                'debit_amount' => $depositSlip->market_short
                            ]);
                        }
                    } else {
                        Log::warning('Salary head not found for user', [
                            'user_id' => $depositSlip->user_id
                        ]);
                    }
                }

                // Handle Godown Short
                if ($depositSlip->godown_short > 0) {
                    Log::info('Processing godown short', [
                        'godown_short_amount' => $depositSlip->godown_short
                    ]);

                    // Find Inventory Loss ledger
                    $inventoryLossLedger = Ledger::where([
                        'business_id' => $depositSlip->business_id,
                        'ledger_type' => 'Inventory Loss'
                    ])->first();

                    if ($inventoryLossLedger) {
                        Log::info('Inventory loss ledger found', [
                            'ledger_id' => $inventoryLossLedger->id,
                            'ledger_name' => $inventoryLossLedger->name
                        ]);

                        // Debit Inventory Loss Ledger for godown short
                        $godownShortTransactionLine = TransactionLine::create([
                            'transaction_id' => $journalTransaction->id,
                            'ledger_id' => $inventoryLossLedger->id,
                            'debit_amount' => $depositSlip->godown_short,
                            'credit_amount' => 0,
                            'narration' => 'Godown Short'
                        ]);

                        Log::info('Godown short transaction line created', [
                            'transaction_line_id' => $godownShortTransactionLine->id,
                            'debit_amount' => $depositSlip->godown_short
                        ]);
                    } else {
                        Log::warning('Inventory loss ledger not found', [
                            'business_id' => $depositSlip->business_id
                        ]);
                    }
                }

                Log::info('Starting ledger balance recalculations');

                // Recalculate ledger balances
                $this->recalcLedgerBalance($cashLedger);
                $cashLedger->refresh();
                Log::info('Cash ledger balance recalculated', [
                    'new_balance' => $cashLedger->current_balance
                ]);

                $this->recalcLedgerBalance($staffLedgerModel);
                $staffLedgerModel->refresh();
                Log::info('Staff ledger balance recalculated', [
                    'new_balance' => $staffLedgerModel->current_balance
                ]);

                if ($expenseAmount > 0) {
                    $this->recalcLedgerBalance($expenseLedger);
                    $expenseLedger->refresh();
                    Log::info('Expense ledger balance recalculated', [
                        'new_balance' => $expenseLedger->current_balance
                    ]);
                }

                // Recalculate balances for damage line supplier ledgers
                if (!empty($depositSlip->damage_lines)) {
                    $damageLines = is_string($depositSlip->damage_lines) ?
                        json_decode($depositSlip->damage_lines, true) :
                        $depositSlip->damage_lines;

                    foreach ($damageLines as $damageLine) {
                        if (isset($damageLine['supplier_ledger_id'])) {
                            $supplierLedger = Ledger::find($damageLine['supplier_ledger_id']);
                            if ($supplierLedger) {
                                $this->recalcLedgerBalance($supplierLedger);
                                $supplierLedger->refresh();
                                Log::info('Supplier ledger balance recalculated', [
                                    'supplier_name' => $supplierLedger->name,
                                    'new_balance' => $supplierLedger->current_balance
                                ]);
                            }
                        }
                    }
                }

                // Recalculate balance for salary payable ledger (market short)
                if ($depositSlip->market_short > 0) {
                    $salaryHead = DB::table('salary_heads')
                        ->where('user_id', $depositSlip->user_id)
                        ->first();
                    if ($salaryHead) {
                        $salaryLedger = Ledger::find($salaryHead->salary_account_ledger_id);
                        if ($salaryLedger) {
                            $this->recalcLedgerBalance($salaryLedger);
                            $salaryLedger->refresh();
                            Log::info('Salary ledger balance recalculated', [
                                'salary_ledger_name' => $salaryLedger->name,
                                'new_balance' => $salaryLedger->current_balance
                            ]);
                        }
                    }
                }

                // Recalculate balance for inventory loss ledger (godown short)
                if ($depositSlip->godown_short > 0) {
                    $inventoryLossLedger = Ledger::where([
                        'business_id' => $depositSlip->business_id,
                        'ledger_type' => 'Inventory Loss'
                    ])->first();
                    if ($inventoryLossLedger) {
                        $this->recalcLedgerBalance($inventoryLossLedger);
                        $inventoryLossLedger->refresh();
                        Log::info('Inventory loss ledger balance recalculated', [
                            'inventory_loss_ledger_name' => $inventoryLossLedger->name,
                            'new_balance' => $inventoryLossLedger->current_balance
                        ]);
                    }
                }

                Log::info('All ledger balances recalculated successfully');
            }

            $depositSlip->update($validated);
            Log::info('Deposit slip status updated', [
                'deposit_slip_id' => $depositSlip->id,
                'new_status' => $validated['status']
            ]);

            DB::commit();
            Log::info('Transaction committed successfully');

            return redirect()
                ->route('admin.accounting.deposit.index')
                ->with('success', 'Deposit slip status updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update deposit slip status', [
                'deposit_slip_id' => $depositSlip->id,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage()
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

    public function destroy(DepositSlip $depositSlip)
    {
        DB::beginTransaction();
        try {
            // Delete associated transaction lines and transaction
            $transaction = Transaction::where([
                'business_id' => $depositSlip->business_id,
                'narration' => "Deposit Slip #{$depositSlip->id} Approval"
            ])->first();

            if ($transaction) {
                // Delete transaction lines
                TransactionLine::where('transaction_id', $transaction->id)->delete();
                // Delete transaction
                $transaction->delete();
            }

            // Delete deposit slip
            $depositSlip->delete();

            DB::commit();
            return redirect()
                ->route('admin.accounting.deposit.index')
                ->with('success', 'Deposit slip and associated entries deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }
}
