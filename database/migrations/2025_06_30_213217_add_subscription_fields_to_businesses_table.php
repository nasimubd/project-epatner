<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSubscriptionFieldsToBusinessesTable extends Migration
{
    public function up()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('subscription_active')->default(false);
            $table->datetime('subscription_expires_at')->nullable();
            $table->enum('subscription_status', ['active', 'expired', 'trial'])->default('trial');
        });
    }

    public function down()
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['subscription_active', 'subscription_expires_at', 'subscription_status']);
        });
    }
}
