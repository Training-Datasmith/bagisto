<?php

declare (strict_types=1);
namespace Webkul\CMS\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Webkul\CMS\Contracts\Page_Translation as PageTranslationContract;
use Webkul\CMS\Database\Factories\Page_Translation_Factory;
class Page_Translation extends Model implements Page_Translation_Contract
{
    use Has_Factory;
    /**
     * Table associated with the model.
     *
     * @var string
     */
    protected $table = 'cms_page_translations';
    public $timestamps = false;
    protected $fillable = ['page_title', 'url_key', 'html_content', 'meta_title', 'meta_description', 'meta_keywords', 'locale', 'cms_page_id'];
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Page_Translation_Factory::new();
    }
}