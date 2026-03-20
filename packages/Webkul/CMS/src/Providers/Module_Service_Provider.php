<?php

declare (strict_types=1);
namespace Webkul\CMS\Providers;

use Webkul\Core\Providers\Core_Module_Service_Provider;
class Module_Service_Provider extends Core_Module_Service_Provider
{
    /**
     * Models.
     *
     * @var array
     */
    protected $models = [\Webkul\CMS\Models\Page::class, \Webkul\CMS\Models\Page_Translation::class];
}