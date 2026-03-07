<?php

declare(strict_types=1);

namespace Webkul\User\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\User\Bouncer as BaseBouncer;

class Bouncer extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BaseBouncer::class;
    }
}
