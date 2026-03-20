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
        Schema::create('attribute_option_translations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('attribute_option_id')->unsigned();
            $table->string('locale');
            $table->text('label')->nullable();
            $table->unique(['attribute_option_id', 'locale'], 'attribute_option_locale_unique');
            $table->foreign('attribute_option_id')->references('id')->on('attribute_options')->on_delete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists('attribute_option_translations');
    }
};