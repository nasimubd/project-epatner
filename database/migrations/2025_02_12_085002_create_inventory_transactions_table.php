<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            // Reference the business that owns this transaction
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            // Entry types: purchase, sale, purchase_return, sales_return  
            // (Ensure the strings match your business logic)
            $table->enum('entry_type', ['purchase', 'sale', 'purchase_return', 'sales_return']);
            $table->dateTime('transaction_date');
            // This ledger_id can link to your accounting ledger (e.g. for cash/bank)
            $table->foreignId('ledger_id')->constrained()->onDelete('cascade');
            // Payment method: cash or credit
            $table->enum('payment_method', ['cash', 'credit']);
            $table->decimal('subtotal', 15, 2);
            $table->decimal('round_off', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->nullable()->default(0);
            $table->decimal('grand_total', 15, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_transactions');
    }
}
