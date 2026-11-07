<?php

declare(strict_types=1);

use App\Core\Session;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $root = dirname(__DIR__, 2);
        return $path !== '' ? $root . '/' . ltrim($path, '/\\') : $root;
    }
}

if (!function_exists('base_url')) {
    function base_url(): string
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $dir = rtrim(dirname($scriptName), '/');
        if ($dir === '.' || $dir === '/') {
            return '';
        }
        return $dir;
    }
}

if (!function_exists('url')) {
    function url(string $path = '/'): string
    {
        $base = base_url();
        $cleanPath = '/' . ltrim($path, '/');
        if ($cleanPath === '/') {
            return $base !== '' ? $base . '/' : '/';
        }
        return $base . $cleanPath;
    }
}

if (!function_exists('asset_url')) {
    function asset_url(string $path): string
    {
        $base = base_url();
        $clean = ltrim($path, '/');
        // If running from root index.php (not inside /public), prefix public/ for static files if needed
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        if (!str_ends_with(dirname($scriptName), '/public') && is_file(base_path('public/' . $clean))) {
            return $base . '/public/' . $clean;
        }
        return $base . '/' . $clean;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('format_indo_datetime')) {
    function format_indo_datetime(?string $datetime, bool $withTime = true): string
    {
        if (empty($datetime)) {
            return '-';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return e($datetime);
        }
        $months = [
            1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
        ];
        $d = (int) date('j', $ts);
        $m = $months[(int) date('n', $ts)] ?? date('M', $ts);
        $y = date('Y', $ts);

        if (!$withTime) {
            return "{$d} {$m} {$y}";
        }
        $time = date('H:i', $ts);
        return "{$d} {$m} {$y}, {$time} WIB";
    }
}

if (!function_exists('initials')) {
    function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $first = isset($parts[0][0]) ? mb_strtoupper(mb_substr($parts[0], 0, 1)) : 'U';
        $second = isset($parts[1][0]) ? mb_strtoupper(mb_substr($parts[1], 0, 1)) : '';
        return $first . $second;
    }
}

