<?php

declare (strict_types=1);
namespace Webkul\Admin\Mail\Customer\GDPR;

use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Webkul\Admin\Mail\Mailable;
use Webkul\GDPR\Contracts\Gdpr_Data_Request;
class New_Request_Notification extends Mailable
{
    /**
     * Create a new message instance.
     */
    public function __construct(public Gdpr_Data_Request $gdpr_request)
    {
    }
    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject_key = $this->gdpr_request->type === 'update' ? 'admin::app.emails.customers.gdpr.new-update-request' : 'admin::app.emails.customers.gdpr.new-delete-request';
        return new Envelope(to: [new Address(core()->get_admin_email_details()['email'], core()->get_admin_email_details()['name'])], subject: trans($subject_key));
    }
    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(view: 'admin::emails.customers.gdpr.new-request');
    }
}