<?php

declare (strict_types=1);
namespace Webkul\Admin\Listeners;

use Illuminate\Support\Facades\Mail;
use Webkul\Sales\Contracts\Order_Comment;
class Base
{
    /**
     * Get the locale of the customer if somehow item name changes then the english locale will pe provided.
     *
     * @param object \Webkul\Sales\Contracts\Order|\Webkul\Sales\Contracts\Invoice|\Webkul\Sales\Contracts\Refund|\Webkul\Sales\Contracts\Shipment|\Webkul\Sales\Contracts\OrderComment
     * @return string
     */
    protected function get_locale($object)
    {
        if ($object instanceof Order_Comment) {
            $object = $object->order;
        }
        $object_first_item = $object->items->first();
        return $object_first_item->additional['locale'] ?? 'en';
    }
    /**
     * Prepare mail.
     *
     * @return void
     */
    protected function prepare_mail($entity, $notification)
    {
        $customer_locale = $this->get_locale($entity);
        $previous_locale = core()->get_current_locale()->code;
        app()->set_locale($customer_locale);
        try {
            Mail::queue($notification);
        } catch (\Exception $e) {
            \Log::error('Error in Sending Email' . $e->get_message());
        }
        app()->set_locale($previous_locale);
    }
}