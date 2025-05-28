<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\RenewSubscriptionJob;
use App\Interfaces\NotificationServiceInterface;
use App\Interfaces\SubscriptionServiceInterface;

class ProcessSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:process {--dry-run : Show what would be processed without making changes}';

    protected $description = 'Process subscription renewals and send reminder notifications';

    public function __construct(
        private SubscriptionServiceInterface $subscriptionService,
        private NotificationServiceInterface $notificationService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting subscription processing...');

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        try {
            // Get subscriptions expiring in 3 days
            $expiringSubscriptions = $this->subscriptionService->getExpiringSubscriptions(3);

            $this->info("Found {$expiringSubscriptions->count()} subscriptions expiring soon");

            $renewalCount = 0;
            $reminderCount = 0;

            foreach ($expiringSubscriptions as $subscription) {
                $this->line("Processing subscription ID: {$subscription->id} (User: {$subscription->user->email})");

                if ($subscription->canBeRenewed()) {
                    if (! $dryRun) {
                        RenewSubscriptionJob::dispatch($subscription);
                    }
                    $renewalCount++;
                    $this->info('  → Renewal job queued');
                } else {
                    if (! $dryRun) {
                        // Queue reminder email
                        $this->notificationService->queueReminderEmail($subscription->user, $subscription);
                    }
                    $reminderCount++;
                    $this->info('  → Reminder email queued');
                }
            }

            $this->info("\nProcessing Summary:");
            $this->info("- Renewals queued: {$renewalCount}");
            $this->info("- Reminders queued: {$reminderCount}");

            if (! $dryRun) {
                \info('Subscription processing completed', [
                    'renewals_queued' => $renewalCount,
                    'reminders_queued' => $reminderCount,
                    'total_processed' => $expiringSubscriptions->count(),
                ]);
            }

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Failed to process subscriptions: {$e->getMessage()}");

            \info('Subscription processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
