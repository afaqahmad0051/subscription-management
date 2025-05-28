<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Enums\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\SubscribeRequest;
use App\Interfaces\SubscriptionServiceInterface;

class UserSubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionServiceInterface $subscriptionService
    ) {}

    /**
     * Get authenticated user's subscriptions.
     */
    public function index(Request $request): JsonResponse
    {
        $subscriptions = $request->user()
            ->subscriptions()
            ->with(['renewalLogs', 'notificationLogs'])
            ->when($request->has('active_only'), function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $subscriptions->through(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'plan_name' => $subscription->plan_name->value,
                    'plan_description' => $subscription->plan_name->getDescription(),
                    'price' => $subscription->price,
                    'start_date' => $subscription->start_date->toISOString(),
                    'end_date' => $subscription->end_date->toISOString(),
                    'is_active' => $subscription->is_active,
                    'auto_renew' => $subscription->auto_renew,
                    'days_until_expiry' => max(0, now()->startOfDay()->diffInDays($subscription->end_date->startOfDay())),
                    'is_expiring_soon' => $subscription->isExpiringSoon(),
                    'is_expired' => $subscription->isExpired(),
                    'can_be_renewed' => $subscription->canBeRenewed(),
                    'created_at' => $subscription->created_at->toISOString(),
                    'updated_at' => $subscription->updated_at->toISOString(),
                    'renewal_logs' => $subscription->relationLoaded('renewalLogs') ? $subscription->renewalLogs->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'status' => $log->status->value,
                            'message' => $log->message,
                            'run_at' => $log->run_at->toISOString(),
                            'created_at' => $log->created_at->toISOString(),
                        ];
                    }) : null,
                    'notification_logs' => $subscription->relationLoaded('notificationLogs') ? $subscription->notificationLogs->map(function ($log) {
                        return [
                            'id' => $log->id,
                            'type' => $log->type->value,
                            'message' => $log->message,
                            'is_sent' => $log->is_sent,
                            'sent_at' => $log->sent_at ? $log->sent_at->toISOString() : null,
                            'created_at' => $log->created_at->toISOString(),
                        ];
                    }) : null,
                ];
            }),
        ]);
    }

    /**
     * Subscribe user to a plan.
     */
    public function subscribe(SubscribeRequest $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            $existingSubscription = $user->activeSubscriptions()
                ->where('plan_name', $request->plan_name)
                ->first();

            if ($existingSubscription) {
                return response()->json([
                    'message' => 'You already have an active subscription for this plan.',
                    'errors' => ['plan_name' => ['Already subscribed to this plan']],
                ], 422);
            }

            $subscription = $this->subscriptionService->createSubscription(
                $user,
                $request->plan_name
            );

            \info('User subscribed to plan', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'plan' => $request->plan_name,
            ]);

            return response()->json([
                'message' => 'Successfully subscribed to plan.',
                'data' => [
                    'id' => $subscription->id,
                    'plan_name' => $subscription->plan_name->value,
                    'plan_description' => $subscription->plan_name->getDescription(),
                    'price' => $subscription->price,
                    'start_date' => $subscription->start_date->toISOString(),
                    'end_date' => $subscription->end_date->toISOString(),
                    'is_active' => $subscription->is_active,
                    'auto_renew' => $subscription->auto_renew,
                    'created_at' => $subscription->created_at->toISOString(),
                ],
            ], 201);

        } catch (\Exception $e) {
            \info('Subscription creation failed', [
                'user_id' => $request->user()->id,
                'plan' => $request->plan_name,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to create subscription. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Cancel user's subscription.
     */
    public function cancel(Request $request, int $subscriptionId): JsonResponse
    {
        $subscription = $request->user()
            ->subscriptions()
            ->where('id', $subscriptionId)
            ->firstOrFail();

        if (! $subscription->is_active) {
            return response()->json([
                'message' => 'Subscription is already inactive.',
            ], 422);
        }

        $success = $this->subscriptionService->cancelSubscription($subscription);

        if ($success) {
            $subscription->refresh();

            return response()->json([
                'message' => 'Subscription cancelled successfully.',
                'data' => [
                    'id' => $subscription->id,
                    'plan_name' => $subscription->plan_name->value,
                    'is_active' => $subscription->is_active,
                    'auto_renew' => $subscription->auto_renew,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Failed to cancel subscription. Please try again.',
        ], 500);
    }

    /**
     * Get available subscription plans.
     */
    public function plans(): JsonResponse
    {
        return response()->json([
            'data' => SubscriptionPlan::cases(),
        ]);
    }

    /**
     * Toggle auto-renewal for subscription.
     */
    public function toggleAutoRenew(Request $request, int $subscriptionId): JsonResponse
    {
        $subscription = $request->user()
            ->subscriptions()
            ->where('id', $subscriptionId)
            ->firstOrFail();

        $subscription->auto_renew = ! $subscription->auto_renew;
        $subscription->save();

        $status = $subscription->auto_renew ? 'enabled' : 'disabled';

        return response()->json([
            'message' => "Auto-renewal {$status} successfully.",
            'data' => [
                'id' => $subscription->id,
                'plan_name' => $subscription->plan_name->value,
                'is_active' => $subscription->is_active,
                'auto_renew' => $subscription->auto_renew,
            ],
        ]);
    }
}
