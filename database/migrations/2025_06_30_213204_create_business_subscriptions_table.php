<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBusinessSubscriptionsTable extends Migration
{
    public function up()
    {
        Schema::create('business_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->datetime('starts_at');
            $table->datetime('expires_at');
            $table->enum('status', ['active', 'expired', 'pending_payment'])->default('pending_payment');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('business_subscriptions');
    }
}
