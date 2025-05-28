<?php

namespace App\Enums;

enum RenewalStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    public function getDescription(): string
    {
        return match ($this) {
            self::PENDING => 'Renewal is pending',
            self::SUCCESS => 'Renewal completed successfully',
            self::FAILED => 'Renewal failed',
            self::CANCELLED => 'Renewal was cancelled',
        };
    }

    public function isCompleted(): bool
    {
        return in_array($this, [self::SUCCESS, self::FAILED, self::CANCELLED]);
    }
}
