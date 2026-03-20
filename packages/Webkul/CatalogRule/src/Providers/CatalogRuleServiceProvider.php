<?php

declare (strict_types=1);
namespace Webkul\Catalog_Rule\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Service_Provider;
use Webkul\Catalog_Rule\Console\Commands\Price_Rule_Index;
class Catalog_Rule_Service_Provider extends Service_Provider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->register_commands();
    }
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->load_migrations_from(__DIR__ . '/../Database/Migrations');
        $this->call_after_resolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('product:price-rule:index')->daily_at('00:01');
        });
        $this->app->register(Event_Service_Provider::class);
    }
    /**
     * Register the console commands of this package.
     */
    protected function register_commands()
    {
        if ($this->app->running_in_console()) {
            $this->commands([Price_Rule_Index::class]);
        }
    }
}