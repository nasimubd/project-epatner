<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('shopfront_order_id')->nullable();
            $table->foreign('shopfront_order_id')->references('id')->on('shopfront_orders')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['shopfront_order_id']);
            $table->dropColumn('shopfront_order_id');
        });
    }
};
