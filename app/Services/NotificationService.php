<?php

// app/Services/NotificationService.php

namespace App\Services;

use App\Models\User;
use App\Models\Subscription;
use App\Jobs\SendReminderJob;
use App\Enums\NotificationType;
use App\Models\NotificationLog;
use App\Jobs\SendNotificationJob;
use App\Interfaces\NotificationServiceInterface;

class NotificationService implements NotificationServiceInterface
{
    public function sendNotification(User $user, Subscription $subscription, NotificationType $type, string $message): void
    {
        $notification = NotificationLog::create([
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'type' => $type,
            'message' => $message,
            'sent_at' => now(),
            'is_sent' => false,
        ]);

        SendNotificationJob::dispatch($notification);

        \info('Notification queued', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
            'type' => $type->value,
        ]);
    }

    public function queueReminderEmail(User $user, Subscription $subscription): void
    {
        SendReminderJob::dispatch($user, $subscription);

        \info('Reminder email queued', [
            'user_id' => $user->id,
            'subscription_id' => $subscription->id,
        ]);
    }

    public function queueRenewalNotification(User $user, Subscription $subscription, bool $success): void
    {
        $type = $success ? NotificationType::RENEWAL_SUCCESS : NotificationType::RENEWAL_FAILED;
        $message = $success
            ? "Your {$subscription->plan_name->value} subscription has been renewed successfully."
            : "We couldn't renew your {$subscription->plan_name->value} subscription. Please update your payment method.";

        $this->sendNotification($user, $subscription, $type, $message);
    }
}
