<?php

// app/Jobs/RenewSubscriptionJob.php

namespace App\Jobs;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Interfaces\SubscriptionServiceInterface;

class RenewSubscriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(
        private Subscription $subscription
    ) {}

    public function handle(SubscriptionServiceInterface $subscriptionService): void
    {
        try {
            \info('Processing subscription renewal', [
                'subscription_id' => $this->subscription->id,
            ]);

            $success = $subscriptionService->renewSubscription($this->subscription);

            if ($success) {
                \info('Subscription renewal completed successfully', [
                    'subscription_id' => $this->subscription->id,
                ]);
            } else {
                \info('Subscription renewal failed', [
                    'subscription_id' => $this->subscription->id,
                ]);
            }

        } catch (\Exception $e) {
            \info('Subscription renewal job failed', [
                'subscription_id' => $this->subscription->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        \info('RenewSubscriptionJob failed permanently', [
            'subscription_id' => $this->subscription->id,
            'error' => $exception->getMessage(),
        ]);
    }
}
