<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\NotificationLog;
use Illuminate\Queue\SerializesModels;

class SubscriptionNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public NotificationLog $notificationLog
    ) {
        $this->subject($this->notificationLog->type->getSubject());
    }

    /**
     * Build the message.
     */
    public function build(): self
    {
        return $this->markdown('emails.subscription', [
            'userName' => $this->notificationLog->user->name,
            'message' => $this->notificationLog->message,
        ]);
    }
}
