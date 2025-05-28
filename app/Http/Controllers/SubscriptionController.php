<?php

namespace App\Http\Controllers;

use App\Models\RenewalLog;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SubscriptionController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of all subscriptions (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Subscription::class);

        $subscriptions = Subscription::with(['user', 'renewalLogs'])
            ->when($request->has('status'), function ($query) use ($request) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->when($request->has('plan'), function ($query) use ($request) {
                $query->where('plan_name', $request->plan);
            })
            ->when($request->has('expiring_soon'), function ($query) {
                $query->expiringSoon();
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $subscriptions->through(function ($subscription) {
                return [
                    'id' => $subscription->id,
                    'user' => $subscription->whenLoaded('user', function () use ($subscription) {
                        return [
                            'id' => $subscription->user->id,
                            'name' => $subscription->user->name,
                            'email' => $subscription->user->email,
                        ];
                    }),
                    'plan_name' => $subscription->plan_name->value,
                    'plan_description' => $subscription->plan_name->getDescription(),
                    'price' => $subscription->price,
                    'start_date' => $subscription->start_date->toISOString(),
                    'end_date' => $subscription->end_date->toISOString(),
                    'is_active' => $subscription->is_active,
                    'auto_renew' => $subscription->auto_renew,
                    'days_until_expiry' => $subscription->end_date->diffInDays(now(), false),
                    'is_expiring_soon' => $subscription->isExpiringSoon(),
                    'is_expired' => $subscription->isExpired(),
                    'can_be_renewed' => $subscription->canBeRenewed(),
                    'created_at' => $subscription->created_at->toISOString(),
                    'updated_at' => $subscription->updated_at->toISOString(),
                    'renewal_logs' => $subscription->whenLoaded('renewalLogs', function () use ($subscription) {
                        return $subscription->renewalLogs->map(function ($log) {
                            return [
                                'id' => $log->id,
                                'status' => $log->status->value,
                                'message' => $log->message,
                                'run_at' => $log->run_at->toISOString(),
                                'created_at' => $log->created_at->toISOString(),
                            ];
                        });
                    }),
                ];
            }),
        ]);
    }

    /**
     * Display the specified subscription.
     */
    public function show(Subscription $subscription): JsonResponse
    {
        $this->authorize('view', $subscription);

        $subscription->load(['user', 'renewalLogs', 'notificationLogs']);

        return response()->json([
            'data' => [
                'id' => $subscription->id,
                'user' => [
                    'id' => $subscription->user->id,
                    'name' => $subscription->user->name,
                    'email' => $subscription->user->email,
                ],
                'plan_name' => $subscription->plan_name->value,
                'plan_description' => $subscription->plan_name->getDescription(),
                'price' => $subscription->price,
                'start_date' => $subscription->start_date->toISOString(),
                'end_date' => $subscription->end_date->toISOString(),
                'is_active' => $subscription->is_active,
                'auto_renew' => $subscription->auto_renew,
                'days_until_expiry' => $subscription->end_date->diffInDays(now(), false),
                'is_expiring_soon' => $subscription->isExpiringSoon(),
                'is_expired' => $subscription->isExpired(),
                'can_be_renewed' => $subscription->canBeRenewed(),
                'created_at' => $subscription->created_at->toISOString(),
                'updated_at' => $subscription->updated_at->toISOString(),
                'renewal_logs' => $subscription->renewalLogs->map(function (RenewalLog $log): array {
                    return [
                        'id' => $log->id,
                        'status' => $log->status->value,
                        'message' => $log->message,
                        'run_at' => $log->run_at->toISOString(),
                        'created_at' => $log->created_at->toISOString(),
                    ];
                }),
                'notification_logs' => $subscription->notificationLogs->map(function (NotificationLog $log): array {
                    return [
                        'id' => $log->id,
                        'type' => $log->type->value,
                        'message' => $log->message,
                        'is_sent' => $log->is_sent,
                        'sent_at' => optional($log->sent_at)->toISOString(),
                        'created_at' => $log->created_at->toISOString(),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Get subscription statistics (admin only).
     */
    public function statistics(): JsonResponse
    {
        $this->authorize('viewAny', Subscription::class);

        $stats = [
            'total_subscriptions' => Subscription::count(),
            'active_subscriptions' => Subscription::where('is_active', true)->count(),
            'inactive_subscriptions' => Subscription::where('is_active', false)->count(),
            'auto_renew_enabled' => Subscription::where('auto_renew', true)->count(),
            'expiring_soon' => Subscription::active()->expiringSoon()->count(),
            'revenue_this_month' => Subscription::active()
                ->whereMonth('created_at', now()->month)
                ->sum('price'),
            'plans_breakdown' => Subscription::active()
                ->selectRaw('plan_name, COUNT(*) as count, SUM(price) as revenue')
                ->groupBy('plan_name')
                ->get(),
        ];

        return response()->json(['data' => $stats]);
    }
}
