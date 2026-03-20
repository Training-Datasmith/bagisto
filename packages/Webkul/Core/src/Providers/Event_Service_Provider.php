<?php

declare (strict_types=1);
namespace Webkul\Core\Providers;

use Illuminate\Foundation\Support\Providers\Event_Service_Provider as ServiceProvider;
class Event_Service_Provider extends Service_Provider
{
    /**
     * The event handler mappings for the application.
     *
     * @var array
     */
    protected $listen = ['Prettus\Repository\Events\RepositoryEntityCreated' => ['Webkul\Core\Listeners\CleanCacheRepository'], 'Prettus\Repository\Events\RepositoryEntityUpdated' => ['Webkul\Core\Listeners\CleanCacheRepository'], 'Prettus\Repository\Events\RepositoryEntityDeleted' => ['Webkul\Core\Listeners\CleanCacheRepository'], 'Spatie\ResponseCache\Events\ResponseCacheHit' => ['Webkul\Core\Listeners\ResponseCacheHit']];
}