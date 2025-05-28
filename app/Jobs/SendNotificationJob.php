<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionNotification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        private NotificationLog $notificationLog
    ) {}

    public function handle(): void
    {
        try {
            Mail::to($this->notificationLog->user)
                ->send(new SubscriptionNotification($this->notificationLog));

            $this->notificationLog->markAsSent();

            \info('Notification email sent successfully', [
                'notification_id' => $this->notificationLog->id,
                'user_id' => $this->notificationLog->user_id,
                'type' => $this->notificationLog->type->value,
            ]);
        } catch (\Exception $e) {
            \info('Failed to send notification email', [
                'notification_id' => $this->notificationLog->id,
                'user_id' => $this->notificationLog->user_id,
                'type' => $this->notificationLog->type->value,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        \info('SendNotificationJob failed permanently', [
            'notification_id' => $this->notificationLog->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
