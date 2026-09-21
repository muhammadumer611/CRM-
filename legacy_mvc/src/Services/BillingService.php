<?php
namespace App\Services;

use PDO;
use DateTimeImmutable;
use DateTimeInterface;

class BillingService
{
    private PDO $db;

    private const DEFAULTS = [
        'late_joining_enabled' => '1',
        'late_joining_cutoff_day' => '10',
        'proration_method' => 'calendar_days',
        'include_joining_day' => '1',
        'rounding_method' => 'nearest_rupee',
        'default_due_day' => '10',
        'manual_first_month_discount_enabled' => '1',
        'maximum_first_month_discount' => '100',
    ];

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function settings(): array
    {
        $settings = self::DEFAULTS;
        try {
            $rows = $this->db->query('SELECT setting_key, setting_value FROM hostel_settings')->fetchAll(PDO::FETCH_KEY_PAIR);
            foreach ($rows as $key => $value) {
                if (array_key_exists($key, $settings)) {
                    $settings[$key] = (string)$value;
                }
            }
        } catch (\Throwable $e) {
            // The migration may not have been applied yet; defaults keep onboarding usable.
        }
        return $settings;
    }

    public function calculate(float $monthlyFee, string $joiningDate, array $input = []): array
    {
        $settings = $this->settings();
        $date = new DateTimeImmutable($joiningDate);
        $daysInMonth = (int)$date->format('t');
        $joiningDay = (int)$date->format('j');
        $mode = strtolower(trim((string)($input['first_month_billing_mode'] ?? 'automatic')));
        $cutoff = max(1, min(31, (int)$settings['late_joining_cutoff_day']));
        $enabled = $settings['late_joining_enabled'] === '1';
        $discount = 0.0;
        $reason = '';
        $payableDays = $daysInMonth;
        $dailyRate = 0.0;

        if ($mode === 'manual') {
            if ($settings['manual_first_month_discount_enabled'] !== '1') {
                throw new \InvalidArgumentException('Manual first-month adjustment is disabled in Fee & Billing Settings.');
            }
            $discount = max(0.0, (float)($input['first_month_discount'] ?? 0));
            $reason = trim((string)($input['first_month_discount_reason'] ?? ''));
            if ($discount > $monthlyFee + 0.01) {
                throw new \InvalidArgumentException('First-month adjustment cannot exceed the monthly fee.');
            }
            if ($discount > 0 && $reason === '') {
                throw new \InvalidArgumentException('Please provide a reason for the manual first-month adjustment.');
            }
        } elseif ($mode === 'proration' || ($mode === 'automatic' && $enabled && $joiningDay > $cutoff)) {
            $payableDays = $settings['include_joining_day'] === '1'
                ? $daysInMonth - $joiningDay + 1
                : $daysInMonth - $joiningDay;
            $payableDays = max(0, $payableDays);
            $basisDays = $settings['proration_method'] === 'fixed_30_day' ? 30 : $daysInMonth;
            $dailyRate = $monthlyFee / $basisDays;
            $proratedAmount = $dailyRate * $payableDays;
            $discount = max(0.0, $monthlyFee - $proratedAmount);
            $reason = 'First-month late joining proration';
        }

        if ($settings['rounding_method'] === 'nearest_rupee') {
            $discount = round($discount, 0);
        } else {
            $discount = round($discount, 2);
        }
        $maximum = $monthlyFee * max(0.0, min(100.0, (float)$settings['maximum_first_month_discount']) / 100);
        $discount = min($discount, $maximum, $monthlyFee);

        return [
            'monthly_fee' => round($monthlyFee, 2),
            'billing_month' => (int)$date->format('n'),
            'billing_year' => (int)$date->format('Y'),
            'days_in_month' => $daysInMonth,
            'payable_days' => $payableDays,
            'daily_rate' => round($dailyRate, 2),
            'discount' => round($discount, 2),
            'net_payable' => round(max(0.0, $monthlyFee - $discount), 2),
            'reason' => $reason,
            'mode' => $mode,
            'due_date' => $this->dueDate((int)$date->format('n'), (int)$date->format('Y'), (int)($input['due_day'] ?? $settings['default_due_day'])),
        ];
    }

    public function createFirstMonthInvoice(int $studentId, float $monthlyFee, string $joiningDate, array $input = []): array
    {
        $calculation = $this->calculate($monthlyFee, $joiningDate, $input);
        $invoiceNumber = 'INV-' . strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
        $stmt = $this->db->prepare('INSERT INTO fee_records (invoice_number, student_id, billing_month, billing_year, invoice_date, amount, additional_charges, discount, paid_amount, due_date, status, charge_type, remarks) VALUES (?, ?, ?, ?, CURDATE(), ?, 0, ?, 0, ?, ?, \'MONTHLY_FEE\', ?)');
        $status = $calculation['net_payable'] <= 0 ? 'Paid' : 'Pending';
        $remarks = trim((string)($input['remarks'] ?? 'First monthly fee'));
        if ($calculation['reason'] !== '') {
            $remarks .= ' - ' . $calculation['reason'];
        }
        $stmt->execute([$invoiceNumber, $studentId, $calculation['billing_month'], $calculation['billing_year'], $monthlyFee, $calculation['discount'], $calculation['due_date'], $status, $remarks]);
        $invoiceId = (int)$this->db->lastInsertId();

        if ($calculation['discount'] > 0) {
            $audit = $this->db->prepare('INSERT INTO discounts (invoice_id, discount_type, discount_value, reason, created_by_admin) VALUES (?, \'FIXED\', ?, ?, ?)');
            $audit->execute([$invoiceId, $calculation['discount'], $calculation['reason'], $input['created_by_admin'] ?? null]);
        }
        return ['id' => $invoiceId] + $calculation;
    }

    private function dueDate(int $month, int $year, int $day): string
    {
        $day = max(1, min($day, (int)(new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month)))->format('t')));
        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }
}
