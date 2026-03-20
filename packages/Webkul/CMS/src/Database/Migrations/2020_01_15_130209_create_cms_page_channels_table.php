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
        Schema::create('cms_page_channels', function (Blueprint $table) {
            $table->integer('cms_page_id')->unsigned();
            $table->integer('channel_id')->unsigned();
            $table->unique(['cms_page_id', 'channel_id']);
            $table->foreign('cms_page_id')->references('id')->on('cms_pages')->on_delete('cascade');
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
        Schema::drop_if_exists('cms_page_channels');
    }
};