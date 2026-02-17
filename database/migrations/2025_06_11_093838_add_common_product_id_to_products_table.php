<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('common_product_id')->nullable()->after('business_id');
            $table->index('common_product_id');
            $table->unique(['business_id', 'common_product_id'], 'unique_business_common_product');
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['common_product_id']);
            $table->dropUnique('unique_business_common_product');
            $table->dropColumn('common_product_id');
        });
    }
};
