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
        Schema::create('catalog_rule_channels', function (Blueprint $table) {
            $table->integer('catalog_rule_id')->unsigned();
            $table->integer('channel_id')->unsigned();
            $table->primary(['catalog_rule_id', 'channel_id']);
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
        Schema::drop_if_exists('catalog_rule_channels');
    }
};