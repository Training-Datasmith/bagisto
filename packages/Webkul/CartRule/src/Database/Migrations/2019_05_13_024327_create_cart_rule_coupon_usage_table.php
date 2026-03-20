<?php

declare (strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cart_rule_coupon_usage', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('times_used')->default(0);
            $table->integer('cart_rule_coupon_id')->unsigned();
            $table->integer('customer_id')->unsigned();
            $table->foreign('cart_rule_coupon_id')->references('id')->on('cart_rule_coupons')->on_delete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->on_delete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists('cart_rule_coupon_usage');
    }
};