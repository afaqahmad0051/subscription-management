<?php

// app/Services/PaymentService.php

namespace App\Services;

use App\Models\Subscription;
use App\Interfaces\PaymentServiceInterface;

class PaymentService implements PaymentServiceInterface
{
    public function processPayment(Subscription $subscription, float $amount): bool
    {
        \info('Processing payment', [
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'user_id' => $subscription->user_id,
        ]);

        usleep(100000); // 0.1 seconds

        $success = rand(1, 100) <= 95;

        if ($success) {
            \info('Payment processed successfully', [
                'subscription_id' => $subscription->id,
                'amount' => $amount,
            ]);
        } else {
            \info('Payment processing failed', [
                'subscription_id' => $subscription->id,
                'amount' => $amount,
            ]);
        }

        return $success;
    }

    public function validatePaymentMethod(int $userId): bool
    {
        \info('Validating payment method', ['user_id' => $userId]);

        return true;
    }

    public function getPaymentStatus(string $transactionId): string
    {
        return 'completed';
    }
}
