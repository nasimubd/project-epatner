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
        if (!Schema::hasTable('ledgers')) {
            Schema::create('ledgers', function (Blueprint $table) {
                $table->id();                         // Primary key
                $table->unsignedBigInteger('business_id');
                $table->string('name');
                $table->string('contact')->nullable();
                $table->string('location')->nullable();
                $table->string('ledger_type');        // e.g., Bank, Cash-in-Hand, Expenses, etc.
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->enum('balance_type', ['Dr', 'Cr'])->default('Dr');  // Dr or Cr
                $table->decimal('current_balance', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('transaction_lines')) {
            Schema::create('transaction_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('transaction_id');
                $table->unsignedBigInteger('ledger_id');
                $table->decimal('debit_amount', 15, 2)->default(0);
                $table->decimal('credit_amount', 15, 2)->default(0);
                $table->string('narration')->nullable();
                $table->timestamps();

                $table->foreign('transaction_id')->references('id')->on('transactions')->onDelete('cascade');
                $table->foreign('ledger_id')->references('id')->on('ledgers')->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_lines');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('ledgers');
    }
};
