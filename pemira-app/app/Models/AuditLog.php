<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class AuditLog
{
    public static function getRecent(int $limit = 100, ?string $actionFilter = null): array
    {
        $pdo = Database::connection();
        $sql = 'SELECT * FROM audit_logs WHERE 1=1';
        $params = [];

        if (!empty($actionFilter)) {
            $sql .= ' AND action = :act';
            $params[':act'] = $actionFilter;
        }

        $sql .= ' ORDER BY id DESC LIMIT ' . (int) $limit;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getDistinctActions(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC');
        return array_column($stmt->fetchAll(), 'action');
    }

    public static function getImportLogs(int $limit = 15): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query(
            'SELECT il.*, u.name AS admin_name
             FROM import_logs il
             INNER JOIN users u ON u.id = il.user_id
             ORDER BY il.id DESC
             LIMIT ' . (int) $limit
        );
        return $stmt->fetchAll();
    }
}

