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
        Schema::connection('mysql_common')->create('tbl_customer_shop_accounts', function (Blueprint $table) {
            $table->id('account_id');

            // Foreign keys
            $table->uuid('global_customer_uuid');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('ledger_id'); // Reference to original customer ledger

            // Account specific details
            $table->string('local_customer_code', 50)->nullable(); // Shop-specific customer code
            $table->enum('account_status', ['active', 'inactive', 'suspended', 'closed'])->default('active');
            $table->enum('account_type', ['primary', 'secondary'])->default('primary'); // primary = main account, secondary = linked account

            // Relationship tracking
            $table->boolean('is_master_account')->default(false); // Is this the master account for this customer
            $table->timestamp('account_created_at')->useCurrent();
            $table->timestamp('last_transaction_at')->nullable();

            // Business metrics
            $table->decimal('total_transactions', 15, 2)->default(0.00);
            $table->integer('transaction_count')->default(0);
            $table->decimal('current_balance', 15, 2)->default(0.00);

            // Metadata
            $table->json('account_metadata')->nullable(); // Store shop-specific customer data
            $table->text('notes')->nullable();

            $table->timestamps();

            // Composite unique constraint - one account per customer per shop
            $table->unique(['global_customer_uuid', 'shop_id'], 'unique_customer_shop_account');

            // Indexes
            $table->index(['global_customer_uuid'], 'idx_account_customer_uuid');
            $table->index(['shop_id'], 'idx_account_shop_id');
            $table->index(['ledger_id'], 'idx_account_ledger_id');
            $table->index(['account_status'], 'idx_account_status');
            $table->index(['is_master_account'], 'idx_master_account');
            $table->index(['last_transaction_at'], 'idx_last_transaction');
            $table->index(['local_customer_code'], 'idx_local_customer_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_common')->dropIfExists('tbl_customer_shop_accounts');
    }
};
