<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use App\Repositories\AdminRepository;
use Exception;

class AdminSettingsService {
    private $adminRepo;
    private $db;

    public function __construct() {
        $this->adminRepo = new AdminRepository();
        $this->db = Database::getInstance()->getConnection();
    }

    public function getCurrentAdmin() {
        $adminId = (int)(Session::get('admin_id') ?? 0);
        if ($adminId <= 0) {
            return null;
        }
        return $this->adminRepo->findById($adminId);
    }

    public function getBillingSettings() {
        $defaults = [
            'late_joining_enabled' => '1',
            'late_joining_cutoff_day' => '10',
            'proration_method' => 'calendar_days',
            'include_joining_day' => '1',
            'rounding_method' => 'nearest_rupee',
            'default_due_day' => '10',
            'manual_first_month_discount_enabled' => '1',
            'maximum_first_month_discount' => '100',
        ];
        try {
            $rows = $this->db->query('SELECT setting_key, setting_value FROM hostel_settings')->fetchAll(\PDO::FETCH_KEY_PAIR);
            return array_merge($defaults, array_intersect_key($rows, $defaults));
        } catch (\Throwable $e) {
            return $defaults;
        }
    }

    public function updateBillingSettings(array $values) {
        $adminId = (int)(Session::get('admin_id') ?? 0);
        $settings = [
            'late_joining_enabled' => !empty($values['late_joining_enabled']) ? '1' : '0',
            'late_joining_cutoff_day' => (string)max(1, min(31, (int)($values['late_joining_cutoff_day'] ?? 10))),
            'proration_method' => in_array($values['proration_method'] ?? '', ['calendar_days', 'fixed_30_day'], true) ? $values['proration_method'] : 'calendar_days',
            'include_joining_day' => !empty($values['include_joining_day']) ? '1' : '0',
            'rounding_method' => in_array($values['rounding_method'] ?? '', ['exact', 'nearest_rupee'], true) ? $values['rounding_method'] : 'nearest_rupee',
            'default_due_day' => (string)max(1, min(31, (int)($values['default_due_day'] ?? 10))),
            'manual_first_month_discount_enabled' => !empty($values['manual_first_month_discount_enabled']) ? '1' : '0',
            'maximum_first_month_discount' => (string)max(0, min(100, (float)($values['maximum_first_month_discount'] ?? 100))),
        ];
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare('INSERT INTO hostel_settings (setting_key, setting_value, updated_by_admin) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_by_admin = VALUES(updated_by_admin)');
            foreach ($settings as $key => $value) {
                $stmt->execute([$key, $value, $adminId ?: null]);
            }
            $this->db->commit();
            return ['success' => true];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'error' => 'Unable to save fee settings. Apply the billing migration first.'];
        }
    }

    public function updateUsername($newUsername) {
        $adminId = (int)(Session::get('admin_id') ?? 0);
        if ($adminId <= 0) {
            return ['success' => false, 'error' => 'Session expired. Please log in again.'];
        }

        $username = trim((string)$newUsername);
        if ($username === '') {
            return ['success' => false, 'error' => 'Username cannot be empty.'];
        }

        if (!preg_match('/^[A-Za-z0-9_.-]{3,30}$/', $username)) {
            return ['success' => false, 'error' => 'Username must be 3-30 characters and may contain letters, numbers, dots, underscores, and hyphens.'];
        }

        $existing = $this->adminRepo->findByUsername($username);
        if ($existing && (int)$existing['id'] !== $adminId) {
            return ['success' => false, 'error' => 'This username is already in use by another administrator.'];
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('UPDATE admins SET username = ? WHERE id = ?');
            $stmt->execute([$username, $adminId]);
            Session::set('admin_user', $username);
            $this->adminRepo->logAction($adminId, 'Admin Settings', 'Username changed to ' . $username, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $this->db->commit();
            return ['success' => true, 'username' => $username];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Unable to update username: ' . $e->getMessage()];
        }
    }

    public function updatePassword($currentPassword, $newPassword, $confirmPassword) {
        $adminId = (int)(Session::get('admin_id') ?? 0);
        if ($adminId <= 0) {
            return ['success' => false, 'error' => 'Session expired. Please log in again.'];
        }

        $admin = $this->adminRepo->findById($adminId);
        if (!$admin) {
            return ['success' => false, 'error' => 'Administrator account not found.'];
        }

        if (!password_verify((string)$currentPassword, (string)$admin['password_hash'])) {
            return ['success' => false, 'error' => 'Current password is incorrect.'];
        }

        if ((string)$newPassword !== (string)$confirmPassword) {
            return ['success' => false, 'error' => 'New password and confirmation do not match.'];
        }

        if (strlen((string)$newPassword) < 8) {
            return ['success' => false, 'error' => 'New password must be at least 8 characters long.'];
        }

        $hash = password_hash((string)$newPassword, PASSWORD_DEFAULT);
        if ($hash === false || $hash === null) {
            return ['success' => false, 'error' => 'Unable to hash the new password.'];
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
            $stmt->execute([$hash, $adminId]);
            $this->adminRepo->logAction($adminId, 'Admin Settings', 'Password changed successfully', $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'error' => 'Unable to update password: ' . $e->getMessage()];
        }
    }
}
