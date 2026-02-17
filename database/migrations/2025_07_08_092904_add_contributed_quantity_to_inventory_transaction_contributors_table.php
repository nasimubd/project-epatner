<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_transaction_contributors', function (Blueprint $table) {
            $table->decimal('contributed_quantity', 10, 2)->after('product_id');
            $table->decimal('contributed_amount', 15, 2)->after('contributed_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_transaction_contributors', function (Blueprint $table) {
            $table->dropColumn(['contributed_quantity', 'contributed_amount']);
        });
    }
};
