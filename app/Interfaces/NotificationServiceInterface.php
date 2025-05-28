<?php

namespace App\Interfaces;

use App\Models\User;
use App\Models\Subscription;
use App\Enums\NotificationType;

interface NotificationServiceInterface
{
    public function sendNotification(User $user, Subscription $subscription, NotificationType $type, string $message): void;

    public function queueReminderEmail(User $user, Subscription $subscription): void;

    public function queueRenewalNotification(User $user, Subscription $subscription, bool $success): void;
}
