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
        Schema::table('inventory_transaction_lines', function (Blueprint $table) {
            $table->decimal('dealer_price', 10, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('inventory_transaction_lines', function (Blueprint $table) {
            $table->dropColumn('dealer_price');
        });
    }
};
