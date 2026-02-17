<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all customers with UUIDs and shop assignments
        $customers = DB::connection('mysql_common')
            ->table('tbl_customer_ledgers')
            ->whereNotNull('global_customer_uuid')
            ->whereNotNull('shop_id')
            ->select([
                'ledger_id',
                'global_customer_uuid',
                'shop_id',
                'ledger_name',
                'created_at',
                'updated_at'
            ])
            ->get();

        $batchSize = 500;
        $batches = $customers->chunk($batchSize);
        $totalInserted = 0;

        foreach ($batches as $batch) {
            $accountData = [];

            foreach ($batch as $customer) {
                // Generate local customer code (shop-specific)
                $localCode = 'CUST_' . str_pad($customer->ledger_id, 6, '0', STR_PAD_LEFT);

                $accountData[] = [
                    'global_customer_uuid' => $customer->global_customer_uuid,
                    'shop_id' => $customer->shop_id,
                    'ledger_id' => $customer->ledger_id,
                    'local_customer_code' => $localCode,
                    'account_status' => 'active',
                    'account_type' => 'primary',
                    'is_master_account' => true, // All legacy customers are master accounts
                    'account_created_at' => $customer->created_at ?? now(),
                    'last_transaction_at' => $customer->updated_at,
                    'total_transactions' => 0.00,
                    'transaction_count' => 0,
                    'current_balance' => 0.00,
                    'account_metadata' => json_encode([
                        'migration_source' => 'legacy_system',
                        'original_ledger_id' => $customer->ledger_id,
                        'customer_name' => $customer->ledger_name,
                        'migration_date' => now()->toDateString()
                    ]),
                    'notes' => 'Account created during multi-shop system migration',
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }

            // Batch insert for performance
            DB::connection('mysql_common')
                ->table('tbl_customer_shop_accounts')
                ->insert($accountData);

            $totalInserted += count($accountData);
        }

        echo "Bridge table populated: {$totalInserted} customer-shop accounts created\n";

        // Verify data integrity
        $customerCount = DB::connection('mysql_common')
            ->table('tbl_customer_ledgers')
            ->whereNotNull('global_customer_uuid')
            ->count();

        $accountCount = DB::connection('mysql_common')
            ->table('tbl_customer_shop_accounts')
            ->count();

        if ($customerCount === $accountCount) {
            echo "✅ Data integrity verified: {$customerCount} customers = {$accountCount} accounts\n";
        } else {
            echo "⚠️ Data integrity warning: {$customerCount} customers ≠ {$accountCount} accounts\n";
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Clear the bridge table
        DB::connection('mysql_common')
            ->table('tbl_customer_shop_accounts')
            ->truncate();

        echo "Bridge table cleared\n";
    }
};
