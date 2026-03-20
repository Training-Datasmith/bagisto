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
        Schema::create('cart_rule_customers', function (Blueprint $table) {
            $table->increments('id');
            $table->big_integer('times_used')->unsigned()->default(0);
            $table->integer('customer_id')->unsigned();
            $table->integer('cart_rule_id')->unsigned();
            $table->foreign('cart_rule_id')->references('id')->on('cart_rules')->on_delete('cascade');
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
        Schema::drop_if_exists('cart_rule_customers');
    }
};