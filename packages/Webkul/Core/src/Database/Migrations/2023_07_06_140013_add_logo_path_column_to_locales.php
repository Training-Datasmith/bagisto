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
        if (Schema::has_column('locales', 'locale_image')) {
            Schema::drop_columns('locales', 'locale_image');
        }
        Schema::table('locales', function (Blueprint $table) {
            $table->string('logo_path')->after('direction')->nullable();
        });
        DB::table('locales')->where_null('logo_path')->update(['logo_path' => DB::raw('CONCAT("locales/", code, ".png")')]);
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::has_column('locales', 'locale_image')) {
            Schema::drop_columns('locales', 'locale_image');
        }
        if (Schema::has_column('locales', 'logo_path')) {
            Schema::drop_columns('locales', 'logo_path');
        }
    }
};