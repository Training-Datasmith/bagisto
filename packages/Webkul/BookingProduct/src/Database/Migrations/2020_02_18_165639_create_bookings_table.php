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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsigned_integer('product_id')->nullable();
            $table->unsigned_integer('order_item_id')->nullable();
            $table->unsigned_integer('order_id')->nullable();
            $table->integer('qty')->default(0)->nullable();
            $table->integer('from')->nullable();
            $table->integer('to')->nullable();
            $table->foreign('order_item_id')->references('id')->on('order_items')->null_on_delete();
            $table->foreign_id('booking_product_event_ticket_id')->nullable()->constrained('booking_product_event_tickets')->null_on_delete();
            $table->foreign('order_id')->references('id')->on('orders')->null_on_delete();
            $table->foreign('product_id')->references('id')->on('products')->null_on_delete();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists('bookings');
    }
};