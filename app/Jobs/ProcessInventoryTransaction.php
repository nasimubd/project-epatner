<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionLine;
use App\Models\ProductBatch;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use App\Models\TransactionLine;
use App\Models\Ledger;
use App\Models\Staff;
use Illuminate\Support\Facades\Auth;

class ProcessInventoryTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionData;
    protected $businessId;
    protected $userId;

    public function __construct(array $transactionData, int $businessId, int $userId)
    {
        $this->transactionData = $transactionData;
        $this->businessId = $businessId;
        $this->userId = $userId;
    }

    public function handle()
    {
        $debugData = [
            'business_id' => $this->businessId,
            'entry_type' => $this->transactionData['entry_type'],
            'transaction_data' => $this->transactionData
        ];

        Log::info('Starting inventory transaction processing', $debugData);

        // Verify database connection
        try {
            DB::connection()->getPdo();
            Log::info('Database connection successful');
        } catch (\Exception $e) {
            Log::critical('Database connection failed', ['error' => $e->getMessage()]);
            throw new \Exception('Database connection failed: ' . $e->getMessage());
        }

        DB::beginTransaction();
        try {
            // Track transaction creation
            Log::info('Creating inventory transaction record');

            $transaction = InventoryTransaction::create([
                'business_id' => $this->businessId,
                'entry_type' => $this->transactionData['entry_type'],
                'transaction_date' => $this->transactionData['transaction_date'],
                'ledger_id' => $this->transactionData['ledger_id'],
                'payment_method' => $this->transactionData['payment_method'],
                'subtotal' => $this->transactionData['subtotal'],
                'round_off' => $this->transactionData['round_off'],
                'discount' => $this->transactionData['discount'],
                'grand_total' => $this->transactionData['grand_total'],
                'status' => 'processing'
            ]);

            Log::info('Transaction record created', ['transaction_id' => $transaction->id]);

            // Process line items with validation
            foreach ($this->transactionData['lines'] as $lineData) {
                $product = Product::findOrFail($lineData['product_id']);

                $line = (object)[
                    'inventory_transaction_id' => $transaction->id,
                    'unit_price' => $lineData['unit_price'],
                    'dealer_price' => $lineData['dealer_price'] ?? null,
                    'line_discount' => $lineData['line_discount'] ?? 0,
                    'line_total' => $lineData['line_total']
                ];

                $this->updateProductStock($product, $lineData['quantity'], $this->transactionData['entry_type'], $line);
            }


            // Create accounting entries
            Log::info('Creating accounting entries');
            $this->createAccountingEntries($transaction);

            // Update transaction status
            $transaction->update(['status' => 'completed']);

            DB::commit();
            Log::info('Transaction completed successfully', ['transaction_id' => $transaction->id]);

            return $transaction->id;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function updateProductStock($product, $quantity, $entryType, $line)
    {
        if ($entryType === 'purchase') {
            // Update product stock
            $product->current_stock += $quantity;
            $product->save();

            // Create new batch
            $batch = ProductBatch::create([
                'product_id' => $product->id,
                'batch_number' => 'B-' . time(),
                'dealer_price' => $line->unit_price, // Using unit_price as dealer_price if not provided
                'trade_price' => $line->unit_price,
                'remaining_quantity' => $quantity,
                'batch_date' => now()->toDateString(),
                'expiry_date' => null,
                'is_opening_batch' => false
            ]);

            // Now create the inventory transaction line with complete data
            InventoryTransactionLine::create([
                'inventory_transaction_id' => $line->inventory_transaction_id,
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'quantity' => $quantity,
                'unit_price' => $line->unit_price,
                'dealer_price' => $line->unit_price, // Using unit_price as dealer_price
                'line_total' => $line->line_total
            ]);
        }

        if ($entryType === 'sale') {
            $product->current_stock -= $quantity;
            $product->save();

            $remainingQtyToDeduct = $quantity;
            $batches = ProductBatch::where('product_id', $product->id)
                ->where('remaining_quantity', '>', 0)
                ->orderBy('batch_date', 'asc')
                ->get();

            foreach ($batches as $batch) {
                if ($remainingQtyToDeduct <= 0) break;

                $qtyToDeduct = min($batch->remaining_quantity, $remainingQtyToDeduct);
                $batch->remaining_quantity -= $qtyToDeduct;
                $batch->save();

                // Create inventory transaction line for each batch in sale
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
        }
    }


    private function createAccountingEntries($transaction)
    {
        $journalTransaction = Transaction::create([
            'business_id' => $transaction->business_id,
            'transaction_type' => 'Journal',
            'transaction_date' => $transaction->transaction_date,
            'amount' => $transaction->grand_total,
            'narration' => "Inventory Transaction - ID {$transaction->id}"
        ]);

        if ($transaction->entry_type === 'sale') {
            $this->createSaleJournalEntries($journalTransaction, $transaction);
        } else {
            $this->createPurchaseJournalEntries($journalTransaction, $transaction);
        }
    }

    /**
     * Creates journal entries for an inventory sale transaction.
     * @param Transaction $journalTransaction
     * @param InventoryTransaction $transaction
     */
    private function createSaleJournalEntries($journalTransaction, $transaction)
    {

        // Get the payment ledger
        $paymentLedger = $this->findPaymentLedger($transaction);
        $currentUser = Auth::user();
        $currentStaff = Staff::where('user_id', $currentUser->id)->first();


        // Get transaction lines with their discounts
        $transactionLines = InventoryTransactionLine::where('inventory_transaction_id', $transaction->id)
            ->with('product')
            ->get();

        // Get Stock-in-Hand Ledger
        $stockLedger = Ledger::where('business_id', $transaction->business_id)
            ->where('ledger_type', 'Stock-in-Hand')
            ->firstOrFail();

        // Credit Stock-in-Hand Ledger for the sale
        TransactionLine::create([
            'transaction_id' => $journalTransaction->id,
            'ledger_id' => $stockLedger->id,
            'debit_amount' => 0,
            'credit_amount' => $transaction->grand_total,
            'narration' => 'Sales Products'
        ]);

        if ($transaction->payment_method === 'cash') {
            // Cash Sale: Debit Cash Ledger
            TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $paymentLedger->id,
                'debit_amount' => $transaction->grand_total,
                'credit_amount' => 0,
                'narration' => 'Cash Sales'
            ]);

            // Credit Customer Ledger
            TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $transaction->ledger_id,
                'debit_amount' => 0,
                'credit_amount' => $transaction->grand_total,
                'narration' => 'Sales Receivable'
            ]);
        }
        if ($transaction->payment_method === 'credit') {
            // Credit Sale: Debit Customer Ledger
            TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $transaction->ledger_id,
                'debit_amount' => $transaction->grand_total,
                'credit_amount' => 0,
                'narration' => 'Credit Sales Receivable'
            ]);

            // For credit sales, check if products belong to staff category
            if ($currentStaff) {
                $staffCategories = $currentStaff->productCategories->pluck('id');
                $staffLedger = $currentStaff->ledgers()->first();

                if ($staffLedger) {
                    $staffCategoryTotal = $transactionLines
                        ->filter(function ($line) use ($staffCategories) {
                            return $staffCategories->contains($line->product->category_id);
                        })
                        ->sum('line_total');

                    if ($staffCategoryTotal > 0) {

                        // Credit staff ledger with total amount of their category products
                        TransactionLine::create([
                            'transaction_id' => $journalTransaction->id,
                            'ledger_id' => $staffLedger->id,
                            'debit_amount' => 0,
                            'credit_amount' => $staffCategoryTotal,
                            'narration' => 'Credit Sales'
                        ]);
                    }
                }
            }
        }

        // Recalculate balances for all affected ledgers
        $paymentLedger = $this->findPaymentLedger($transaction);
        $customerLedger = Ledger::find($transaction->ledger_id);

        $this->recalcLedgerBalance($paymentLedger);
        $this->recalcLedgerBalance($customerLedger);
        $this->recalcLedgerBalance($stockLedger);

        if ($currentStaff && isset($staffLedger)) {
            $this->recalcLedgerBalance($staffLedger);
        }
    }

    private function createPurchaseJournalEntries($journalTransaction, $transaction)
    {
        // Find Stock in Hand Ledger
        $stockLedger = Ledger::where('business_id', $transaction->business_id)
            ->where('ledger_type', 'Stock-in-Hand')
            ->first();

        // Find Purchase Accounts Ledger
        $purchaseLedger = Ledger::where('business_id', $transaction->business_id)
            ->where('ledger_type', 'Purchase Accounts')
            ->first();

        if (!$stockLedger) {
            Log::error('Stock Ledger not found', ['business_id' => $transaction->business_id]);
            throw new \Exception('Stock-in-Hand ledger not found');
        }

        if ($transaction->payment_method === 'cash') {
            // Find Cash Ledger
            $cashLedger = Ledger::where([
                'business_id' => $transaction->business_id,
                'ledger_type' => 'Cash-in-Hand'
            ])->first();

            if (!$cashLedger) {
                throw new \Exception('Cash ledger not found');
            }

            // Debit Stock in Hand
            TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $stockLedger->id,
                'debit_amount' => $transaction->grand_total,
                'credit_amount' => 0,
                'narration' => 'Stock Purchase'
            ]);

            // Credit Cash
            TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $cashLedger->id,
                'debit_amount' => 0,
                'credit_amount' => $transaction->grand_total,
                'narration' => 'Cash Purchase'
            ]);

            // Dabit Purchase Account
            TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $purchaseLedger->id,
                'debit_amount' => $transaction->grand_total,
                'credit_amount' => 0,
                'narration' => 'Cash Purchase'
            ]);
        } else {
            // Get transaction lines with product categories
            $transactionLines = InventoryTransactionLine::where('inventory_transaction_id', $transaction->id)
                ->with(['product.category'])
                ->get();

            // Debit Stock in Hand
            TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $stockLedger->id,
                'debit_amount' => $transaction->grand_total,
                'credit_amount' => 0,
                'narration' => 'Stock Purchase'
            ]);

            // Debit Purchase Account
            TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $purchaseLedger->id,
                'debit_amount' => $transaction->grand_total,
                'credit_amount' => 0,
                'narration' => 'Credit Purchase'
            ]);

            // Group by category and create supplier entries
            $categoryTotals = [];
            foreach ($transactionLines as $line) {
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
            foreach ($categoryTotals as $categoryId => $data) {
                $category = $data['category'];
                if ($category && $category->ledger_id) {
                    TransactionLine::create([
                        'transaction_id' => $journalTransaction->id,
                        'ledger_id' => $category->ledger_id,
                        'debit_amount' => 0,
                        'credit_amount' => $data['total'],
                        'narration' => "Credit Purchase: {$category->name}"
                    ]);
                }
            }
        }

        // Recalculate balances for all affected ledgers
        $stockLedger = Ledger::where('business_id', $transaction->business_id)
            ->where('ledger_type', 'Stock-in-Hand')
            ->first();

        $this->recalcLedgerBalance($purchaseLedger);
        $this->recalcLedgerBalance($stockLedger);

        if ($transaction->payment_method === 'cash') {
            $cashLedger = Ledger::where([
                'business_id' => $transaction->business_id,
                'ledger_type' => 'Cash-in-Hand'
            ])->first();
            $this->recalcLedgerBalance($cashLedger);
        } else {
            // Recalc supplier ledger balances for credit purchase
            foreach ($categoryTotals as $categoryId => $data) {
                $category = $data['category'];
                if ($category && $category->ledger_id) {
                    $supplierLedger = Ledger::find($category->ledger_id);
                    $this->recalcLedgerBalance($supplierLedger);
                }
            }
        }
    }


    private function findPaymentLedger($transaction)
    {
        // For cash transactions
        if ($transaction->payment_method === 'cash') {
            $ledger = Ledger::where([
                'business_id' => $transaction->business_id,
                'ledger_type' => 'Cash-in-Hand'
            ])->first();

            if (!$ledger) {
                throw new \Exception('Cash ledger not found for this business');
            }

            return $ledger;
        }

        // For credit transactions
        $ledger = Ledger::where([
            'business_id' => $transaction->business_id,
            'ledger_type' => 'Sundry Debtors (Customer)'
        ])->first();

        if (!$ledger) {
            throw new \Exception('Customer ledger not found for this business');
        }

        return $ledger;
    }

    // Add the recalcLedgerBalance method from TransactionController
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
}
