<?php

declare (strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->decimal('price_incl_tax', 12, 4)->default(0)->after('base_discount_amount');
            $table->decimal('base_price_incl_tax', 12, 4)->default(0)->after('price_incl_tax');
            $table->decimal('total_incl_tax', 12, 4)->default(0)->after('base_price_incl_tax');
            $table->decimal('base_total_incl_tax', 12, 4)->default(0)->after('total_incl_tax');
            $table->string('applied_tax_rate')->nullable()->after('base_total_incl_tax');
        });
        DB::table('cart_items')->update(['price_incl_tax' => DB::raw('price'), 'base_price_incl_tax' => DB::raw('base_price'), 'total_incl_tax' => DB::raw('total'), 'base_total_incl_tax' => DB::raw('base_total')]);
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->drop_column('applied_tax_rate');
            $table->drop_column('base_total_incl_tax');
            $table->drop_column('total_incl_tax');
            $table->drop_column('base_price_incl_tax');
            $table->drop_column('price_incl_tax');
        });
    }
};