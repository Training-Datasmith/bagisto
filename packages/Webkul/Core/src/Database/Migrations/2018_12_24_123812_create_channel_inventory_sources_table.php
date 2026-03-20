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
        Schema::create('channel_inventory_sources', function (Blueprint $table) {
            $table->integer('channel_id')->unsigned();
            $table->integer('inventory_source_id')->unsigned();
            $table->unique(['channel_id', 'inventory_source_id'], 'channel_inventory_source_unique');
            $table->foreign('channel_id')->references('id')->on('channels')->on_delete('cascade');
            $table->foreign('inventory_source_id')->references('id')->on('inventory_sources')->on_delete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists('channel_inventory_sources');
    }
};