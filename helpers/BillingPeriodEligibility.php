<?php
namespace Helpers;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class BillingPeriodEligibility
{
    public static function validate(int $billingMonth, int $billingYear, ?DateTimeInterface $today = null): ?string
    {
        if ($billingMonth < 1 || $billingMonth > 12 || $billingYear < 1) {
            return 'A valid calendar month and year are required.';
        }

        $today = $today ?: new DateTimeImmutable('now');
        $requestedPeriod = ($billingYear * 12) + $billingMonth;
        $currentPeriod = ((int)$today->format('Y') * 12) + (int)$today->format('n');

        if ($requestedPeriod <= $currentPeriod) {
            return null;
        }

        $availableFrom = new DateTimeImmutable(sprintf('%04d-%02d-01', $billingYear, $billingMonth));
        return $availableFrom->format('F Y') . ' monthly fee is not available yet. It can be created from ' . $availableFrom->format('F 1, Y') . '.';
    }

    public static function sqlPredicate(string $alias): string
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $alias)) {
            throw new InvalidArgumentException('Invalid SQL table alias.');
        }

        return "({$alias}.charge_type <> 'MONTHLY_FEE' OR {$alias}.billing_year < YEAR(CURDATE()) OR ({$alias}.billing_year = YEAR(CURDATE()) AND {$alias}.billing_month <= MONTH(CURDATE())))";
    }
}