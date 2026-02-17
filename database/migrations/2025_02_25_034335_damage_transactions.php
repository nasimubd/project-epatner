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
        Schema::create('damage_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->unsignedBigInteger('supplier_ledger_id');
            $table->unsignedBigInteger('customer_ledger_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->date('transaction_date');
            $table->enum('status', ['pending', 'processed', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses');
            $table->foreign('supplier_ledger_id')->references('id')->on('ledgers');
            $table->foreign('customer_ledger_id')->references('id')->on('ledgers');
        });

        Schema::create('damage_transaction_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('damage_transaction_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_value', 10, 2);
            $table->text('damage_reason')->nullable();
            $table->timestamps();

            $table->foreign('damage_transaction_id')->references('id')->on('damage_transactions')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('damage_transaction_lines');
        Schema::dropIfExists('damage_transactions');
    }
};
