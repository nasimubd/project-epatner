<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;
use App\Models\InventoryTransaction;
use Illuminate\Support\Facades\Log;
use App\Jobs\ProcessInventoryLines;
use App\Jobs\ProcessDamageTransaction;

class CreateInventoryTransaction implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionData;
    protected $businessId;
    protected $userId;
    public $tries = 3;
    public $backoff = [60, 120, 180];

    public function __construct(array $transactionData, int $businessId, int $userId)
    {
        $this->transactionData = $transactionData;
        $this->businessId = $businessId;
        $this->userId = $userId;
    }

    public function handle()
    {
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

        if ($this->transactionData['entry_type'] === 'sale') {
            ProcessSalesTransaction::dispatch($transaction->id, $this->transactionData['lines'])
                ->delay(now()->addSeconds(5));

            // Process damage after sales if exists
            if ($this->hasDamageProducts()) {
                ProcessDamageTransaction::dispatch($transaction->id, $this->getDamageProducts())
                    ->delay(now()->addSeconds(10));
            }
        } else {
            ProcessPurchaseTransaction::dispatch($transaction->id, $this->transactionData['lines'])
                ->delay(now()->addSeconds(5));
        }

        return $transaction->id;
    }

    private function hasDamageProducts()
    {
        return isset($this->transactionData['damage_lines']) &&
            !empty($this->transactionData['damage_lines']);
    }

    private function getDamageProducts()
    {
        return $this->transactionData['damage_lines'] ?? [];
    }
}
