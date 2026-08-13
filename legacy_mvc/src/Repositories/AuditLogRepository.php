<?php
namespace App\Repositories;

use App\Core\Database;
use PDO;

class AuditLogRepository {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findAll(array $filters = [], $limit = 20, $offset = 0, $sort = 'created_at', $direction = 'DESC') {
        $allowedSort = ['created_at', 'action', 'admin_id', 'entity_type'];
        $sortColumn = in_array($sort, $allowedSort, true) ? $sort : 'created_at';
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';

        $sql = "SELECT l.*, a.username AS admin_username
                FROM system_logs l
                LEFT JOIN admins a ON a.id = l.admin_id
                WHERE 1 = 1";
        $params = [];

        if (!empty($filters['action'])) {
            $sql .= ' AND l.action = :action';
            $params['action'] = trim((string)$filters['action']);
        }

        if (!empty($filters['entity_type'])) {
            $sql .= ' AND l.entity_type = :entity_type';
            $params['entity_type'] = trim((string)$filters['entity_type']);
        }

        if (!empty($filters['admin'])) {
            $sql .= ' AND a.username LIKE :admin';
            $params['admin'] = '%' . trim((string)$filters['admin']) . '%';
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(l.created_at) >= :date_from';
            $params['date_from'] = trim((string)$filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(l.created_at) <= :date_to';
            $params['date_to'] = trim((string)$filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string)$filters['search']);
            $sql .= ' AND (l.description LIKE :search OR CAST(l.entity_id AS CHAR) LIKE :search_entity_id)';
            $params['search'] = '%' . $search . '%';
            $params['search_entity_id'] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY l.' . $sortColumn . ' ' . $direction . ' LIMIT :limit OFFSET :offset';

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }

        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function count(array $filters = []) {
        $sql = "SELECT COUNT(*)
                FROM system_logs l
                LEFT JOIN admins a ON a.id = l.admin_id
                WHERE 1 = 1";
        $params = [];

        if (!empty($filters['action'])) {
            $sql .= ' AND l.action = :action';
            $params['action'] = trim((string)$filters['action']);
        }

        if (!empty($filters['entity_type'])) {
            $sql .= ' AND l.entity_type = :entity_type';
            $params['entity_type'] = trim((string)$filters['entity_type']);
        }

        if (!empty($filters['admin'])) {
            $sql .= ' AND a.username LIKE :admin';
            $params['admin'] = '%' . trim((string)$filters['admin']) . '%';
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND DATE(l.created_at) >= :date_from';
            $params['date_from'] = trim((string)$filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND DATE(l.created_at) <= :date_to';
            $params['date_to'] = trim((string)$filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string)$filters['search']);
            $sql .= ' AND (l.description LIKE :search OR CAST(l.entity_id AS CHAR) LIKE :search_entity_id)';
            $params['search'] = '%' . $search . '%';
            $params['search_entity_id'] = '%' . $search . '%';
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return (int)$stmt->fetchColumn();
    }

    public function findById($id) {
        $stmt = $this->db->prepare(
            "SELECT l.*, a.username AS admin_username
             FROM system_logs l
             LEFT JOIN admins a ON a.id = l.admin_id
             WHERE l.id = :id"
        );
        $stmt->execute(['id' => (int)$id]);
        return $stmt->fetch();
    }
}
