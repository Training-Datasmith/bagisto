<?php

declare (strict_types=1);
namespace Webkul\Core\Facades;

use Illuminate\Support\Facades\Facade;
use Webkul\Core\Elastic_Search as BaseElasticSearch;
class Elastic_Search extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function get_facade_accessor()
    {
        return Base_Elastic_Search::class;
    }
}