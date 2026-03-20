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
        Schema::create('booking_product_event_ticket_translations', function (Blueprint $table) {
            $table->id();
            $table->unsigned_big_integer('booking_product_event_ticket_id');
            $table->unique(['booking_product_event_ticket_id', 'locale'], 'bpet_locale_unique');
            $table->string('locale');
            $table->text('name')->nullable();
            $table->text('description')->nullable();
            $table->foreign('booking_product_event_ticket_id', 'bpet_translations_fk')->references('id')->on('booking_product_event_tickets')->cascade_on_delete();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop_if_exists('booking_product_event_ticket_translations');
    }
};