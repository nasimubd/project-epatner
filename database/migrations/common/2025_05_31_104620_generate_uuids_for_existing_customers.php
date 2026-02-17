<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Generate UUIDs for all existing customers
        $customers = DB::connection('mysql_common')
            ->table('tbl_customer_ledgers')
            ->whereNull('global_customer_uuid')
            ->select('ledger_id')
            ->get();

        $batchSize = 1000;
        $batches = $customers->chunk($batchSize);

        foreach ($batches as $batch) {
            $updates = [];

            foreach ($batch as $customer) {
                $updates[] = [
                    'ledger_id' => $customer->ledger_id,
                    'global_customer_uuid' => Str::uuid()->toString(),
                    'customer_source' => 'legacy_migration',
                    'last_activity_at' => now()
                ];
            }

            // Batch update for performance
            foreach ($updates as $update) {
                DB::connection('mysql_common')
                    ->table('tbl_customer_ledgers')
                    ->where('ledger_id', $update['ledger_id'])
                    ->update([
                        'global_customer_uuid' => $update['global_customer_uuid'],
                        'customer_source' => $update['customer_source'],
                        'last_activity_at' => $update['last_activity_at']
                    ]);
            }
        }

        // Log migration progress
        $totalCustomers = DB::connection('mysql_common')
            ->table('tbl_customer_ledgers')
            ->count();

        $migratedCustomers = DB::connection('mysql_common')
            ->table('tbl_customer_ledgers')
            ->whereNotNull('global_customer_uuid')
            ->count();

        echo "UUID Migration Complete: {$migratedCustomers}/{$totalCustomers} customers migrated\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove generated UUIDs (only for migration rollback)
        DB::connection('mysql_common')
            ->table('tbl_customer_ledgers')
            ->where('customer_source', 'legacy_migration')
            ->update([
                'global_customer_uuid' => null,
                'customer_source' => 'manual',
                'last_activity_at' => null
            ]);
    }
};
