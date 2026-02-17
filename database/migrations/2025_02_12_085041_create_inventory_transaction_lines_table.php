<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventoryTransactionLinesTable extends Migration
{
    public function up()
    {
        Schema::create('inventory_transaction_lines', function (Blueprint $table) {
            $table->id();
            // Link to the header record
            $table->foreignId('inventory_transaction_id')->constrained('inventory_transactions')->onDelete('cascade');
            // The product involved in this line
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            // (Optional) If you are managing batches, you can reference the product batch.
            // For sales, you might deduct from a specific batch (or use FIFO logic later)
            $table->foreignId('batch_id')->nullable()->constrained('product_batches')->onDelete('set null');
            // Quantity sold/purchased; note that quantity is a decimal so it works for fractions
            $table->decimal('quantity', 10, 2);
            // The price per unit at which the product is transacted
            $table->decimal('unit_price', 10, 2);
            // (Optional) discount applied on this line
            $table->decimal('line_discount', 10, 2)->nullable()->default(0);
            // The total amount for the line (calculated as quantity * unit_price - line_discount)
            $table->decimal('line_total', 15, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('inventory_transaction_lines');
    }
}
