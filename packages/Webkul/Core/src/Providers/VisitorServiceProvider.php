<?php

declare (strict_types=1);
namespace Webkul\Core\Providers;

use Illuminate\Http\Request;
use Shetabit\Visitor\Provider\Visitor_Service_Provider as BaseVisitorServiceProvider;
use Webkul\Core\Visitor;
/**
 * This is the overridden `VisitorServiceProvider` class from the `shetabit/visitor` package.
 */
class Visitor_Service_Provider extends Base_Visitor_Service_Provider
{
    /**
     * Register any package services.
     */
    public function register(): void
    {
        /**
         * Bind to service container.
         */
        $this->app->singleton('shetabit-visitor', function () {
            $request = app(Request::class);
            return new Visitor($request, config('visitor'));
        });
    }
    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        $this->register_macro_helpers();
    }
}