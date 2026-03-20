<?php

declare (strict_types=1);
namespace Webkul\Core\Providers;

use Konekt\Concord\Base_Module_Service_Provider;
/**
 * This is the overridden `CoreModuleServiceProvider` class from the `konekt/concord` package.
 */
class Core_Module_Service_Provider extends Base_Module_Service_Provider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if ($this->are_migrations_enabled()) {
            $this->register_migrations();
        }
        if ($this->are_models_enabled()) {
            $this->register_models();
            $this->register_enums();
            $this->register_request_types();
        }
        if ($this->are_views_enabled()) {
            $this->register_views();
        }
        if ($routes = $this->config('routes', true)) {
            $this->register_routes($routes);
        }
    }
}