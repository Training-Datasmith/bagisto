<?php

declare (strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kalnoy\Nestedset\Nested_Set;
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('position')->default(0);
            $table->string('image')->nullable();
            $table->string('category_banner')->nullable();
            $table->boolean('status')->default(0);
            $table->string('display_mode')->default('products_and_description')->nullable();
            Nested_Set::columns($table);
            $table->json('additional')->nullable();
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
        Schema::drop_if_exists('categories');
    }
};