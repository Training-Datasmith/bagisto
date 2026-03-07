<?php

declare(strict_types=1);

namespace Webkul\Attribute\Repositories;

use Webkul\Core\Eloquent\Repository;

class AttributeGroupRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Attribute\Contracts\AttributeGroup';
    }
}
