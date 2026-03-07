<?php

declare(strict_types=1);

namespace Webkul\Sitemap\Repositories;

use Webkul\Core\Eloquent\Repository;
use Webkul\Sitemap\Contracts\Sitemap;

class SitemapRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return Sitemap::class;
    }
}
