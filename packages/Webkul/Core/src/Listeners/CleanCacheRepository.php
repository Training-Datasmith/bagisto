<?php

declare (strict_types=1);
namespace Webkul\Core\Listeners;

use Illuminate\Support\Facades\Log;
use Prettus\Repository\Events\Repository_Event_Base;
use Prettus\Repository\Helpers\Cache_Keys;
use Prettus\Repository\Listeners\Clean_Cache_Repository as BaseCleanCacheRepository;
class Clean_Cache_Repository extends Base_Clean_Cache_Repository
{
    public function handle(Repository_Event_Base $event)
    {
        try {
            $this->repository = $event->get_repository();
            $clean_enabled = $this->repository->allowed_clean();
            if ($clean_enabled) {
                $this->model = $event->get_model();
                $this->action = $event->get_action();
                $class_name = get_class($this->repository);
                if (config("repository.cache.repositories.{$class_name}.clean.on.{$this->action}", config("repository.cache.clean.on.{$this->action}", true))) {
                    $cache_keys = Cache_Keys::get_keys($class_name);
                    if (is_array($cache_keys)) {
                        foreach ($cache_keys as $key) {
                            $this->cache->forget($key);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error($e->get_message());
        }
    }
}