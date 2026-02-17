<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->unsignedBigInteger('common_customer_id')->nullable()->after('business_id');
            $table->index(['business_id', 'common_customer_id']);
        });
    }

    public function down()
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'common_customer_id']);
            $table->dropColumn('common_customer_id');
        });
    }
};
