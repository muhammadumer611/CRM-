<?php
namespace App\Services;

use DateTimeImmutable;
use DateTimeInterface;

class BillingPeriodEligibility
{
    public static function validate(int $billingMonth, int $billingYear, ?DateTimeInterface $today = null): ?string
    {
        $today = $today ?: new DateTimeImmutable('now');
        $currentMonth = (int)$today->format('n');
        $currentYear = (int)$today->format('Y');
        $requestedPeriod = ($billingYear * 12) + $billingMonth;
        $currentPeriod = ($currentYear * 12) + $currentMonth;

        if ($requestedPeriod <= $currentPeriod) {
            return null;
        }

        $period = sprintf('%04d-%02d-01', $billingYear, $billingMonth);
        $availableFrom = new DateTimeImmutable($period);
        return $availableFrom->format('F Y') . ' monthly fee is not available yet. It can be created from ' . $availableFrom->format('F 1, Y') . '.';
    }
}