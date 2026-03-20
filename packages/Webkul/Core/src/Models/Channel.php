<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Relations\Belongs_To;
use Illuminate\Database\Eloquent\Relations\Belongs_To_Many;
use Illuminate\Support\Facades\Storage;
use Webkul\Category\Models\Category_Proxy;
use Webkul\Core\Contracts\Channel as ChannelContract;
use Webkul\Core\Database\Factories\Channel_Factory;
use Webkul\Core\Eloquent\Translatable_Model;
use Webkul\Inventory\Models\Inventory_Source_Proxy;
class Channel extends Translatable_Model implements Channel_Contract
{
    use Has_Factory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['code', 'name', 'description', 'theme', 'hostname', 'default_locale_id', 'base_currency_id', 'root_category_id', 'home_seo', 'is_maintenance_on', 'maintenance_mode_text', 'allowed_ips'];
    /**
     * Castable.
     *
     * @var array
     */
    protected $casts = ['home_seo' => 'array'];
    /**
     * Translated attributes.
     *
     * @var array
     */
    public $translated_attributes = ['name', 'description', 'maintenance_mode_text', 'home_seo'];
    /**
     * Get the channel locales.
     */
    public function locales(): Belongs_To_Many
    {
        return $this->belongs_to_many(Locale_Proxy::model_class(), 'channel_locales');
    }
    /**
     * Get the default locale
     */
    public function default_locale(): Belongs_To
    {
        return $this->belongs_to(Locale_Proxy::model_class());
    }
    /**
     * Get the channel locales.
     */
    public function currencies(): Belongs_To_Many
    {
        return $this->belongs_to_many(Currency_Proxy::model_class(), 'channel_currencies');
    }
    /**
     * Get the channel inventory sources.
     */
    public function inventory_sources(): Belongs_To_Many
    {
        return $this->belongs_to_many(Inventory_Source_Proxy::model_class(), 'channel_inventory_sources');
    }
    /**
     * Get the base currency.
     */
    public function base_currency(): Belongs_To
    {
        return $this->belongs_to(Currency_Proxy::model_class());
    }
    /**
     * Get the root category.
     */
    public function root_category(): Belongs_To
    {
        return $this->belongs_to(Category_Proxy::model_class(), 'root_category_id');
    }
    /**
     * Get logo image url.
     */
    public function logo_url()
    {
        if (!$this->logo) {
            return;
        }
        return Storage::url($this->logo);
    }
    /**
     * Get logo image url.
     */
    public function get_logo_url_attribute()
    {
        return $this->logo_url();
    }
    /**
     * Get favicon image url.
     */
    public function favicon_url()
    {
        if (!$this->favicon) {
            return;
        }
        return Storage::url($this->favicon);
    }
    /**
     * Get favicon image url.
     */
    public function get_favicon_url_attribute()
    {
        return $this->favicon_url();
    }
    /**
     * Create a new factory instance for the model
     */
    protected static function new_factory(): Factory
    {
        return Channel_Factory::new();
    }
}