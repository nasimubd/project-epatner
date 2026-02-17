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
            // Add foreign key constraint for merged_into (self-referencing)
            $table->foreign('merged_into', 'fk_customer_ledger_merged_into')
                ->references('ledger_id')
                ->on('tbl_customer_ledgers')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_common')->table('tbl_customer_ledgers', function (Blueprint $table) {
            $table->dropForeign('fk_customer_ledger_merged_into');
        });
    }
};
