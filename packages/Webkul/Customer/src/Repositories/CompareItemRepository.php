<?php

declare (strict_types=1);
namespace Webkul\Customer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Contracts\Compare_Item;
class Compare_Item_Repository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return Compare_Item::class;
    }
}