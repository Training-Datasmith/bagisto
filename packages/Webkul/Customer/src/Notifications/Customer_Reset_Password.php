<?php

declare (strict_types=1);
namespace Webkul\Customer\Notifications;

use Illuminate\Auth\Notifications\Reset_Password;
use Illuminate\Notifications\Messages\Mail_Message;
class Customer_Reset_Password extends Reset_Password
{
    /**
     * Build the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function to_mail($notifiable)
    {
        if (static::$to_mail_callback) {
            return call_user_func(static::$to_mail_callback, $notifiable, $this->token);
        }
        return (new Mail_Message())->from(core()->get_sender_email_details()['email'], core()->get_sender_email_details()['name'])->subject(trans('shop::app.mail.forget-password.subject'))->view('shop::emails.customers.forget-password', ['user_name' => $notifiable->name, 'token' => $this->token]);
    }
}