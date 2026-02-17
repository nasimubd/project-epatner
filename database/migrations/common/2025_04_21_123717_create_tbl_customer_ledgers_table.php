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
        Schema::connection('mysql_common')->create('tbl_customer_ledgers', function (Blueprint $table) {
            $table->id('ledger_id');
            $table->string('ledger_name');
            $table->string('location')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('type')->nullable();
            $table->string('sub_district')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mysql_common')->dropIfExists('tbl_customer_ledgers');
    }
};
