<?php

declare(strict_types=1);

namespace Webkul\BookingProduct\Repositories;

use Webkul\BookingProduct\Contracts\BookingProductRentalSlot;
use Webkul\Core\Eloquent\Repository;

class BookingProductRentalSlotRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return BookingProductRentalSlot::class;
    }
}
