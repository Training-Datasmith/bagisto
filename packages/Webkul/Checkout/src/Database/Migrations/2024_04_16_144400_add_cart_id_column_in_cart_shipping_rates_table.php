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
        Schema::table('cart_shipping_rates', function (Blueprint $table) {
            $table->integer('cart_id')->nullable()->unsigned();
            $table->foreign('cart_id')->references('id')->on('cart')->on_delete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_shipping_rates', function (Blueprint $table) {
            $table->drop_foreign(['cart_id']);
            $table->drop_column('cart_id');
        });
    }
};