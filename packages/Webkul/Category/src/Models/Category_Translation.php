<?php

declare (strict_types=1);
namespace Webkul\Category\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Category\Contracts\Category_Translation as CategoryTranslationContract;
use Webkul\Category\Database\Factories\Category_Translation_Factory;
class Category_Translation extends Model implements Category_Translation_Contract
{
    use Has_Factory;
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'description', 'slug', 'meta_title', 'meta_description', 'meta_keywords', 'locale_id'];
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Category_Translation_Factory::new();
    }
}