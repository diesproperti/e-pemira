<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Election;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;

final class AuthController extends Controller
{
    public function home(): void
    {
        if (Session::isLoggedIn()) {
            $this->redirect(Session::isAdmin() ? '/admin/dashboard' : '/student/dashboard');
        }
        $this->redirect('/login');
    }

    public function showLogin(): void
    {
        $election = Election::getActiveOrLatest();
        $timeState = Election::getTimeState($election);

        $this->render('auth/login', [
            'title'     => 'Login Portal Pemilihan',
            'election'  => $election,
            'timeState' => $timeState,
        ], null);
    }

    public function login(): void
    {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            Session::flash('error', 'Harap masukkan NIM / Username dan Password Anda.');
            $this->redirect('/login');
        }

        $user = User::findByUsername($username);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            AuditService::log(
                'LOGIN_FAILED',
                'users',
                null,
                "Percobaan login gagal untuk akun/NIM '{$username}'.",
                ['id' => null, 'name' => $username, 'role' => 'guest']
            );
            Session::flash('error', 'Kredensial tidak valid. Periksa kembali NIM/Username dan Password Anda.');
            $this->redirect('/login');
        }

        // Jika role adalah student, wajib validasi terhadap dataset mahasiswa aktif (FR-02, FR-03, AC-01)
        if ($user['role'] === 'student') {
            $student = Student::findByUserId((int) $user['id']);
            if ($student === null) {
                Session::flash('error', 'Data Anda belum terdaftar dalam dataset mahasiswa kampus.');
                $this->redirect('/login');
            }

            if (!Student::isEligible($student) || (int) $user['is_active'] !== 1) {
                AuditService::log(
                    'LOGIN_REJECTED_INACTIVE',
                    'students',
                    (int) $student['id'],
                    sprintf(
                        'Login ditolak untuk NIM %s (%s): Status Mahasiswa=%s, Status Semester=%s.',
                        $student['nim'],
                        $student['nama'],
                        $student['status_mahasiswa'],
                        $student['status_semester']
                    ),
                    ['id' => (int) $user['id'], 'name' => $student['nama'], 'role' => 'student']
                );
                Session::flash(
                    'error',
                    sprintf(
                        'Akses Ditolak: Status akademik Anda tercatat sebagai Status Mahasiswa "%s" dan Status Semester "%s". Hanya mahasiswa berstatus Aktif yang memiliki hak pilih.',
                        $student['status_mahasiswa'],
                        $student['status_semester']
                    )
                );
                $this->redirect('/login');
            }
        }

        Session::regenerate();
        User::updateLastLogin((int) $user['id']);

        $sessionUser = [
            'id'                   => (int) $user['id'],
            'username'             => (string) $user['username'],
            'name'                 => (string) $user['name'],
            'role'                 => (string) $user['role'],
            'must_change_password' => (int) ($user['must_change_password'] ?? 0),
        ];
        Session::set('auth_user', $sessionUser);

        AuditService::log(
            'LOGIN_SUCCESS',
            'users',
            (int) $user['id'],
            "Pengguna {$user['name']} ({$user['username']}) berhasil login sebagai {$user['role']}.",
            $sessionUser
        );

        if ($user['role'] === 'admin') {
            Session::flash('success', 'Selamat datang kembali, ' . $user['name'] . '!');
            $this->redirect('/admin/dashboard');
        }

        if ((int) ($user['must_change_password'] ?? 0) === 1) {
            Session::flash(
                'warning',
                'Selamat datang! Demi keamanan hak pilih Anda, Anda disarankan mengganti password default saat pertama kali login.'
            );
        } else {
            Session::flash('success', 'Selamat datang di Bilik Suara Digital, ' . $user['name'] . '!');
        }

        $this->redirect('/student/dashboard');
    }

    public function updatePassword(): void
    {
        $authUser = Session::user();
        if ($authUser === null) {
            $this->redirect('/login');
        }

        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword     = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        $redirectBack = $authUser['role'] === 'admin' ? '/admin/dashboard' : '/student/dashboard';

        $dbUser = User::findById((int) $authUser['id']);
        if ($dbUser === null || !password_verify($currentPassword, (string) $dbUser['password_hash'])) {
            Session::flash('error', 'Password saat ini yang Anda masukkan tidak sesuai.');
            $this->redirect($redirectBack);
        }

        if (strlen($newPassword) < 6) {
            Session::flash('error', 'Password baru minimal harus terdiri dari 6 karakter.');
            $this->redirect($redirectBack);
        }

        if ($newPassword !== $confirmPassword) {
            Session::flash('error', 'Konfirmasi password baru tidak cocok.');
            $this->redirect($redirectBack);
        }

        User::updatePassword((int) $authUser['id'], $newPassword, false);

        $authUser['must_change_password'] = 0;
        Session::set('auth_user', $authUser);

        AuditService::log(
            'PASSWORD_CHANGED',
            'users',
            (int) $authUser['id'],
            "Pengguna {$authUser['name']} ({$authUser['username']}) memperbarui password akunnya secara mandiri."
        );

        Session::flash('success', 'Password Anda berhasil dikustomisasi dan diperbarui!');
        $this->redirect($redirectBack);
    }

    public function logout(): void
    {
        if (Session::isLoggedIn()) {
            $u = Session::user();
            AuditService::log(
                'LOGOUT',
                'users',
                (int) ($u['id'] ?? 0),
                "Pengguna {$u['name']} ({$u['username']}) logout dari sistem."
            );
        }
        Session::destroy();
        Session::start();
        Session::flash('success', 'Anda telah berhasil keluar dari sesi aplikasi.');
        $this->redirect('/login');
    }
}

