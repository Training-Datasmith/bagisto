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
        Schema::create('booking_products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsigned_integer('product_id');
            $table->string('type');
            $table->integer('qty')->default(0)->nullable();
            $table->string('location')->nullable();
            $table->boolean('show_location')->default(false);
            $table->boolean('available_every_week')->nullable();
            $table->date_time('available_from')->nullable();
            $table->date_time('available_to')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->cascade_on_delete();
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
        Schema::drop_if_exists('booking_products');
    }
};