<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shopfront_orders', function (Blueprint $table) {
            // Modify the status enum to include 'due'
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled', 'due'])->change();
        });
    }

    public function down()
    {
        Schema::table('shopfront_orders', function (Blueprint $table) {
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->change();
        });
    }
};
