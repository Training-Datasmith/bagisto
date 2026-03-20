<?php

declare (strict_types=1);
namespace Webkul\Core\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Service_Provider;
use Webkul\Theme\View_Render_Event_Manager;
class Core_Service_Provider extends Service_Provider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        include __DIR__ . '/../Http/helpers.php';
        $this->register_commands();
        $this->register_overrides();
    }
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->load_migrations_from(__DIR__ . '/../Database/Migrations');
        $this->load_translations_from(__DIR__ . '/../Resources/lang', 'core');
        $this->load_views_from(__DIR__ . '/../Resources/views', 'core');
        Event::listen('bagisto.shop.layout.body.after', static function (View_Render_Event_Manager $view_render_event_manager) {
            $view_render_event_manager->add_template('core::blade.tracer.style');
        });
        Event::listen('bagisto.admin.layout.head', static function (View_Render_Event_Manager $view_render_event_manager) {
            $view_render_event_manager->add_template('core::blade.tracer.style');
        });
        $this->call_after_resolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('invoice:cron')->daily_at('3:00');
        });
        $this->app->register(Event_Service_Provider::class);
        $this->app->register(Image_Service_Provider::class);
        $this->app->register(Visitor_Service_Provider::class);
    }
    /**
     * Register the console commands of this package.
     */
    protected function register_commands(): void
    {
        if ($this->app->running_in_console()) {
            $this->commands([\Webkul\Core\Console\Commands\Bagisto_Version::class, \Webkul\Core\Console\Commands\Exchange_Rate_Update::class, \Webkul\Core\Console\Commands\Invoice_Overdue_Cron::class, \Webkul\Core\Console\Commands\Translations_Checker::class]);
        }
    }
    /**
     * Register the overrides.
     */
    protected function register_overrides(): void
    {
        $this->app->extend(\Illuminate\Foundation\Console\Up_Command::class, fn() => new \Webkul\Core\Console\Commands\Up_Command());
        $this->app->extend(\Illuminate\Foundation\Console\Down_Command::class, fn() => new \Webkul\Core\Console\Commands\Down_Command());
        $this->app->bind(\Illuminate\Contracts\Debug\Exception_Handler::class, \Webkul\Core\Exceptions\Handler::class);
        $this->app->bind(\Illuminate\Foundation\Http\Middleware\Prevent_Requests_During_Maintenance::class, fn($app) => new \Webkul\Core\Http\Middleware\Prevent_Requests_During_Maintenance($app));
        $this->app->singleton(\Elastic\Elasticsearch\Client::class, fn() => \Webkul\Core\Facades\Elastic_Search::get_facade_application()->connection());
        $this->app->singleton('blade.compiler', fn($app) => new \Webkul\Core\View\Compilers\Blade_Compiler($app['files'], $app['config']['view.compiled']));
    }
}