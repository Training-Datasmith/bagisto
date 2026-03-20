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
        Schema::create('customer_notes', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id')->unsigned()->nullable();
            $table->text('note');
            $table->boolean('customer_notified')->default(0);
            $table->timestamps();
            $table->foreign('customer_id')->references('id')->on('customers')->on_delete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop_if_exists('customer_notes');
    }
};