<?php

declare (strict_types=1);
namespace Webkul\Core\Image_Cache;

use Intervention\Image\Abstract_Driver;
use Intervention\Image\Exception\Not_Readable_Exception;
use Intervention\Image\Exception\Not_Supported_Exception;
use Intervention\Image\Image_Manager as BaseImageManager;
class Image_Manager extends Base_Image_Manager
{
    /**
     * Initiates an Image instance from different input types
     *
     * @param  mixed  $data
     * @return \Intervention\Image\Image
     */
    public function make($data)
    {
        $driver = $this->create_driver();
        if ((bool) filter_var($data, FILTER_VALIDATE_URL)) {
            return $this->init_from_url($driver, $data);
        }
        return $driver->init($data);
    }
    /**
     * Init from given URL
     *
     * @param  mixed  $driver
     * @param  string  $url
     * @return \Intervention\Image\Image
     */
    public function init_from_url($driver, $url)
    {
        $domain = config('app.url');
        $options = ['http' => [
            'method' => 'GET',
            'protocol_version' => 1.1,
            // force use HTTP 1.1 for service mesh environment with envoy
            'header' => "Accept-language: en\r\n" . "Domain: {$domain}\r\n" . "User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/97.0.4692.71 Safari/537.36\r\n",
        ]];
        $context = stream_context_create($options);
        if ($data = @file_get_contents($url, false, $context)) {
            return $driver->decoder->init_from_binary($data);
        }
        throw new Not_Readable_Exception('Unable to init from given url (' . $url . ').');
    }
    /**
     * Creates a driver instance according to config settings
     *
     * @return \Intervention\Image\AbstractDriver
     */
    private function create_driver()
    {
        if (is_string($this->config['driver'])) {
            $driver_name = ucfirst($this->config['driver']);
            $driver_class = sprintf('Intervention\Image\%s\Driver', $driver_name);
            if (class_exists($driver_class)) {
                return new $driver_class();
            }
            throw new Not_Supported_Exception("Driver ({$driver_name}) could not be instantiated.");
        }
        if ($this->config['driver'] instanceof Abstract_Driver) {
            return $this->config['driver'];
        }
        throw new Not_Supported_Exception('Unknown driver type.');
    }
}