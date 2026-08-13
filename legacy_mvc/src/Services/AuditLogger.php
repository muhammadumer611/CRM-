<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Session;
use PDO;
use Throwable;

class AuditLogger {
    public static function log($adminId = null, $action = 'SYSTEM', $entityType = null, $entityId = null, $description = '', $oldValues = null, $newValues = null, $ipAddress = null, $userAgent = null) {
        try {
            $db = Database::getInstance()->getConnection();

            $sql = "INSERT INTO system_logs (
                        admin_id,
                        action,
                        entity_type,
                        entity_id,
                        description,
                        old_values,
                        new_values,
                        ip_address,
                        user_agent,
                        created_at
                    ) VALUES (
                        :admin_id,
                        :action,
                        :entity_type,
                        :entity_id,
                        :description,
                        :old_values,
                        :new_values,
                        :ip_address,
                        :user_agent,
                        NOW()
                    )";

            $stmt = $db->prepare($sql);
            $stmt->execute([
                'admin_id' => $adminId,
                'action' => trim((string)$action),
                'entity_type' => $entityType ? trim((string)$entityType) : null,
                'entity_id' => $entityId !== null && $entityId !== '' ? (int)$entityId : null,
                'description' => trim((string)$description),
                'old_values' => self::prepareJson($oldValues),
                'new_values' => self::prepareJson($newValues),
                'ip_address' => $ipAddress ? substr((string)$ipAddress, 0, 45) : null,
                'user_agent' => $userAgent ? substr((string)$userAgent, 0, 255) : null,
            ]);

            return (int)$db->lastInsertId();
        } catch (Throwable $e) {
            error_log('Audit log failed: ' . $e->getMessage());
            return false;
        }
    }

    public static function logAdminAction($action, $entityType = null, $entityId = null, $description = '', $oldValues = null, $newValues = null) {
        $adminId = Session::get('admin_id');
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        return self::log($adminId, $action, $entityType, $entityId, $description, $oldValues, $newValues, $ipAddress, $userAgent);
    }

    public static function prepareJson($value) {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return $decoded !== null ? json_encode($decoded, JSON_UNESCAPED_SLASHES) : json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        return json_encode($value, JSON_UNESCAPED_SLASHES);
    }
}
