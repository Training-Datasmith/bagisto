<?php

declare (strict_types=1);
namespace Webkul\Admin\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Service_Provider;
use Webkul\Core\Http\Middleware\Prevent_Requests_During_Maintenance;
class Admin_Service_Provider extends Service_Provider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->register_config();
    }
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Route::middleware(['web', Prevent_Requests_During_Maintenance::class])->group(__DIR__ . '/../Routes/web.php');
        $this->load_translations_from(__DIR__ . '/../Resources/lang', 'admin');
        $this->load_views_from(__DIR__ . '/../Resources/views', 'admin');
        Blade::anonymous_component_path(__DIR__ . '/../Resources/views/components', 'admin');
        $this->app->register(Event_Service_Provider::class);
    }
    /**
     * Register package config.
     */
    protected function register_config(): void
    {
        $this->merge_config_from(dirname(__DIR__) . '/Config/menu.php', 'menu.admin');
        $this->merge_config_from(dirname(__DIR__) . '/Config/acl.php', 'acl');
        $this->merge_config_from(dirname(__DIR__) . '/Config/system.php', 'core');
    }
}