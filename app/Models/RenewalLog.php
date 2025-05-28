<?php

namespace App\Models;

use App\Enums\RenewalStatus;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\RenewalLogFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RenewalLog extends Model
{
    /** @use HasFactory<RenewalLogFactory> */
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'status',
        'run_at',
        'message',
        'metadata',
    ];

    protected $casts = [
        'run_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => RenewalStatus::class,
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<Subscription, $this>
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isSuccessful(): bool
    {
        return $this->status === RenewalStatus::SUCCESS;
    }

    public function isFailed(): bool
    {
        return $this->status === RenewalStatus::FAILED;
    }
}
