<?php

declare (strict_types=1);
namespace Webkul\Core\Helpers\Exchange;

abstract class Exchange_Rate
{
    abstract public function update_rates();
}