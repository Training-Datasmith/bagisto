<?php

declare (strict_types=1);
namespace Webkul\Attribute\Repositories;

use Webkul\Core\Eloquent\Repository;
class Attribute_Option_Translation_Repository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return 'Webkul\Attribute\Contracts\AttributeOptionTranslation';
    }
}