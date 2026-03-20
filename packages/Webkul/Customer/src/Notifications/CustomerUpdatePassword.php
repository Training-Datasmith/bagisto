<?php

declare (strict_types=1);
namespace Webkul\Customer\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\Serializes_Models;
use Webkul\Customer\Models\Customer;
class Customer_Update_Password extends Mailable
{
    use Queueable;
    use Serializes_Models;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public Customer $customer)
    {
    }
    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from(core()->get_sender_email_details()['email'], core()->get_sender_email_details()['name'])->to($this->customer->email, $this->customer->name)->subject(trans('shop::app.mail.update-password.subject'))->view('shop::emails.customer.update-password', ['user' => $this->customer]);
    }
}