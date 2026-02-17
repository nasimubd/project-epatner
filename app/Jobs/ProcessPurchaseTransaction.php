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

class ProcessPurchaseTransaction implements ShouldQueue
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
        Log::info('Processing purchase transaction', [
            'transaction_id' => $this->transactionId
        ]);

        DB::transaction(function () {
            $transaction = InventoryTransaction::findOrFail($this->transactionId);

            foreach ($this->lines as $line) {
                $this->processPurchaseLine($transaction, $line);
            }

            // Create accounting entries
            $this->createPurchaseJournalEntries($transaction);

            $transaction->status = 'completed';
            $transaction->save();
        });
    }

    private function processPurchaseLine($transaction, $line)
    {
        $product = Product::findOrFail($line['product_id']);

        // Create new batch for purchased products
        $batch = ProductBatch::create([
            'product_id' => $product->id,
            'batch_number' => 'B-' . time() . '-' . $product->id,
            'dealer_price' => $line['dealer_price'] ?? $line['unit_price'],
            'trade_price' => $line['unit_price'],
            'remaining_quantity' => $line['quantity'],
            'batch_date' => now()->toDateString(),
            'expiry_date' => $line['expiry_date'] ?? null,
            'is_opening_batch' => false
        ]);

        // Create transaction line
        InventoryTransactionLine::create([
            'inventory_transaction_id' => $transaction->id,
            'product_id' => $product->id,
            'batch_id' => $batch->id,
            'quantity' => $line['quantity'],
            'unit_price' => $line['unit_price'],
            'dealer_price' => $line['dealer_price'] ?? $line['unit_price'],
            'line_total' => $line['line_total']
        ]);

        // Update product stock
        $product->current_stock += $line['quantity'];
        $product->save();
    }

    private function createPurchaseJournalEntries($transaction)
    {
        $journalTransaction = Transaction::create([
            'business_id' => $transaction->business_id,
            'transaction_type' => 'Journal',
            'transaction_date' => $transaction->transaction_date,
            'amount' => $transaction->grand_total,
            'narration' => "Purchase Transaction - ID {$transaction->id}"
        ]);

        $stockLedger = Ledger::where('business_id', $transaction->business_id)
            ->where('ledger_type', 'Stock-in-Hand')
            ->firstOrFail();

        // Debit Stock-in-Hand
        TransactionLine::create([
            'transaction_id' => $journalTransaction->id,
            'ledger_id' => $stockLedger->id,
            'debit_amount' => $transaction->grand_total,
            'credit_amount' => 0,
            'narration' => 'Stock Purchase'
        ]);

        if ($transaction->payment_method === 'cash') {
            $cashLedger = Ledger::where([
                'business_id' => $transaction->business_id,
                'ledger_type' => 'Cash-in-Hand'
            ])->firstOrFail();

            // Credit Cash
            TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $cashLedger->id,
                'debit_amount' => 0,
                'credit_amount' => $transaction->grand_total,
                'narration' => 'Cash Purchase'
            ]);

            $this->recalcLedgerBalance($cashLedger);
        } else {
            // Credit Supplier
            TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $transaction->ledger_id,
                'debit_amount' => 0,
                'credit_amount' => $transaction->grand_total,
                'narration' => 'Credit Purchase'
            ]);
        }

        $this->recalcLedgerBalance($stockLedger);
        $this->recalcLedgerBalance(Ledger::find($transaction->ledger_id));
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
