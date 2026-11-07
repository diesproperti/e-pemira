<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class User
{
    public static function findByUsername(string $username): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => trim($username)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function updateLastLogin(int $id): void
    {
        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare('UPDATE users SET last_login_at = :now, updated_at = :u WHERE id = :id');
        $stmt->execute([
            ':now' => $now,
            ':u'   => $now,
            ':id'  => $id,
        ]);
    }

    public static function updatePassword(int $id, string $plainPassword, bool $mustChange = false): void
    {
        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');
        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare(
            'UPDATE users
             SET password_hash = :hash,
                 must_change_password = :must_change,
                 password_changed_at = :changed_at,
                 updated_at = :updated_at
             WHERE id = :id'
        );
        $stmt->execute([
            ':hash'        => $hash,
            ':must_change' => $mustChange ? 1 : 0,
            ':changed_at'  => $mustChange ? null : $now,
            ':updated_at'  => $now,
            ':id'          => $id,
        ]);
    }
}

