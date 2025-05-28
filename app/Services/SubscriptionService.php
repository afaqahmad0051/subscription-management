<?php

// app/Services/SubscriptionService.php

namespace App\Services;

use App\Models\User;
use App\Models\RenewalLog;
use App\Enums\RenewalStatus;
use App\Models\Subscription;
use App\Enums\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use App\Events\SubscriptionRenewed;
use App\Interfaces\PaymentServiceInterface;
use Illuminate\Database\Eloquent\Collection;
use App\Interfaces\SubscriptionServiceInterface;

class SubscriptionService implements SubscriptionServiceInterface
{
    public function __construct(
        private PaymentServiceInterface $paymentService
    ) {}

    public function createSubscription(User $user, string $planName): Subscription
    {
        $plan = SubscriptionPlan::from($planName);

        return DB::transaction(function () use ($user, $plan) {
            $subscription = $user->subscriptions()->create([
                'plan_name' => $plan,
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'is_active' => true,
                'auto_renew' => true,
                'price' => $plan->getPrice(),
            ]);

            \info('New subscription created', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan' => $plan->value,
            ]);

            return $subscription;
        });
    }

    public function renewSubscription(Subscription $subscription): bool
    {
        if (! $subscription->canBeRenewed()) {
            $this->logRenewal($subscription, RenewalStatus::FAILED, 'Subscription cannot be renewed');

            return false;
        }

        return $this->processRenewal($subscription);
    }

    public function getExpiringSubscriptions(int $days = 3): Collection
    {
        return Subscription::active()
            ->expiringSoon($days)
            ->with('user')
            ->get();
    }

    public function processRenewal(Subscription $subscription): bool
    {
        try {
            DB::beginTransaction();

            // Process payment
            $paymentSuccess = $this->paymentService->processPayment(
                $subscription,
                $subscription->plan_name->getPrice()
            );

            if (! $paymentSuccess) {
                $this->logRenewal($subscription, RenewalStatus::FAILED, 'Payment processing failed');
                DB::rollBack();

                return false;
            }

            // Extend subscription
            $subscription->renew();

            $this->logRenewal($subscription, RenewalStatus::SUCCESS, 'Subscription renewed successfully');

            // Fire event
            event(new SubscriptionRenewed($subscription));

            DB::commit();

            \info('Subscription renewed successfully', [
                'subscription_id' => $subscription->id,
                'new_end_date' => $subscription->end_date->toISOString(),
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            $this->logRenewal($subscription, RenewalStatus::FAILED, $e->getMessage());

            \info('Subscription renewal failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function cancelSubscription(Subscription $subscription): bool
    {
        try {
            $subscription->deactivate();

            \info('Subscription cancelled', [
                'subscription_id' => $subscription->id,
                'user_id' => $subscription->user_id,
            ]);

            return true;
        } catch (\Exception $e) {
            \info('Failed to cancel subscription', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function logRenewal(Subscription $subscription, RenewalStatus $status, string $message): void
    {
        RenewalLog::create([
            'subscription_id' => $subscription->id,
            'status' => $status,
            'run_at' => now(),
            'message' => $message,
            'metadata' => [
                'plan' => $subscription->plan_name->value,
                'price' => $subscription->price,
                'end_date' => $subscription->end_date->toISOString(),
            ],
        ]);
    }
}
