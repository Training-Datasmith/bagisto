<?php

declare (strict_types=1);
namespace Webkul\Core\Repositories;

use Webkul\Core\Eloquent\Repository;
class Country_State_Repository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Core\Contracts\CountryState';
    }
}