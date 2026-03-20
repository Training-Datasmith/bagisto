<?php

declare (strict_types=1);
namespace Webkul\Admin\Listeners;

use Webkul\Admin\Mail\Order\Refunded_Notification;
use Webkul\Paypal\Payment\Smart_Button;
class Refund extends Base
{
    /**
     * After order is created
     *
     * @param  \Webkul\Sales\Contracts\Refund  $refund
     * @return void
     */
    public function after_created($refund)
    {
        $this->refund_order($refund);
        try {
            if (!core()->get_config_data('emails.general.notifications.emails.general.notifications.new_refund_mail_to_admin')) {
                return;
            }
            $this->prepare_mail($refund, new Refunded_Notification($refund));
        } catch (\Exception $e) {
            report($e);
        }
    }
    /**
     * After Refund is created
     *
     * @param  \Webkul\Sales\Contracts\Refund  $refund
     * @return void
     */
    public function refund_order($refund)
    {
        $order = $refund->order;
        if ($order->payment->method === 'paypal_smart_button') {
            /* getting smart button instance */
            $smart_button = new Smart_Button();
            /* getting paypal oder id */
            $paypal_order_id = $order->payment->additional['orderID'];
            /* getting capture id by paypal order id */
            $capture_id = $smart_button->get_capture_id($paypal_order_id);
            /* now refunding order on the basis of capture id and refund data */
            $smart_button->refund_order($capture_id, ['amount' => ['value' => round($refund->grand_total, 2), 'currency_code' => $refund->order_currency_code]]);
        }
    }
}