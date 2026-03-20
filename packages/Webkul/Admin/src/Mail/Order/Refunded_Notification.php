<?php

declare (strict_types=1);
namespace Webkul\Admin\Mail\Order;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\Sales\Contracts\Refund;
class Refunded_Notification extends Mailable
{
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public Refund $refund)
    {
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(to: [new Address(core()->get_admin_email_details()['email'], core()->get_admin_email_details()['name'])], subject: trans('admin::app.emails.orders.refunded.subject'));
    }
    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(view: 'admin::emails.orders.refunded');
    }
}