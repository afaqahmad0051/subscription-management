<?php

namespace App\Models;

use App\Enums\SubscriptionPlan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Database\Factories\SubscriptionFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Subscription extends Model
{
    /** @use HasFactory<SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_name',
        'start_date',
        'end_date',
        'is_active',
        'auto_renew',
        'price',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'auto_renew' => 'boolean',
        'price' => 'decimal:2',
        'plan_name' => SubscriptionPlan::class,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<RenewalLog, $this>
     */
    public function renewalLogs(): HasMany
    {
        return $this->hasMany(RenewalLog::class);
    }

    /**
     * @return HasMany<NotificationLog, $this>
     */
    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function isExpiringSoon(int $days = 3): bool
    {
        return $this->end_date->between(now(), now()->addDays($days));
    }

    public function isExpired(): bool
    {
        return $this->end_date->isPast();
    }

    public function canBeRenewed(): bool
    {
        return $this->is_active && $this->auto_renew;
    }

    public function renew(int $months = 1): void
    {
        $this->end_date = $this->end_date->addMonths($months);
        $this->save();
    }

    public function deactivate(): void
    {
        $this->is_active = false;
        $this->save();
    }

    /**
     * @param  Builder<Subscription>  $query
     * @return Builder<Subscription>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Subscription>  $query
     * @return Builder<Subscription>
     */
    public function scopeExpiringSoon($query, int $days = 3)
    {
        return $query->whereBetween('end_date', [now(), now()->addDays($days)]);
    }

    /**
     * @param  Builder<Subscription>  $query
     * @return Builder<Subscription>
     */
    public function scopeAutoRenewable($query)
    {
        return $query->where('auto_renew', true);
    }
}
