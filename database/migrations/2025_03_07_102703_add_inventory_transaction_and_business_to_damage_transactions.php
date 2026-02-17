<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('damage_transactions', function (Blueprint $table) {
            $table->foreignId('inventory_transaction_id')->nullable()
                ->constrained('inventory_transactions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('damage_transactions', function (Blueprint $table) {
            $table->dropForeign(['inventory_transaction_id']);

            $table->dropColumn(['inventory_transaction_id']);
        });
    }
};
