<?php

namespace App\Interfaces;

use App\Models\Subscription;

interface PaymentServiceInterface
{
    public function processPayment(Subscription $subscription, float $amount): bool;

    public function validatePaymentMethod(int $userId): bool;

    public function getPaymentStatus(string $transactionId): string;
}
