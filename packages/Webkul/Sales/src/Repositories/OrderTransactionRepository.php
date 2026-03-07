<?php

declare(strict_types=1);

namespace Webkul\Sales\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Sales\Contracts\OrderTransaction;

class OrderTransactionRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return OrderTransaction::class;
    }
}
