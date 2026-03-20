<?php

declare (strict_types=1);
namespace Webkul\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\Should_Queue;
use Illuminate\Mail\Mailable as BaseMailable;
use Illuminate\Queue\Serializes_Models;
class Mailable extends Base_Mailable implements Should_Queue
{
    use Queueable;
    use Serializes_Models;
    /**
     * Add the sender to the message.
     *
     * @param  \Illuminate\Mail\Message  $message
     */
    protected function build_from($message): Mailable
    {
        !empty($this->from) ? $message->from($this->from[0]['address'], $this->from[0]['name']) : $message->from(core()->get_sender_email_details()['email'], core()->get_sender_email_details()['name']);
        return $this;
    }
}