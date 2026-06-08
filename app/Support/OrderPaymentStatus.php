<?php

namespace App\Support;

final class OrderPaymentStatus
{
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const FAILED = 'failed';
    public const EXPIRED = 'expired';
    public const CANCELLED = 'cancelled';
    public const REFUNDED = 'refunded';

    public static function terminalStatuses(): array
    {
        return [
            self::PAID,
            self::FAILED,
            self::EXPIRED,
            self::CANCELLED,
            self::REFUNDED,
        ];
    }
}
