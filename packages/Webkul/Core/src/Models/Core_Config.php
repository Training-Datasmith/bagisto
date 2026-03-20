<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\Core_Config as CoreConfigContract;
use Webkul\Core\Database\Factories\Core_Config_Factory;
class Core_Config extends Model implements Core_Config_Contract
{
    use Has_Factory;
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = 'core_config';
    /**
     * Fillable for mass assignment
     *
     * @var array
     */
    protected $fillable = ['code', 'value', 'channel_code', 'locale_code'];
    /**
     * Hidden properties
     *
     * @var array
     */
    protected $hidden = ['token'];
    /**
     * Create a new factory instance for the model
     */
    protected static function new_factory(): Factory
    {
        return Core_Config_Factory::new();
    }
}