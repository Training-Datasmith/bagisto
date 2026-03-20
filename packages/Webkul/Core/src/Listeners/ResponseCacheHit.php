<?php

declare (strict_types=1);
namespace Webkul\Core\Listeners;

use Spatie\Response_Cache\Events\Response_Cache_Hit as ResponseCacheHitEvent;
use Webkul\Core\Jobs\Update_Create_Visitable_Index;
use Webkul\Core\Jobs\Update_Create_Visit_Index;
class Response_Cache_Hit
{
    /**
     * @param  \Spatie\ResponseCache\Events\ResponseCacheHit  $request
     * @return void
     */
    public function handle(Response_Cache_Hit_Event $event)
    {
        if (!core()->get_config_data('general.general.visitor_options.enabled')) {
            return;
        }
        $log = visitor()->get_log();
        if (request()->route()->get_name() == 'shop.home.index') {
            Update_Create_Visit_Index::dispatch(null, $log);
            return;
        }
        Update_Create_Visitable_Index::dispatch(array_merge($log, ['path_info' => $event->request->get_path_info()]));
    }
}