<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mysql_common')->table('tbl_customer_ledgers', function (Blueprint $table) {
            // Add UUID for global customer identification
            $table->uuid('global_customer_uuid')->nullable()->after('ledger_id');

            // Add shop reference
            $table->unsignedBigInteger('shop_id')->nullable()->after('global_customer_uuid');

            // Add customer status
            $table->enum('customer_status', ['active', 'inactive', 'suspended'])->default('active')->after('shop_id');

            // Add customer source tracking
            $table->string('customer_source', 50)->default('manual')->after('customer_status'); // manual, import, api, etc.

            // Add data quality score
            $table->decimal('data_quality_score', 5, 2)->default(0.00)->after('customer_source');

            // Add last activity tracking
            $table->timestamp('last_activity_at')->nullable()->after('data_quality_score');
        });

        // Add indexes for the new columns
        Schema::connection('mysql_common')->table('tbl_customer_ledgers', function (Blueprint $table) {
            $table->index(['global_customer_uuid'], 'idx_global_customer_uuid');
            $table->index(['shop_id'], 'idx_customer_shop_id');
            $table->index(['customer_status'], 'idx_customer_status');
            $table->index(['customer_source'], 'idx_customer_source');
            $table->index(['last_activity_at'], 'idx_last_activity');
            $table->index(['global_customer_uuid', 'shop_id'], 'idx_customer_shop_composite');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_common')->table('tbl_customer_ledgers', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_global_customer_uuid');
            $table->dropIndex('idx_customer_shop_id');
            $table->dropIndex('idx_customer_status');
            $table->dropIndex('idx_customer_source');
            $table->dropIndex('idx_last_activity');
            $table->dropIndex('idx_customer_shop_composite');

            // Drop columns
            $table->dropColumn([
                'global_customer_uuid',
                'shop_id',
                'customer_status',
                'customer_source',
                'data_quality_score',
                'last_activity_at'
            ]);
        });
    }
};
