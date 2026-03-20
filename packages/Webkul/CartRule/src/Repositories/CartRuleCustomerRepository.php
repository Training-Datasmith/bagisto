<?php

declare (strict_types=1);
namespace Webkul\Cart_Rule\Repositories;

use Webkul\Core\Eloquent\Repository;
class Cart_Rule_Customer_Repository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\CartRule\Contracts\CartRuleCustomer';
    }
}