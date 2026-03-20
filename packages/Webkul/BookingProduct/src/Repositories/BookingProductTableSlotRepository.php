<?php

declare (strict_types=1);
namespace Webkul\Booking_Product\Repositories;

use Webkul\Booking_Product\Contracts\Booking_Product_Table_Slot;
use Webkul\Core\Eloquent\Repository;
class Booking_Product_Table_Slot_Repository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return Booking_Product_Table_Slot::class;
    }
}