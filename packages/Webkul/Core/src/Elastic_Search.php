<?php

declare (strict_types=1);
namespace Webkul\Core;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\Client_Builder;
use Illuminate\Support\Arr;
class Elastic_Search
{
    /**
     * Map configuration array keys with ES ClientBuilder setters
     *
     * @var array
     */
    protected $config_mappings = ['retries' => 'setRetries', 'caBundle' => 'setCABundle'];
    /**
     * Make a new connection.
     */
    protected function make_connection(?string $name = null): Client
    {
        $connection = $name ?: $this->get_default_connection();
        $config = $this->get_connection_config($connection);
        $client_builder = Client_Builder::create();
        if ($connection == 'default') {
            /**
             * Build default connection.
             */
            $client_builder->set_hosts($config['hosts'])->set_basic_authentication($config['user'] ?: '', $config['pass'] ?: '');
        } elseif ($connection == 'api') {
            /**
             * Build API key connection.
             */
            $client_builder->set_hosts($config['hosts'])->set_api_key($config['key']);
        } elseif ($connection == 'cloud') {
            /**
             * Build elastic cloud connection.
             */
            $client_builder->set_elastic_cloud_id($config['id']);
            if ($config['api_key']) {
                $client_builder->set_api_key($config['api_key']);
            } else {
                $client_builder->set_basic_authentication($config['user'], $config['pass']);
            }
        }
        /**
         * Set additional client configuration.
         */
        foreach ($this->config_mappings as $key => $method) {
            $value = Arr::get(config('elasticsearch'), $key);
            if (!is_null($value)) {
                $client_builder->{$method}($value);
            }
        }
        return $client_builder->build();
    }
    /**
     * Get the default connection.
     */
    public function get_default_connection(): string
    {
        return config('elasticsearch.connection');
    }
    /**
     * Get the configuration for a named connection.
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function get_connection_config(string $name)
    {
        $connections = config('elasticsearch.connections');
        if (null === $config = Arr::get($connections, $name)) {
            throw new \InvalidArgumentException("Elasticsearch connection [{$name}] not configured.");
        }
        return $config;
    }
    /**
     * Dynamically pass methods to the default connection.
     *
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return call_user_func_array([$this->make_connection(), $method], $parameters);
    }
}