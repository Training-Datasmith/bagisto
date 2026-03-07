<?php

declare(strict_types=1);

namespace Webkul\Core\Repositories;

use Webkul\Core\Eloquent\Repository;

class ExchangeRateRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\Core\Contracts\CurrencyExchangeRate';
    }
}
