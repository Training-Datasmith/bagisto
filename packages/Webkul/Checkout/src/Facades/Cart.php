<?php

declare(strict_types=1);

namespace Webkul\Checkout\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Checkout\Cart as BaseCart;

class Cart extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BaseCart::class;
    }
}
