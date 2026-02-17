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
        // Add foreign key for customer ledgers -> shops
        Schema::connection('mysql_common')->table('tbl_customer_ledgers', function (Blueprint $table) {
            $table->foreign('shop_id', 'fk_customer_ledger_shop_id')
                ->references('shop_id')
                ->on('tbl_shops')
                ->onDelete('restrict') // Prevent deleting shop if customers exist
                ->onUpdate('cascade');
        });

        // Add foreign keys for customer shop accounts
        Schema::connection('mysql_common')->table('tbl_customer_shop_accounts', function (Blueprint $table) {
            $table->foreign('shop_id', 'fk_account_shop_id')
                ->references('shop_id')
                ->on('tbl_shops')
                ->onDelete('restrict')
                ->onUpdate('cascade');

            $table->foreign('ledger_id', 'fk_account_ledger_id')
                ->references('ledger_id')
                ->on('tbl_customer_ledgers')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });

        // Add foreign keys for audit table
        Schema::connection('mysql_common')->table('tbl_customer_ledger_audit', function (Blueprint $table) {
            $table->foreign('shop_id', 'fk_audit_shop_id')
                ->references('shop_id')
                ->on('tbl_shops')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_common')->table('tbl_customer_ledger_audit', function (Blueprint $table) {
            $table->dropForeign('fk_audit_shop_id');
        });

        Schema::connection('mysql_common')->table('tbl_customer_shop_accounts', function (Blueprint $table) {
            $table->dropForeign('fk_account_shop_id');
            $table->dropForeign('fk_account_ledger_id');
        });

        Schema::connection('mysql_common')->table('tbl_customer_ledgers', function (Blueprint $table) {
            $table->dropForeign('fk_customer_ledger_shop_id');
        });
    }
};
