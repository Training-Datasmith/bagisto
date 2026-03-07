<?php

declare(strict_types=1);

namespace Webkul\Customer\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Customer\Captcha as BaseCaptcha;

class Captcha extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return BaseCaptcha::class;
    }
}
