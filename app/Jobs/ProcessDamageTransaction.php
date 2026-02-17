<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;
use App\Models\DamageTransaction;
use App\Models\DamageTransactionLine;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Ledger;
use App\Models\TransactionLine;
use App\Models\Transaction;

class ProcessDamageTransaction implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $inventoryTransactionId;
    protected $damageLines;
    public $tries = 3;
    public $backoff = [60, 120, 180];
    public $timeout = 180;

    public function __construct(int $inventoryTransactionId, array $damageLines)
    {
        $this->inventoryTransactionId = $inventoryTransactionId;
        $this->damageLines = $damageLines;
    }

    public function handle()
    {
        Log::info('Starting damage transaction processing', [
            'inventory_transaction_id' => $this->inventoryTransactionId
        ]);

        DB::transaction(function () {
            $inventoryTransaction = InventoryTransaction::findOrFail($this->inventoryTransactionId);

            $damageTransaction = DamageTransaction::create([
                'business_id' => $inventoryTransaction->business_id,
                'inventory_transaction_id' => $this->inventoryTransactionId,
                'customer_ledger_id' => $inventoryTransaction->ledger_id,
                'transaction_date' => now(),
                'status' => 'processing'
            ]);

            $totalDamageValue = 0;
            $supplierLedgerIds = [];

            foreach ($this->damageLines as $line) {
                $product = Product::findOrFail($line['product_id']);

                // Get supplier ledger ID if exists
                $supplierLedgerId = $product->category->ledger_id ?? null;
                if ($supplierLedgerId) {
                    $supplierLedgerIds[] = $supplierLedgerId;
                }

                $damageTransactionLine = DamageTransactionLine::create([
                    'damage_transaction_id' => $damageTransaction->id,
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'total_value' => $line['quantity'] * $line['unit_price'],
                    'damage_reason' => $line['damage_reason'] ?? 'Damaged during delivery'
                ]);

                $totalDamageValue += $damageTransactionLine->total_value;

                // Update product stock
                $product->current_stock -= $line['quantity'];
                $product->save();
            }

            // Create accounting entries for damage
            $this->createDamageAccountingEntries(
                $damageTransaction,
                array_unique($supplierLedgerIds),
                $totalDamageValue
            );

            $damageTransaction->status = 'completed';
            $damageTransaction->save();

            Log::info('Damage transaction completed', [
                'damage_transaction_id' => $damageTransaction->id,
                'total_value' => $totalDamageValue
            ]);
        });
    }

    private function createDamageAccountingEntries($damageTransaction, $supplierLedgerIds, $totalValue)
    {
        $journalTransaction = Transaction::create([
            'business_id' => $damageTransaction->business_id,
            'transaction_type' => 'Journal',
            'transaction_date' => $damageTransaction->transaction_date,
            'amount' => $totalValue,
            'narration' => "Damage Transaction - ID {$damageTransaction->id}"
        ]);

        // Debit Supplier Ledgers proportionally
        foreach ($supplierLedgerIds as $supplierId) {
            TransactionLine::create([
                'transaction_id' => $journalTransaction->id,
                'ledger_id' => $supplierId,
                'debit_amount' => $totalValue / count($supplierLedgerIds),
                'credit_amount' => 0,
                'narration' => 'Damage Claim'
            ]);
        }

        // Recalculate ledger balances
        foreach ($supplierLedgerIds as $supplierId) {
            $supplierLedger = Ledger::find($supplierId);
            $this->recalcLedgerBalance($supplierLedger);
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

    public function tags()
    {
        return ['damage_transaction', 'inventory_transaction:' . $this->inventoryTransactionId];
    }

    public function failed(\Throwable $exception)
    {
        Log::error('Damage transaction processing failed', [
            'inventory_transaction_id' => $this->inventoryTransactionId,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
