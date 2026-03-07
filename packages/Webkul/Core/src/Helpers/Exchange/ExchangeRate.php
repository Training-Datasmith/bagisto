<?php

declare(strict_types=1);

namespace Webkul\Core\Helpers\Exchange;

abstract class ExchangeRate
{
    abstract public function updateRates();
}
