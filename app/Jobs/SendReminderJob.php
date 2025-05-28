<?php

// app/Jobs/SendReminderJob.php

namespace App\Jobs;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use App\Enums\NotificationType;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionNotification;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(
        private User $user,
        private Subscription $subscription
    ) {}

    public function handle(): void
    {
        try {
            $message = "Your {$this->subscription->plan_name->value} subscription expires on {$this->subscription->end_date->format('M d, Y')}. Please ensure your payment method is up to date.";

            $notificationLog = NotificationLog::create([
                'user_id' => $this->user->id,
                'subscription_id' => $this->subscription->id,
                'type' => NotificationType::RENEWAL_REMINDER,
                'message' => $message,
                'sent_at' => now(),
                'is_sent' => false,
            ]);

            Mail::to($this->user)->send(new SubscriptionNotification($notificationLog));

            $notificationLog->markAsSent();

            \info('Reminder email sent successfully', [
                'user_id' => $this->user->id,
                'subscription_id' => $this->subscription->id,
            ]);

        } catch (\Exception $e) {
            \info('Failed to send reminder email', [
                'user_id' => $this->user->id,
                'subscription_id' => $this->subscription->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        \info('SendReminderJob failed permanently', [
            'user_id' => $this->user->id,
            'subscription_id' => $this->subscription->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
