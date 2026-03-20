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
        Schema::create('catalog_rule_product_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('price', 12, 4)->default(0);
            $table->date('rule_date');
            $table->datetime('starts_from')->nullable();
            $table->datetime('ends_till')->nullable();
            $table->integer('product_id')->unsigned();
            $table->integer('customer_group_id')->unsigned();
            $table->integer('catalog_rule_id')->unsigned();
            $table->integer('channel_id')->unsigned();
            $table->foreign('product_id')->references('id')->on('products')->on_delete('cascade');
            $table->foreign('customer_group_id')->references('id')->on('customer_groups')->on_delete('cascade');
            $table->foreign('catalog_rule_id')->references('id')->on('catalog_rules')->on_delete('cascade');
            $table->foreign('channel_id')->references('id')->on('channels')->on_delete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists('catalog_rule_product_prices');
    }
};