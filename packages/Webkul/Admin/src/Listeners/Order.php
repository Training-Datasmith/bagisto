<?php

declare (strict_types=1);
namespace Webkul\Admin\Listeners;

use Webkul\Admin\Mail\Order\Canceled_Notification;
use Webkul\Admin\Mail\Order\Created_Notification;
use Webkul\Sales\Contracts\Order as OrderContract;
class Order extends Base
{
    /**
     * After order is created
     *
     * @return void
     */
    public function after_created(Order_Contract $order)
    {
        try {
            if (!core()->get_config_data('emails.general.notifications.emails.general.notifications.new_order_mail_to_admin')) {
                return;
            }
            $this->prepare_mail($order, new Created_Notification($order));
        } catch (\Exception $e) {
            report($e);
        }
    }
    /**
     * Send cancel order mail.
     *
     * @param  \Webkul\Sales\Contracts\Order  $order
     * @return void
     */
    public function after_canceled($order)
    {
        try {
            if (!core()->get_config_data('emails.general.notifications.emails.general.notifications.cancel_order_mail_to_admin')) {
                return;
            }
            $this->prepare_mail($order, new Canceled_Notification($order));
        } catch (\Exception $e) {
            report($e);
        }
    }
}