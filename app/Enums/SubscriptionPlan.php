<?php

namespace App\Enums;

enum SubscriptionPlan: string
{
    case BASIC = 'basic';
    case PREMIUM = 'premium';
    case PRO = 'pro';

    public function getPrice(): float
    {
        return match ($this) {
            self::BASIC => 9.99,
            self::PREMIUM => 19.99,
            self::PRO => 29.99,
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::BASIC => 'Basic Plan - Essential features',
            self::PREMIUM => 'Premium Plan - Advanced features',
            self::PRO => 'Pro Plan - All features included',
        };
    }

    public static function getAllPlans(): array
    {
        return array_map(fn ($case) => [
            'name' => $case->value,
            'price' => $case->getPrice(),
            'description' => $case->getDescription(),
        ], self::cases());
    }
}
