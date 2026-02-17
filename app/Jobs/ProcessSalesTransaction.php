<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\InventoryTransactionLine;
use App\Models\Transaction;
use App\Models\TransactionLine;
use App\Models\Ledger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Staff;

class ProcessSalesTransaction implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionId;
    protected $lines;
    public $tries = 3;
    public $backoff = [60, 120, 180];

    public function __construct(int $transactionId, array $lines)
    {
        $this->transactionId = $transactionId;
        $this->lines = $lines;
    }

    public function handle()
    {
        Log::info('Processing sales transaction', [
            'transaction_id' => $this->transactionId
        ]);

        DB::transaction(function () {
            $transaction = InventoryTransaction::findOrFail($this->transactionId);

            foreach ($this->lines as $line) {
                $this->processSaleLine($transaction, $line);
            }

            // Create accounting entries
            $this->createAccountingEntries($transaction);

            $transaction->status = 'completed';
            $transaction->save();
        });
    }


    private function processSaleLine($transaction, $line)
    {
        $product = Product::findOrFail($line['product_id']);
        $remainingQtyToDeduct = $line['quantity'];

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

            InventoryTransactionLine::create([
                'inventory_transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'batch_id' => $batch->id,
                'quantity' => $qtyToDeduct,
                'unit_price' => $line['unit_price'],
                'line_total' => $qtyToDeduct * $line['unit_price']
            ]);

            $remainingQtyToDeduct -= $qtyToDeduct;
        }

        $product->current_stock -= $line['quantity'];
        $product->save();
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

        $this->createSaleJournalEntries($journalTransaction, $transaction);
    }

    private function createSaleJournalEntries($journalTransaction, $transaction)
    {
        $paymentLedger = $this->findPaymentLedger($transaction);
        $currentUser = Auth::user();
        $currentStaff = Staff::where('user_id', $currentUser->id)->first();
        $isAdmin = $currentUser->hasRole('admin');

        $transactionLines = InventoryTransactionLine::where('inventory_transaction_id', $transaction->id)
            ->with('product')
            ->get();

        $stockLedger = Ledger::where('business_id', $transaction->business_id)
            ->where('ledger_type', 'Stock-in-Hand')
            ->firstOrFail();

        // Credit Stock-in-Hand ledger
        TransactionLine::create([
            'transaction_id' => $journalTransaction->id,
            'ledger_id' => $stockLedger->id,
            'debit_amount' => 0,
            'credit_amount' => $transaction->grand_total,
            'narration' => 'Sales Products'
        ]);

        if ($isAdmin) {
            // For admin users - new accounting entry system
            if ($transaction->payment_method === 'cash') {
                // Debit Cash ledger
                TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $paymentLedger->id,
                    'debit_amount' => $transaction->grand_total,
                    'credit_amount' => 0,
                    'narration' => 'Cash Sales'
                ]);

                // Credit Sales ledger
                TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $transaction->ledger_id,
                    'debit_amount' => 0,
                    'credit_amount' => $transaction->grand_total,
                    'narration' => 'Sales Revenue'
                ]);
            } else {
                // For credit sales
                // Debit Customer ledger
                TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $transaction->ledger_id,
                    'debit_amount' => $transaction->grand_total,
                    'credit_amount' => 0,
                    'narration' => 'Credit Sales'
                ]);

                // Credit Sales ledger
                TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $transaction->ledger_id,
                    'debit_amount' => 0,
                    'credit_amount' => $transaction->grand_total,
                    'narration' => 'Sales Revenue'
                ]);
            }
        } else {
            // For staff users - keep existing functionality
            if ($transaction->payment_method === 'cash') {
                TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $paymentLedger->id,
                    'debit_amount' => $transaction->grand_total,
                    'credit_amount' => 0,
                    'narration' => 'Cash Sales'
                ]);

                TransactionLine::create([
                    'transaction_id' => $journalTransaction->id,
                    'ledger_id' => $transaction->ledger_id,
                    'debit_amount' => 0,
                    'credit_amount' => $transaction->grand_total,
                    'narration' => 'Sales Receivable'
                ]);
            }

            // Handle staff category pricing adjustments
            if ($currentStaff) {
                $staffCategories = $currentStaff->productCategories->pluck('id');
                $staffCategoryTotal = 0;

                foreach ($transactionLines as $line) {
                    if ($staffCategories->contains($line->product->category_id)) {
                        $staffCategoryTotal += $line->line_total;
                    }
                }

                if ($staffCategoryTotal > 0) {
                    $staffLedger = Ledger::where('business_id', $transaction->business_id)
                        ->where('ledger_type', 'Staff')
                        ->firstOrFail();

                    TransactionLine::create([
                        'transaction_id' => $journalTransaction->id,
                        'ledger_id' => $staffLedger->id,
                        'debit_amount' => 0,
                        'credit_amount' => $staffCategoryTotal,
                        'narration' => 'Credit Sales'
                    ]);

                    foreach ($transactionLines as $line) {
                        if ($staffCategories->contains($line->product->category_id)) {
                            $priceDifference = ($line->unit_price - $line->product->trade_price) * $line->quantity;

                            TransactionLine::create([
                                'transaction_id' => $journalTransaction->id,
                                'ledger_id' => $staffLedger->id,
                                'debit_amount' => $priceDifference > 0 ? $priceDifference : 0,
                                'credit_amount' => $priceDifference < 0 ? abs($priceDifference) : 0,
                                'narration' => sprintf(
                                    "Over pricing adjustment - %s (TP: %s, SP: %s)",
                                    $line->product->name,
                                    number_format($line->product->trade_price, 2),
                                    number_format($line->unit_price, 2)
                                )
                            ]);
                        }
                    }
                }
            }
        }

        // Recalculate balances for all affected ledgers
        $this->recalcLedgerBalance($paymentLedger);
        $this->recalcLedgerBalance($transaction->ledger);
        $this->recalcLedgerBalance($stockLedger);
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
