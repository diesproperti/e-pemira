<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Core\Session;

final class AuditService
{
    public static function log(
        string $action,
        ?string $entityType = null,
        ?int $entityId = null,
        string $description = '',
        ?array $actorOverride = null
    ): void {
        try {
            $pdo = Database::connection();
            $user = $actorOverride ?? Session::user();

            $userId = isset($user['id']) ? (int) $user['id'] : null;
            $actorName = (string) ($user['name'] ?? 'Guest / System');
            $actorRole = (string) ($user['role'] ?? 'guest');

            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'CLI'), 0, 250);
            $now = date('Y-m-d H:i:s');

            $stmt = $pdo->prepare(
                'INSERT INTO audit_logs (user_id, actor_name, actor_role, action, entity_type, entity_id, description, ip_address, user_agent, created_at)
                 VALUES (:uid, :actor, :role, :action, :etype, :eid, :desc, :ip, :ua, :created)'
            );
            $stmt->execute([
                ':uid'     => $userId,
                ':actor'   => $actorName,
                ':role'    => $actorRole,
                ':action'  => $action,
                ':etype'   => $entityType,
                ':eid'     => $entityId,
                ':desc'    => $description,
                ':ip'      => $ip,
                ':ua'      => $ua,
                ':created' => $now,
            ]);
        } catch (\Throwable) {
            // Jangan gagalkan request utama apabila pencatatan log mengalami kendala
        }
    }
}

