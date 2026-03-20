<?php

declare (strict_types=1);
namespace Webkul\Core;

use Konekt\Concord\Conventions\Concord_Default;
class Core_Convention extends Concord_Default
{
    /**
     * Migration folder.
     */
    public function migrations_folder(): string
    {
        return 'Database/Migrations';
    }
    /**
     * Manifest file.
     */
    public function manifest_file(): string
    {
        return 'Resources/manifest.php';
    }
}