<?php

declare(strict_types=1);

use Webkul\Payment\Facades\Payment;

if (! function_exists('payment')) {
    /**
     * Payment helper.
     *
     * @return \Webkul\Payment\Payment
     */
    function payment()
    {
        return Payment::getFacadeRoot();
    }
}
