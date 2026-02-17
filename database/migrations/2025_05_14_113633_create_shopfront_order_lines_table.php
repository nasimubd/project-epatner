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
        Schema::create('shopfront_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('shopfront_orders')->onDelete('cascade');
            $table->unsignedBigInteger('product_id');
            $table->boolean('is_common_product')->default(false);
            $table->string('product_name');
            $table->decimal('quantity', 10, 3);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopfront_order_lines');
    }
};
