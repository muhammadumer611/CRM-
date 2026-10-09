<?php
namespace App\Services;

use DateTimeInterface;

class BillingPeriodEligibility
{
    public static function validate(int $billingMonth, int $billingYear, ?DateTimeInterface $today = null): ?string
    {
        require_once dirname(__DIR__, 3) . '/helpers/BillingPeriodEligibility.php';
        return \Helpers\BillingPeriodEligibility::validate($billingMonth, $billingYear, $today);
    }

    public static function sqlPredicate(string $alias): string
    {
        require_once dirname(__DIR__, 3) . '/helpers/BillingPeriodEligibility.php';
        return \Helpers\BillingPeriodEligibility::sqlPredicate($alias);
    }
}