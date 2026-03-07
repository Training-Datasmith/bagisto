<?php

declare(strict_types=1);

namespace Webkul\Sales\Contracts;

interface Sequencer
{
    /**
     * Create and return the next sequence number for e.g. an order.
     */
    public function generate(): string;
}
