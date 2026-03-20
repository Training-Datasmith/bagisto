<?php

declare (strict_types=1);
namespace Webkul\CMS\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Webkul\CMS\Contracts\Page as PageContract;
use Webkul\CMS\Database\Factories\Page_Factory;
use Webkul\Core\Eloquent\Translatable_Model;
use Webkul\Core\Models\Channel_Proxy;
class Page extends Translatable_Model implements Page_Contract
{
    use Has_Factory;
    /**
     * Table associated with the model.
     *
     * @var string
     */
    protected $table = 'cms_pages';
    /**
     * Translation model foreign key column
     *
     * @var string
     */
    protected $translation_foreign_key = 'cms_page_id';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['layout'];
    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translated_attributes = ['content', 'meta_description', 'meta_title', 'page_title', 'meta_keywords', 'html_content', 'url_key'];
    /**
     * With the translations given attributes
     *
     * @var array
     */
    protected $with = ['translations'];
    /**
     * Get the channels.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany;
     */
    public function channels()
    {
        return $this->belongs_to_many(Channel_Proxy::model_class(), 'cms_page_channels', 'cms_page_id');
    }
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Page_Factory::new();
    }
}