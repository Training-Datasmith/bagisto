<?php

declare(strict_types=1);

namespace Webkul\SocialLogin\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [
        \Webkul\SocialLogin\Models\CustomerSocialAccount::class,
    ];
}
