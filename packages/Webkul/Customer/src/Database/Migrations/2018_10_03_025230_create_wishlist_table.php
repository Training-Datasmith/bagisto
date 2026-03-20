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
        Schema::create('wishlist', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('channel_id')->unsigned();
            $table->integer('product_id')->unsigned();
            $table->integer('customer_id')->unsigned();
            $table->json('item_options')->nullable();
            $table->date('moved_to_cart')->nullable();
            $table->boolean('shared')->nullable();
            $table->date('time_of_moving')->nullable();
            $table->json('additional')->nullable();
            $table->timestamps();
            $table->foreign('channel_id')->references('id')->on('channels')->on_delete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->on_delete('cascade');
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
        Schema::drop_if_exists('wishlist');
    }
};