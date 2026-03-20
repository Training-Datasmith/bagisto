<?php

declare (strict_types=1);
namespace Webkul\Customer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Contracts\Customer_Address;
class Customer_Address_Repository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Customer_Address::class;
    }
}