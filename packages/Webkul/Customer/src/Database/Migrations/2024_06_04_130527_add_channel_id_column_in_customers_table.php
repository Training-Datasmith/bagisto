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
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('channel_id')->unsigned()->nullable()->after('customer_group_id');
            $table->foreign('channel_id')->references('id')->on('channels')->on_delete('SET NULL');
        });
        $first_channel_id = DB::table('channels')->value('id');
        if (!$first_channel_id) {
            return;
        }
        DB::table('customers')->update(['channel_id' => $first_channel_id]);
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->drop_foreign(['channel_id']);
            $table->drop_column('channel_id');
        });
    }
};