<?php

namespace App\Listeners;

use App\Events\SubscriptionRenewed;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Interfaces\NotificationServiceInterface;

class HandleSubscriptionRenewal implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        private NotificationServiceInterface $notificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(SubscriptionRenewed $event): void
    {
        try {
            $subscription = $event->subscription;
            $user = $subscription->user;

            $this->notificationService->queueRenewalNotification($user, $subscription, true);

            \info('Subscription renewal notification queued', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
            ]);

        } catch (\Exception $e) {
            \info('Failed to handle subscription renewal event', [
                'subscription_id' => $event->subscription->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(SubscriptionRenewed $event, \Throwable $exception): void
    {
        \info('HandleSubscriptionRenewal listener failed', [
            'subscription_id' => $event->subscription->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
