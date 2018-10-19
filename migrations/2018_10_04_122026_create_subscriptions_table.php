<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateSubscriptionsTable
 */
class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('subscription_coupon_id')->nullable();
            $table->unsignedInteger('subscription_type_id');
            $table->timestamp('expire_at')->nullable();
            $table->unsignedInteger('user_id');
            $table->timestamp('suspended_at')->nullable();
            $table->string('driver');
            $table->string('status');
            $table->string('recurring_payment_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->unique(['user_id', 'subscription_type_id']);
            $table->foreign('subscription_coupon_id')->on('subscription_coupons')->references('id');
            $table->foreign('subscription_type_id')->on('subscription_types')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscriptions');
    }
}
