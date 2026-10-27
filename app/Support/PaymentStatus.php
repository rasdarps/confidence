<?php

namespace App\Support;

class PaymentStatus
{
    public const FULL_PAYMENT = 'full_payment';
    public const PARTIAL_PAYMENT = 'partial_payment';
    public const FULL_CREDIT = 'full_credit';

    public static function canonicalStatuses(): array
    {
        return [
            self::FULL_PAYMENT,
            self::PARTIAL_PAYMENT,
            self::FULL_CREDIT,
        ];
    }

    public static function normalize(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        $normalized = strtolower(trim($status));

        return match ($normalized) {
            self::FULL_PAYMENT => self::FULL_PAYMENT,
            self::PARTIAL_PAYMENT => self::PARTIAL_PAYMENT,
            self::FULL_CREDIT => self::FULL_CREDIT,
            default => null,
        };
    }

    public static function isCredit(string $status): bool
    {
        return in_array(self::normalize($status), [self::PARTIAL_PAYMENT, self::FULL_CREDIT], true);
    }
}
