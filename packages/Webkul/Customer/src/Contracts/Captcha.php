<?php

declare(strict_types=1);

namespace Webkul\Customer\Contracts;

interface Captcha
{
    public const CLIENT_ENDPOINT = 'https://www.google.com/recaptcha/api.js';

    public const SITE_VERIFY_ENDPOINT = 'https://google.com/recaptcha/api/siteverify';
}
