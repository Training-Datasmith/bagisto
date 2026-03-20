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
        Schema::create('attribute_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('attribute_family_id')->unsigned();
            $table->string('name');
            $table->integer('position');
            $table->boolean('is_user_defined')->default(1);
            $table->unique(['attribute_family_id', 'name']);
            $table->foreign('attribute_family_id')->references('id')->on('attribute_families')->on_delete('cascade');
        });
        Schema::create('attribute_group_mappings', function (Blueprint $table) {
            $table->integer('attribute_id')->unsigned();
            $table->integer('attribute_group_id')->unsigned();
            $table->integer('position')->nullable();
            $table->primary(['attribute_id', 'attribute_group_id']);
            $table->foreign('attribute_id')->references('id')->on('attributes')->on_delete('cascade');
            $table->foreign('attribute_group_id')->references('id')->on('attribute_groups')->on_delete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists('attribute_group_mappings');
        Schema::drop_if_exists('attribute_groups');
    }
};