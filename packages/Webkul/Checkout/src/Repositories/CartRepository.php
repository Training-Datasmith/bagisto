<?php

declare(strict_types=1);

namespace Webkul\Checkout\Repositories;

use Webkul\Core\Eloquent\Repository;

class CartRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Checkout\Contracts\Cart';
    }
}
