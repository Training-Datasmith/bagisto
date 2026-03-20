<?php

declare (strict_types=1);
namespace Webkul\Customer\Repositories;

use Webkul\Core\Eloquent\Repository;
class Customer_Note_Repository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Customer\Contracts\CustomerNote';
    }
}