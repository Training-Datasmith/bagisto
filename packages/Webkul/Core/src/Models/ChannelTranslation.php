<?php

declare (strict_types=1);
namespace Webkul\Core\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\Has_Factory;
use Illuminate\Database\Eloquent\Model;
use Webkul\Core\Contracts\Channel_Translation as ChannelTranslationContract;
use Webkul\Core\Database\Factories\Channel_Translation_Factory;
class Channel_Translation extends Model implements Channel_Translation_Contract
{
    use Has_Factory;
    /**
     * Guarded.
     *
     * @var array
     */
    protected $guarded = [];
    /**
     * Castable.
     *
     * @var array
     */
    protected $casts = ['home_seo' => 'array'];
    /**
     * Create a new factory instance for the model.
     */
    protected static function new_factory(): Factory
    {
        return Channel_Translation_Factory::new();
    }
}