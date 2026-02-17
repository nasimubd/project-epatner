<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_of_measurements', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['unit', 'fraction']);
            $table->string('name');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        // Add default units
        DB::table('unit_of_measurements')->insert([
            ['type' => 'unit', 'name' => 'Unit', 'status' => true],
            ['type' => 'fraction', 'name' => 'Fraction', 'status' => true]
        ]);

        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('category_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('product_category_id')->constrained('product_categories');
            $table->foreignId('business_id')->constrained();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained();
            $table->string('name');
            $table->string('barcode')->nullable();
            $table->foreignId('category_id')->constrained('product_categories');
            $table->foreignId('unit_id')->constrained('unit_of_measurements');
            $table->integer('quantity_alert')->nullable();
            $table->decimal('opening_stock', 10, 2)->default(0);
            $table->date('opening_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->decimal('dealer_price', 10, 2)->comment('DP');
            $table->decimal('profit_margin', 8, 2)->default(0);
            $table->decimal('trade_price', 10, 2)->comment('TP');
            $table->string('tax')->default('NA');
            $table->binary('image')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });



        Schema::create('product_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->string('batch_number');
            $table->decimal('dealer_price', 10, 2);
            $table->decimal('trade_price', 10, 2);
            $table->decimal('remaining_quantity', 10, 2);
            $table->date('batch_date');
            $table->date('expiry_date')->nullable();
            $table->boolean('is_opening_batch')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_batches');
        Schema::dropIfExists('category_staff');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
        Schema::dropIfExists('unit_of_measurements');
    }
};
