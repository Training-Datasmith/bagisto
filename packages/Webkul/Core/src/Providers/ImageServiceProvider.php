<?php

declare (strict_types=1);
namespace Webkul\Core\Providers;

use Intervention\Image\Image_Manager;
use Intervention\Image\Image_Service_Provider as BaseImageServiceProvider;
/**
 * This is the overridden `ImageServiceProvider` class from the `intervention/image` package. The base class
 * supports all versions of Laravel, but this class only supports the current Laravel version used by Bagisto.
 */
class Image_Service_Provider extends Base_Image_Service_Provider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('image', function ($app) {
            return new Image_Manager($this->get_image_config($app));
        });
        $this->app->alias('image', 'Intervention\Image\ImageManager');
    }
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->cache_is_installed() ? $this->bootstrap_image_cache() : null;
    }
    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['image'];
    }
    /**
     * Bootstrap imagecache
     *
     * @return void
     */
    protected function bootstrap_image_cache()
    {
        /**
         * Image cache route.
         */
        if (is_string(config('imagecache.route'))) {
            $filename_pattern = '[ \w\.\/\-\@\(\)\=]+';
            $this->app['router']->get(config('imagecache.route') . '/{template}/{filename}', ['uses' => 'Webkul\Core\ImageCache\Controller@getResponse', 'as' => 'imagecache'])->where(['filename' => $filename_pattern]);
        }
    }
    /**
     * Determines if Intervention Image Cache is installed.
     *
     * @return bool
     */
    private function cache_is_installed()
    {
        return class_exists('Intervention\Image\ImageCache');
    }
    /**
     * Return image configuration as array.
     *
     * @param  Application  $app
     * @return array
     */
    private function get_image_config($app)
    {
        $config = $app['config']->get('image');
        if (is_null($config)) {
            return [];
        }
        return $config;
    }
}