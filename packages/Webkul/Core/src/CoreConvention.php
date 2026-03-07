<?php

declare(strict_types=1);

namespace Webkul\Core;

use Konekt\Concord\Conventions\ConcordDefault;

class CoreConvention extends ConcordDefault
{
    /**
     * Migration folder.
     */
    public function migrationsFolder(): string
    {
        return 'Database/Migrations';
    }

    /**
     * Manifest file.
     */
    public function manifestFile(): string
    {
        return 'Resources/manifest.php';
    }
}
