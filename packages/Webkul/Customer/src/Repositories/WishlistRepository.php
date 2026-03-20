<?php

declare (strict_types=1);
namespace Webkul\Customer\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Customer\Contracts\Wishlist;
class Wishlist_Repository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Wishlist::class;
    }
}