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
        // Create the default/main shop entry
        $defaultShopId = DB::connection('mysql_common')
            ->table('tbl_shops')
            ->insertGetId([
                'shop_code' => 'MAIN_001',
                'shop_name' => 'Main Shop',
                'shop_type' => 'retail',
                'district' => 'Default',
                'sub_district' => 'Default',
                'village' => 'Default',
                'address' => 'Legacy System - Main Location',
                'landmark' => 'Original System Location',
                'contact_number' => null,
                'email' => null,
                'manager_name' => 'System Administrator',
                'is_active' => true,
                'opening_date' => '2024-01-01', // Adjust based on your business start date
                'notes' => 'Default shop created during multi-shop system migration. All existing customers are initially assigned to this shop.',
                'created_at' => now(),
                'updated_at' => now()
            ]);

        // Update all existing customers to belong to the default shop
        DB::connection('mysql_common')
            ->table('tbl_customer_ledgers')
            ->whereNull('shop_id')
            ->update([
                'shop_id' => $defaultShopId,
                'updated_at' => now()
            ]);

        echo "Default shop created with ID: {$defaultShopId}\n";

        $assignedCustomers = DB::connection('mysql_common')
            ->table('tbl_customer_ledgers')
            ->where('shop_id', $defaultShopId)
            ->count();

        echo "Assigned {$assignedCustomers} existing customers to default shop\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the default shop ID
        $defaultShop = DB::connection('mysql_common')
            ->table('tbl_shops')
            ->where('shop_code', 'MAIN_001')
            ->first();

        if ($defaultShop) {
            // Remove shop assignment from customers
            DB::connection('mysql_common')
                ->table('tbl_customer_ledgers')
                ->where('shop_id', $defaultShop->shop_id)
                ->update(['shop_id' => null]);

            // Delete the default shop
            DB::connection('mysql_common')
                ->table('tbl_shops')
                ->where('shop_id', $defaultShop->shop_id)
                ->delete();
        }
    }
};
