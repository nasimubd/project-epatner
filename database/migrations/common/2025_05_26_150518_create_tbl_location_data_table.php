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
        Schema::connection('mysql_common')->create('tbl_location_data', function (Blueprint $table) {
            $table->id();
            $table->string('district', 100);
            $table->string('sub_district', 100);
            $table->string('village', 100); // Changed from gram to village, removed upazila
            $table->timestamps();

            // Indexes for better performance
            $table->index(['district'], 'idx_district');
            $table->index(['district', 'sub_district'], 'idx_district_sub');
            $table->index(['district', 'sub_district', 'village'], 'idx_full_location'); // Updated without upazila, gram->village

            // Unique constraint to prevent duplicate location entries
            $table->unique(['district', 'sub_district', 'village'], 'unique_location_combination'); // Updated without upazila, gram->village
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_common')->dropIfExists('tbl_location_data');
    }
};
