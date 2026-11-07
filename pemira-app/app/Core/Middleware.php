<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\Student;
use App\Services\AuditService;

final class Middleware
{
    public static function handle(string $name): void
    {
        match ($name) {
            'guest'   => self::ensureGuest(),
            'auth'    => self::ensureAuthenticated(),
            'admin'   => self::ensureAdmin(),
            'student' => self::ensureActiveStudent(),
            'csrf'    => self::ensureCsrfValid(),
            default   => null,
        };
    }

    private static function ensureGuest(): void
    {
        if (Session::isLoggedIn()) {
            $target = Session::isAdmin() ? '/admin/dashboard' : '/student/dashboard';
            header('Location: ' . url($target));
            exit;
        }
    }

    private static function ensureAuthenticated(): void
    {
        if (!Session::isLoggedIn()) {
            Session::flash('error', 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.');
            header('Location: ' . url('/login'));
            exit;
        }
    }

    private static function ensureAdmin(): void
    {
        self::ensureAuthenticated();
        if (!Session::isAdmin()) {
            AuditService::log(
                'UNAUTHORIZED_ACCESS',
                'security',
                null,
                'Percobaan akses halaman administrasi oleh pengguna non-admin (' . (Session::user()['username'] ?? 'unknown') . ').'
            );
            http_response_code(403);
            Session::flash('error', 'Akses Ditolak (403): Halaman tersebut hanya dapat diakses oleh Administrator.');
            header('Location: ' . url('/student/dashboard'));
            exit;
        }
    }

    private static function ensureActiveStudent(): void
    {
        self::ensureAuthenticated();
        if (!Session::isStudent()) {
            header('Location: ' . url('/admin/dashboard'));
            exit;
        }

        $authUser = Session::user();
        $student = Student::findByUserId((int) $authUser['id']);
        if ($student === null || !Student::isEligible($student)) {
            Session::destroy();
            Session::start();
            Session::flash(
                'error',
                'Akses dihentikan: Status kemahasiswaan atau semester Anda saat ini tidak aktif dalam dataset kampus.'
            );
            header('Location: ' . url('/login'));
            exit;
        }
    }

    public static function ensureCsrfValid(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
            if (!Session::verifyCsrf(is_string($token) ? $token : null)) {
                http_response_code(419);
                Session::flash('error', 'Sesi keamanan formulir telah kedaluwarsa (Invalid CSRF Token). Silakan coba lagi.');
                $referer = $_SERVER['HTTP_REFERER'] ?? url('/login');
                header('Location: ' . $referer);
                exit;
            }
        }
    }
}

