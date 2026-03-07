<?php

declare(strict_types=1);

use Webkul\User\Facades\Bouncer as BouncerFacade;

if (! function_exists('bouncer')) {
    /**
     * Bouncer helper.
     *
     * @return \Webkul\User\Bouncer
     */
    function bouncer()
    {
        return BouncerFacade::getFacadeRoot();
    }
}
