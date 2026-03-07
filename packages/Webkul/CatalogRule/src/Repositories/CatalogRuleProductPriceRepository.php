<?php

declare(strict_types=1);

namespace Webkul\CatalogRule\Repositories;

use Webkul\Core\Eloquent\Repository;

class CatalogRuleProductPriceRepository extends Repository
{
    /**
     * Specify Model class name.
     */
    public function model(): string
    {
        return 'Webkul\CatalogRule\Contracts\CatalogRuleProductPrice';
    }
}
