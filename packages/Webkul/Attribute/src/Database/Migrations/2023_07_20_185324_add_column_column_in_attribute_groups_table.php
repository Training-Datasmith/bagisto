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
        Schema::table('attribute_groups', function (Blueprint $table) {
            $table->integer('column')->default(1)->after('name');
        });
        $families = DB::table('attribute_families')->get();
        foreach ($families as $family) {
            DB::table('attribute_groups')->insert(['name' => 'Settings', 'column' => 2, 'is_user_defined' => 0, 'position' => 3, 'attribute_family_id' => $family->id]);
            $general_group = DB::table('attribute_groups')->where('name', 'General')->where('attribute_family_id', $family->id)->first();
            $setting_group = DB::table('attribute_groups')->where('name', 'Settings')->where('attribute_family_id', $family->id)->first();
            DB::table('attribute_group_mappings')->where('attribute_group_id', $general_group->id)->where_in('attribute_id', [5, 6, 7, 8, 26])->update(['attribute_group_id' => $setting_group->id]);
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attribute_groups', function (Blueprint $table) {
            $table->drop_column('column');
        });
    }
};