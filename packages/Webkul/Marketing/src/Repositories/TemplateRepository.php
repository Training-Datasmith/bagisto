<?php

declare(strict_types=1);

namespace Webkul\Marketing\Repositories;

use Webkul\Core\Eloquent\Repository;

class TemplateRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\Marketing\Contracts\Template';
    }
}
