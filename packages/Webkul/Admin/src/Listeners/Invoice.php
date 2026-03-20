<?php

declare (strict_types=1);
namespace Webkul\Admin\Listeners;

use Webkul\Admin\Mail\Order\Invoiced_Notification;
use Webkul\Sales\Repositories\Order_Transaction_Repository;
class Invoice extends Base
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(protected Order_Transaction_Repository $order_transaction_repository)
    {
    }
    /**
     * After order is created
     *
     * @param  \Webkul\Sale\Contracts\Invoice  $invoice
     * @return void
     */
    public function after_created($invoice)
    {
        $this->send_mail($invoice);
        if ($invoice->can_create_transaction) {
            $this->create_transaction($invoice);
        }
    }
    /**
     * Send Transaction mail.
     *
     * @param  \Webkul\Sale\Contracts\Invoice  $invoice
     * @return void
     */
    public function send_mail($invoice)
    {
        try {
            if (!core()->get_config_data('emails.general.notifications.emails.general.notifications.new_invoice_mail_to_admin')) {
                return;
            }
            $this->prepare_mail($invoice, new Invoiced_Notification($invoice));
        } catch (\Exception $e) {
            report($e);
        }
    }
    /**
     * Create the transaction data for Money-transfer and Cash-on-delivery.
     *
     * @param  \Webkul\Sale\Contracts\Invoice  $invoice
     * @return void
     */
    public function create_transaction($invoice)
    {
        $transaction_id = md5(uniqid());
        $transaction_data = ['transaction_id' => $transaction_id, 'status' => $invoice->state, 'type' => $invoice->order->payment->method, 'payment_method' => $invoice->order->payment->method, 'order_id' => $invoice->order->id, 'invoice_id' => $invoice->id, 'amount' => $invoice->grand_total];
        $this->order_transaction_repository->create($transaction_data);
    }
}