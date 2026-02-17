<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProductBatchesQuantityColumn extends Migration
{
    public function up()
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->decimal('remaining_quantity', 10, 3)->change(); // Allow 3 decimal places
        });
    }

    public function down()
    {
        Schema::table('product_batches', function (Blueprint $table) {
            $table->integer('remaining_quantity')->change();
        });
    }
}
