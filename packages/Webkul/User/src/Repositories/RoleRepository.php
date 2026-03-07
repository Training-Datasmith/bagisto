<?php

declare(strict_types=1);

namespace Webkul\User\Repositories;

use Webkul\Core\Eloquent\Repository;

class RoleRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return 'Webkul\User\Contracts\Role';
    }
}
