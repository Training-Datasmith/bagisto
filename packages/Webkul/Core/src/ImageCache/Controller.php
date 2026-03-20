<?php

declare (strict_types=1);
namespace Webkul\Core\Image_Cache;

use Closure;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Facades\Config;
use Intervention\Image\Image_Cache_Controller;
class Controller extends Image_Cache_Controller
{
    /**
     * Cache template.
     *
     * @var string
     */
    protected $template;
    /**
     * Logo.
     *
     * @var string
     */
    public const BAGISTO_LOGO = 'https://updates.bagisto.com/bagisto.png';
    /**
     * Get HTTP response of either original image file or
     * template applied file.
     *
     * @param  string  $template
     * @param  string  $filename
     * @return Illuminate\Http\Response
     */
    public function get_response($template, $filename)
    {
        switch (strtolower($template)) {
            case 'original':
                return $this->get_original($filename);
            case 'download':
                return $this->get_download($filename);
            default:
                return $this->get_image($template, $filename);
        }
    }
    /**
     * Get HTTP response of template applied image file
     *
     * @param  string  $template
     * @param  string  $filename
     * @return Illuminate\Http\Response
     */
    public function get_image($template, $filename)
    {
        $this->template = $template;
        $cache_time = $template == 'logo' ? 10080 : config('imagecache.lifetime');
        if ($template == 'logo') {
            $path = self::BAGISTO_LOGO;
        } else {
            $template = $this->get_template($template);
            $path = $this->get_image_path($filename);
        }
        /**
         * Image manipulation based on callback
         */
        $manager = new Image_Manager(Config::get('image'));
        try {
            $content = $manager->cache(function ($image) use ($template, $path) {
                if ($template instanceof Closure) {
                    /**
                     * Build from closure callback template
                     */
                    $template($image->make($path));
                } elseif (is_object($template)) {
                    /**
                     * Build from filter template
                     */
                    $image->make($path)->filter($template);
                } else {
                    $image->make($path);
                }
            }, $cache_time);
        } catch (\Exception $e) {
            if ($template != 'logo') {
                abort(404);
            }
            $content = '';
        }
        return $this->build_response($content);
    }
    /**
     * Builds HTTP response from given image data
     *
     * @param  string  $content
     * @return Illuminate\Http\Response
     */
    protected function build_response($content)
    {
        /**
         * Define mime type
         */
        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $content);
        /**
         * Respond with 304 not modified if browser has the image cached
         */
        $e_tag = md5($content);
        $not_modified = isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] == $e_tag;
        $content = $not_modified ? null : $content;
        $status_code = $not_modified ? 304 : 200;
        $max_age = ($this->template == 'logo' ? 10080 : config('imagecache.lifetime')) * 60;
        /**
         * Return http response
         */
        return new Illuminate_Response($content, $status_code, ['Content-Type' => $mime, 'Cache-Control' => 'max-age=' . $max_age . ', public', 'Content-Length' => strlen($content), 'Etag' => $e_tag]);
    }
}