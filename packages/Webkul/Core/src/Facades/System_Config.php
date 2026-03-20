<?php

declare (strict_types=1);
namespace Webkul\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Core\System_Config as BaseSystemConfig;
class System_Config extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function get_facade_accessor()
    {
        return Base_System_Config::class;
    }
}