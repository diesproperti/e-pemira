<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            $config = require dirname(__DIR__) . '/Config/app.php';
            session_name($config['session_name'] ?? 'PEMIRA_SESSID');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, ?string $message = null): ?string
    {
        self::start();
        if ($message !== null) {
            $_SESSION['_flash'][$key] = $message;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function setFlashData(string $key, mixed $data): void
    {
        self::start();
        $_SESSION['_flash_data'][$key] = $data;
    }

    public static function getFlashData(string $key, mixed $default = null): mixed
    {
        self::start();
        $val = $_SESSION['_flash_data'][$key] ?? $default;
        unset($_SESSION['_flash_data'][$key]);
        return $val;
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION['_csrf_token'];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::start();
        if (empty($token) || empty($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals((string) $_SESSION['_csrf_token'], $token);
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION['auth_user'] ?? null;
    }

    public static function isLoggedIn(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && ($user['role'] ?? '') === 'admin';
    }

    public static function isStudent(): bool
    {
        $user = self::user();
        return $user !== null && ($user['role'] ?? '') === 'student';
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                (bool) $params['secure'],
                (bool) $params['httponly']
            );
        }
        session_destroy();
    }
}
