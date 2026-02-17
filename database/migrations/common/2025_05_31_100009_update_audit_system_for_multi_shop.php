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
        Schema::connection('mysql_common')->table('tbl_customer_ledger_audit', function (Blueprint $table) {
            // Add shop context
            $table->unsignedBigInteger('shop_id')->nullable()->after('ledger_id');
            $table->uuid('global_customer_uuid')->nullable()->after('shop_id');

            // Enhanced action tracking
            $table->string('action_category', 50)->nullable()->after('action'); // 'customer', 'account', 'merge', 'system'
            $table->string('action_source', 50)->default('manual')->after('action_category'); // 'manual', 'api', 'system', 'import'

            // Cross-shop operation tracking
            $table->json('affected_shops')->nullable()->after('action_source'); // For cross-shop operations
            $table->uuid('related_customer_uuid')->nullable()->after('affected_shops'); // For merge operations

            // Enhanced metadata
            $table->string('user_role', 50)->nullable()->after('changed_by');
            $table->string('ip_address', 45)->nullable()->after('user_role');
            $table->string('user_agent', 500)->nullable()->after('ip_address');

            // Data quality tracking
            $table->decimal('data_quality_before', 5, 2)->nullable()->after('user_agent');
            $table->decimal('data_quality_after', 5, 2)->nullable()->after('data_quality_before');
        });

        // Add new indexes
        Schema::connection('mysql_common')->table('tbl_customer_ledger_audit', function (Blueprint $table) {
            $table->index(['shop_id'], 'idx_audit_shop_id');
            $table->index(['global_customer_uuid'], 'idx_audit_global_uuid');
            $table->index(['action_category'], 'idx_audit_action_category');
            $table->index(['action_source'], 'idx_audit_action_source');
            $table->index(['related_customer_uuid'], 'idx_audit_related_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_common')->table('tbl_customer_ledger_audit', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('idx_audit_shop_id');
            $table->dropIndex('idx_audit_global_uuid');
            $table->dropIndex('idx_audit_action_category');
            $table->dropIndex('idx_audit_action_source');
            $table->dropIndex('idx_audit_related_uuid');

            // Drop columns
            $table->dropColumn([
                'shop_id',
                'global_customer_uuid',
                'action_category',
                'action_source',
                'affected_shops',
                'related_customer_uuid',
                'user_role',
                'ip_address',
                'user_agent',
                'data_quality_before',
                'data_quality_after'
            ]);
        });
    }
};
