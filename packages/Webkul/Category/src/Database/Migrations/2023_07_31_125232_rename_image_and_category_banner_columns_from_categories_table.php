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
        if (Schema::has_columns('categories', ['image', 'category_banner'])) {
            Schema::drop_columns('categories', ['image', 'category_banner']);
        }
        Schema::table('categories', function (Blueprint $table) {
            $table->text('logo_path')->nullable()->after('position');
            $table->text('banner_path')->nullable()->after('additional');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::has_columns('categories', ['logo_path', 'banner_path'])) {
            Schema::drop_columns('categories', ['logo_path', 'banner_path']);
        }
        Schema::table('categories', function (Blueprint $table) {
            $table->text('image')->nullable()->after('position');
            $table->text('category_banner')->nullable()->after('additional');
        });
    }
};