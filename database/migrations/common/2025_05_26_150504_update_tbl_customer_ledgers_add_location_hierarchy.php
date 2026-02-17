<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('mysql_common')->table('tbl_customer_ledgers', function (Blueprint $table) {
            // Add new location hierarchy columns
            $table->string('district', 100)->nullable()->after('type');
            $table->string('village', 100)->nullable()->after('sub_district'); // Changed from gram to village, removed upazila
            $table->string('landmark', 255)->nullable()->after('village');

            // Add QR code column
            $table->string('qr_code', 100)->unique()->nullable()->after('contact_number');

            // Add duplicate tracking columns
            $table->boolean('is_merged')->default(false)->after('landmark');
            $table->unsignedBigInteger('merged_into')->nullable()->after('is_merged');
            $table->json('duplicate_flags')->nullable()->after('merged_into');

            // Modify existing columns
            $table->string('sub_district', 100)->nullable()->change();
            $table->string('contact_number', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_common')->table('tbl_customer_ledgers', function (Blueprint $table) {
            // Drop new columns
            $table->dropColumn([
                'district',
                'village', // Changed from gram to village
                'landmark',
                'qr_code',
                'is_merged',
                'merged_into',
                'duplicate_flags'
            ]);
        });
    }
};
