<?php

namespace App\Enums;

enum NotificationType: string
{
    case RENEWAL_REMINDER = 'renewal_reminder';
    case RENEWAL_SUCCESS = 'renewal_success';
    case RENEWAL_FAILED = 'renewal_failed';
    case SUBSCRIPTION_EXPIRED = 'subscription_expired';
    case WELCOME = 'welcome';

    public function getSubject(): string
    {
        return match ($this) {
            self::RENEWAL_REMINDER => 'Subscription Renewal Reminder',
            self::RENEWAL_SUCCESS => 'Subscription Renewed Successfully',
            self::RENEWAL_FAILED => 'Subscription Renewal Failed',
            self::SUBSCRIPTION_EXPIRED => 'Subscription Has Expired',
            self::WELCOME => 'Welcome to Our Service',
        };
    }

    public function getTemplate(): string
    {
        return match ($this) {
            self::RENEWAL_REMINDER => 'emails.renewal-reminder',
            self::RENEWAL_SUCCESS => 'emails.renewal-success',
            self::RENEWAL_FAILED => 'emails.renewal-failed',
            self::SUBSCRIPTION_EXPIRED => 'emails.subscription-expired',
            self::WELCOME => 'emails.welcome',
        };
    }
}
