<?php

declare (strict_types=1);
namespace Webkul\Admin\Listeners;

use Illuminate\Support\Facades\Mail;
use Webkul\Admin\Mail\Customer\GDPR\New_Request_Notification;
use Webkul\Admin\Mail\Customer\GDPR\Status_Update_Notification;
class GDPR extends Base
{
    /**
     * Send mail on creating GDPR request
     *
     * @param  \Webkul\GDPR\Models\GDPRDataRequest  $gdprRequest
     * @return void
     */
    public function after_gdpr_request_created($gdpr_request)
    {
        try {
            Mail::queue(new New_Request_Notification($gdpr_request));
        } catch (\Exception $e) {
            report($e);
        }
    }
    /**
     * Send mail on creating GDPR request
     *
     * @param  \Webkul\GDPR\Models\GDPRDataRequest  $gdprRequest
     * @return void
     */
    public function after_gdpr_request_updated($gdpr_request)
    {
        try {
            Mail::queue(new Status_Update_Notification($gdpr_request));
        } catch (\Exception $e) {
            report($e);
        }
    }
}