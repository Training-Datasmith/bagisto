<?php

declare (strict_types=1);
namespace Webkul\Attribute\Providers;

use Illuminate\Support\Service_Provider;
class Attribute_Service_Provider extends Service_Provider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->load_migrations_from(__DIR__ . '/../Database/Migrations');
        $this->load_translations_from(__DIR__ . '/../Resources/lang', 'attribute');
    }
}