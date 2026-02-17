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
        Schema::connection('mysql_common')->table('tbl_customer_ledgers', function (Blueprint $table) {
            // Add indexes for better performance
            $table->index(['ledger_name'], 'idx_ledger_name');
            $table->index(['contact_number'], 'idx_contact_number');
            $table->index(['qr_code'], 'idx_qr_code');
            $table->index(['type'], 'idx_type');
            $table->index(['district', 'sub_district', 'village'], 'idx_location_hierarchy'); // Updated without upazila, gram->village
            $table->index(['is_merged'], 'idx_is_merged');
            $table->index(['created_at'], 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_common')->table('tbl_customer_ledgers', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex('idx_ledger_name');
            $table->dropIndex('idx_contact_number');
            $table->dropIndex('idx_qr_code');
            $table->dropIndex('idx_type');
            $table->dropIndex('idx_location_hierarchy');
            $table->dropIndex('idx_is_merged');
            $table->dropIndex('idx_created_at');
        });
    }
};
