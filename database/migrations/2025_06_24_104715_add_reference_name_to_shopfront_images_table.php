<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('shopfront_images', function (Blueprint $table) {
            $table->string('reference_name')->nullable()->after('reference_id');
        });
    }

    public function down()
    {
        Schema::table('shopfront_images', function (Blueprint $table) {
            $table->dropColumn('reference_name');
        });
    }
};
