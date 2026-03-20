<?php

declare (strict_types=1);
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attribute_groups', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
        });
        $attribute_groups = DB::table('attribute_groups')->get();
        foreach ($attribute_groups as $attribute_group) {
            DB::table('attribute_groups')->where('id', $attribute_group->id)->update(['code' => Str::of($attribute_group->name)->snake()]);
        }
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attribute_groups', function (Blueprint $table) {
            $table->drop_column('code');
        });
    }
};