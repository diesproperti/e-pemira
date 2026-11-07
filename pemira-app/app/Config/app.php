<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

return [
    'app_name'        => 'PEMIRA Kampus',
    'app_SubTitle'    => 'Sistem Pemilihan Ketua Organisasi Mahasiswa Berbasis Web',
    'institution'     => 'Komisi Pemilihan Raya Mahasiswa (KPRM)',
    'version'         => '1.1.0',
    'timezone'        => 'Asia/Jakarta',
    'session_name'    => 'PEMIRA_SESSID',
    'upload_max_size' => 5 * 1024 * 1024, // 5 MB
    'db' => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('DB_PORT') ?: '3306',
        'database' => getenv('DB_DATABASE') ?: 'pemira_db',
        'username' => getenv('DB_USERNAME') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset'  => 'utf8mb4',
    ],
];

