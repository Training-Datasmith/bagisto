<?php

declare (strict_types=1);
namespace Webkul\Core\Acl;

use Illuminate\Support\Collection;
class Acl_Item
{
    /**
     * Create a new AclItem instance.
     */
    public function __construct(public string $key, public string $name, public string $route, public int $sort, public Collection $children)
    {
    }
}