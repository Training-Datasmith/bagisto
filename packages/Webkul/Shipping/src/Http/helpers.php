<?php

declare(strict_types=1);

use Webkul\Shipping\Facades\Shipping;

if (! function_exists('shipping')) {
    /**
     * Shipping helper.
     *
     * @return \Webkul\Shipping\Shipping
     */
    function shipping()
    {
        return Shipping::getFacadeRoot();
    }
}
