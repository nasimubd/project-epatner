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
        Schema::connection('mysql_common')->create('tbl_shops', function (Blueprint $table) {
            $table->id('shop_id');
            $table->string('shop_code', 20)->unique(); // Unique identifier for shop
            $table->string('shop_name', 255);
            $table->string('shop_type', 50)->default('retail'); // retail, wholesale, etc.

            // Location details
            $table->string('district', 100)->nullable();
            $table->string('sub_district', 100)->nullable();
            $table->string('village', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('landmark', 255)->nullable();

            // Contact information
            $table->string('contact_number', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('manager_name', 255)->nullable();

            // Business details
            $table->boolean('is_active')->default(true);
            $table->date('opening_date')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes for better performance
            $table->index(['shop_code'], 'idx_shop_code');
            $table->index(['shop_name'], 'idx_shop_name');
            $table->index(['is_active'], 'idx_shop_active');
            $table->index(['district', 'sub_district', 'village'], 'idx_shop_location');
            $table->index(['created_at'], 'idx_shop_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_common')->dropIfExists('tbl_shops');
    }
};
