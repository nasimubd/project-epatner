<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('business_sub_districts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('business_id');
            $table->string('district');
            $table->string('sub_district');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->onDelete('cascade');
            $table->unique(['business_id', 'district', 'sub_district'], 'unique_business_sub_district');

            $table->index(['business_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('business_sub_districts');
    }
};
