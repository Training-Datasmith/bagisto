<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Contracts\Locale as LocaleContract;
use Webkul\Core\Database\Factories\Locale_Factory;
class Locale extends Model implements Locale_Contract
{
    use Has_Factory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['code', 'name', 'direction'];
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['logo_url'];
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Locale_Factory::new();
    }
    /**
     * Get the logo full path of the locale.
     *
     * @return string|null
     */
    public function get_logo_url_attribute()
    {
        return $this->logo_url();
    }
    /**
     * Get the logo full path of the locale.
     *
     * @return string|void
     */
    public function logo_url()
    {
        if (empty($this->logo_path)) {
            return;
        }
        return Storage::url($this->logo_path);
    }
}