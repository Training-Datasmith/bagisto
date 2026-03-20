<?php

declare (strict_types=1);
namespace Webkul\Admin\Listeners;

use Webkul\Admin\Mail\Order\Inventory_Source_Notification;
use Webkul\Admin\Mail\Order\Shipped_Notification;
use Webkul\Sales\Contracts\Shipment as ShipmentContract;
class Shipment extends Base
{
    /**
     * After order is created
     *
     * @return void
     */
    public function after_created(Shipment_Contract $shipment)
    {
        try {
            if (core()->get_config_data('emails.general.notifications.emails.general.notifications.new_shipment_mail_to_admin')) {
                $this->prepare_mail($shipment, new Shipped_Notification($shipment));
            }
            if (core()->get_config_data('emails.general.notifications.emails.general.notifications.new_inventory_source')) {
                $this->prepare_mail($shipment, new Inventory_Source_Notification($shipment));
            }
        } catch (\Exception $e) {
            report($e);
        }
    }
}