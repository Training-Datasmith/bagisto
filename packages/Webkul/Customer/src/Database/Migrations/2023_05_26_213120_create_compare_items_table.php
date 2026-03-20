<?php

declare (strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('compare_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsigned_integer('product_id');
            $table->unsigned_integer('customer_id');
            $table->timestamps();
            $table->foreign('product_id')->references('id')->on('products')->on_update('cascade')->on_delete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->on_update('cascade')->on_delete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop_if_exists('compare_items');
    }
};