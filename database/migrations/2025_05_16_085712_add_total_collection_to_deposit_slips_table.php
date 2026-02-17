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
        Schema::table('deposit_slips', function (Blueprint $table) {
            $table->decimal('total_collection', 15, 2)->nullable()->after('total_amount');
            $table->unsignedBigInteger('ledger_id')->nullable()->after('business_id');
            $table->foreign('ledger_id')->references('id')->on('ledgers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposit_slips', function (Blueprint $table) {
            $table->dropForeign(['ledger_id']);
            $table->dropColumn(['total_collection', 'ledger_id']);
        });
    }
};
