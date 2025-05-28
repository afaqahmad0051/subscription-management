<?php

namespace App\Interfaces;

use App\Models\User;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Collection;

interface SubscriptionServiceInterface
{
    public function createSubscription(User $user, string $planName): Subscription;

    public function renewSubscription(Subscription $subscription): bool;

    /**
     * @return Collection<int, Subscription>
     */
    public function getExpiringSubscriptions(int $days = 3): Collection;

    public function processRenewal(Subscription $subscription): bool;

    public function cancelSubscription(Subscription $subscription): bool;
}
