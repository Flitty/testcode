<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateSubscriptionActivitiesTable
 */
class CreateSubscriptionActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_activities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type');
            $table->unsignedInteger('subscription_id');
            $table->text('message');
            $table->json('body')->nullable();
            $table->timestamps();

            $table->foreign('subscription_id')->on('subscriptions')->references('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_activities');
    }
}
