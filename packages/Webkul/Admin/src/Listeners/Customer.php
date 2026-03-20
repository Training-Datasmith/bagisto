<?php

declare (strict_types=1);
namespace Webkul\Admin\Listeners;

use Illuminate\Support\Facades\Mail;
use Webkul\Admin\Mail\Customer\Registration_Notification;
class Customer extends Base
{
    /**
     * After customer is created
     *
     * @param  \Webkul\Customer\Contracts\Customer  $customer
     * @return void
     */
    public function after_created($customer)
    {
        try {
            if (!core()->get_config_data('emails.general.notifications.emails.general.notifications.customer_registration_confirmation_mail_to_admin')) {
                return;
            }
            Mail::queue(new Registration_Notification($customer));
        } catch (\Exception $e) {
            report($e);
        }
    }
}