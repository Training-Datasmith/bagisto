<?php

declare (strict_types=1);
namespace Webkul\Admin\Mail\Customer;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\Customer\Contracts\Customer;
class Registration_Notification extends Mailable
{
    /**
     * Create a new mailable instance.
     *
     * @return void
     */
    public function __construct(public Customer $customer)
    {
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(to: [new Address(core()->get_admin_email_details()['email'], core()->get_admin_email_details()['name'])], subject: trans('admin::app.emails.customers.registration.subject'));
    }
    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(view: 'admin::emails.customers.registration');
    }
}