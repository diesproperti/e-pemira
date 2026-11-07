<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;
    private static string $driver = 'mysql';

    public static function connection(): PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = require __DIR__ . '/app.php';
        $db = $config['db'];
        $forceSqlite = getenv('DB_DRIVER') === 'sqlite';

        if (!$forceSqlite && extension_loaded('pdo_mysql')) {
            try {
                $dsnWithoutDb = sprintf(
                    'mysql:host=%s;port=%s;charset=%s',
                    $db['host'],
                    $db['port'],
                    $db['charset']
                );
                $pdo = new PDO($dsnWithoutDb, $db['username'], $db['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_TIMEOUT            => 2,
                ]);

                $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $db['database']);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$dbName}`");

                self::$instance = $pdo;
                self::$driver = 'mysql';
                self::ensureInitialized($pdo, 'mysql');
                return self::$instance;
            } catch (PDOException) {
                // Fallback to SQLite if MySQL server in Laragon is not started yet
            }
        }

        $storageDir = dirname(__DIR__, 2) . '/storage/database';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }

        $sqlitePath = $storageDir . '/pemira.sqlite';
        $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');

        self::$instance = $pdo;
        self::$driver = 'sqlite';
        self::ensureInitialized($pdo, 'sqlite');

        return self::$instance;
    }

    public static function driver(): string
    {
        if (self::$instance === null) {
            self::connection();
        }
        return self::$driver;
    }

    private static function ensureInitialized(PDO $pdo, string $driver): void
    {
        try {
            $stmt = $pdo->query('SELECT COUNT(*) FROM users');
            $count = (int) $stmt->fetchColumn();
            if ($count > 0) {
                return;
            }
        } catch (PDOException) {
            self::createTables($pdo, $driver);
        }

        self::seedInitialData($pdo);
    }

    private static function createTables(PDO $pdo, string $driver): void
    {
        if ($driver === 'mysql') {
            $schemaFile = dirname(__DIR__, 2) . '/database/schema.sql';
            if (is_file($schemaFile)) {
                $sql = (string) file_get_contents($schemaFile);
                // Remove CREATE DATABASE / USE statements as we already selected DB
                $sql = preg_replace('/CREATE DATABASE.*?;/is', '', $sql) ?? $sql;
                $sql = preg_replace('/USE\s+`?pemira_db`?\s*;/is', '', $sql) ?? $sql;
                $pdo->exec($sql);
                return;
            }
        }

        // SQLite compatible DDL (mirrors database/schema.sql 1:1)
        $queries = [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'student',
                is_active INTEGER NOT NULL DEFAULT 1,
                must_change_password INTEGER NOT NULL DEFAULT 1,
                password_changed_at TEXT NULL,
                last_login_at TEXT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS students (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                nim TEXT NOT NULL UNIQUE,
                nama TEXT NOT NULL,
                jenjang TEXT NOT NULL DEFAULT 'S1',
                program_studi TEXT NOT NULL,
                periode_masuk TEXT NOT NULL,
                status_mahasiswa TEXT NOT NULL DEFAULT 'Aktif',
                status_semester TEXT NOT NULL DEFAULT 'Aktif',
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS elections (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                organization_name TEXT NOT NULL DEFAULT 'BEM Universitas',
                period_label TEXT NOT NULL DEFAULT '2026/2027',
                description TEXT NULL,
                start_time TEXT NOT NULL,
                end_time TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'draft',
                is_result_published INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS candidates (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                election_id INTEGER NOT NULL,
                candidate_number INTEGER NOT NULL,
                name TEXT NOT NULL,
                nim TEXT NOT NULL,
                jenjang TEXT NOT NULL DEFAULT 'S1',
                program_studi TEXT NOT NULL,
                tagline TEXT NULL,
                photo_path TEXT NULL,
                vision TEXT NOT NULL,
                mission TEXT NOT NULL,
                created_at TEXT NOT NULL,
                updated_at TEXT NOT NULL,
                UNIQUE (election_id, candidate_number),
                FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS voter_attendances (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                election_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                receipt_code TEXT NOT NULL UNIQUE,
                ip_address TEXT NULL,
                user_agent TEXT NULL,
                voted_at TEXT NOT NULL,
                UNIQUE (election_id, student_id),
                FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE RESTRICT,
                FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE RESTRICT
            )",
            "CREATE TABLE IF NOT EXISTS votes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                election_id INTEGER NOT NULL,
                candidate_id INTEGER NOT NULL,
                cast_at TEXT NOT NULL,
                FOREIGN KEY (election_id) REFERENCES elections(id) ON DELETE RESTRICT,
                FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE RESTRICT
            )",
            "CREATE TABLE IF NOT EXISTS import_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                filename TEXT NOT NULL,
                total_rows INTEGER NOT NULL DEFAULT 0,
                inserted_count INTEGER NOT NULL DEFAULT 0,
                updated_count INTEGER NOT NULL DEFAULT 0,
                failed_count INTEGER NOT NULL DEFAULT 0,
                error_details TEXT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            "CREATE TABLE IF NOT EXISTS audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                actor_name TEXT NOT NULL,
                actor_role TEXT NOT NULL,
                action TEXT NOT NULL,
                entity_type TEXT NULL,
                entity_id INTEGER NULL,
                description TEXT NOT NULL,
                ip_address TEXT NULL,
                user_agent TEXT NULL,
                created_at TEXT NOT NULL
            )",
        ];

        foreach ($queries as $sql) {
            $pdo->exec($sql);
        }
    }

    private static function seedInitialData(PDO $pdo): void
    {
        $now = date('Y-m-d H:i:s');

        // 1. Seed Super Admin (username: admin, password: password123)
        $adminHash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmtUser = $pdo->prepare(
            'INSERT INTO users (username, name, password_hash, role, is_active, must_change_password, created_at, updated_at)
             VALUES (:username, :name, :password_hash, :role, :is_active, :must_change_password, :created_at, :updated_at)'
        );
        $stmtUser->execute([
            ':username'             => 'admin',
            ':name'                 => 'Administrator KPRM',
            ':password_hash'        => $adminHash,
            ':role'                 => 'admin',
            ':is_active'            => 1,
            ':must_change_password' => 0,
            ':created_at'           => $now,
            ':updated_at'           => $now,
        ]);
        $adminId = (int) $pdo->lastInsertId();

        // 2. Seed Initial Dataset Mahasiswa (matching NIM, Nama, Jenjang, Program Studi, Periode masuk, Status Mahasiswa, Status Semester)
        $studentsData = [
            ['230101001', 'Budi Santoso', 'S1', 'Teknik Informatika', '2023/2024 Ganjil', 'Aktif', 'Aktif'],
            ['230101002', 'Siti Aminah', 'S1', 'Sistem Informasi', '2023/2024 Ganjil', 'Aktif', 'Aktif'],
            ['230101003', 'Rizky Pratama', 'S1', 'Teknik Informatika', '2023/2024 Ganjil', 'Aktif', 'Aktif'],
            ['230102004', 'Dewi Lestari', 'S1', 'Manajemen', '2023/2024 Ganjil', 'Aktif', 'Aktif'],
            ['240103005', 'Fajar Nugroho', 'D4', 'Teknologi Rekayasa Perangkat Lunak', '2024/2025 Ganjil', 'Aktif', 'Aktif'],
            ['240104006', 'Nadia Putri Maharani', 'S1', 'Akuntansi', '2024/2025 Ganjil', 'Aktif', 'Aktif'],
            ['220101007', 'Andi Wijaya', 'S1', 'Teknik Informatika', '2022/2023 Ganjil', 'Cuti', 'Cuti'],
            ['210102008', 'Hendra Saputra', 'S1', 'Manajemen', '2021/2022 Ganjil', 'Non-Aktif', 'Non-Aktif'],
        ];

        $stmtStudent = $pdo->prepare(
            'INSERT INTO students (user_id, nim, nama, jenjang, program_studi, periode_masuk, status_mahasiswa, status_semester, created_at, updated_at)
             VALUES (:user_id, :nim, :nama, :jenjang, :program_studi, :periode_masuk, :status_mahasiswa, :status_semester, :created_at, :updated_at)'
        );

        foreach ($studentsData as [$nim, $nama, $jenjang, $prodi, $periode, $statusMhs, $statusSmt]) {
            $isActive = (strcasecmp($statusMhs, 'Aktif') === 0 && strcasecmp($statusSmt, 'Aktif') === 0) ? 1 : 0;
            $stmtUser->execute([
                ':username'             => $nim,
                ':name'                 => $nama,
                ':password_hash'        => password_hash($nim, PASSWORD_BCRYPT),
                ':role'                 => 'student',
                ':is_active'            => $isActive,
                ':must_change_password' => 1,
                ':created_at'           => $now,
                ':updated_at'           => $now,
            ]);
            $uid = (int) $pdo->lastInsertId();

            $stmtStudent->execute([
                ':user_id'          => $uid,
                ':nim'              => $nim,
                ':nama'             => $nama,
                ':jenjang'          => $jenjang,
                ':program_studi'    => $prodi,
                ':periode_masuk'    => $periode,
                ':status_mahasiswa' => $statusMhs,
                ':status_semester'  => $statusSmt,
                ':created_at'       => $now,
                ':updated_at'       => $now,
            ]);
        }

        // 3. Seed Active Election Period
        $startTime = date('Y-m-d H:i:s', strtotime('-1 day'));
        $endTime   = date('Y-m-d H:i:s', strtotime('+7 days'));

        $stmtElection = $pdo->prepare(
            'INSERT INTO elections (title, organization_name, period_label, description, start_time, end_time, status, is_result_published, created_at, updated_at)
             VALUES (:title, :org, :period, :desc, :start, :end, :status, :pub, :created, :updated)'
        );
        $stmtElection->execute([
            ':title'   => 'Pemilihan Ketua Badan Eksekutif Mahasiswa (BEM)',
            ':org'     => 'BEM Universitas',
            ':period'  => '2026/2027',
            ':desc'    => 'Pemilihan Raya Ketua BEM Universitas Periode 2026/2027 secara langsung, umum, bebas, dan rahasia (Secret Ballot) bagi seluruh mahasiswa aktif.',
            ':start'   => $startTime,
            ':end'     => $endTime,
            ':status'  => 'active',
            ':pub'     => 0,
            ':created' => $now,
            ':updated' => $now,
        ]);
        $electionId = (int) $pdo->lastInsertId();

        // 4. Seed 3 Candidates
        $candidates = [
            [
                'number'  => 1,
                'name'    => 'Dimas Arya Pratama',
                'nim'     => '220101011',
                'jenjang' => 'S1',
                'prodi'   => 'Teknik Informatika',
                'tagline' => 'Kampus Kolaboratif, Inovatif, dan Berdampak Nyata',
                'vision'  => 'Mewujudkan BEM Universitas sebagai poros pergerakan mahasiswa yang inklusif, berbasis inovasi digital, dan responsif terhadap advokasi kesejahteraan mahasiswa.',
                'mission' => "1. Membangun sistem advokasi terpadu yang transparan dan cepat tanggap bagi seluruh mahasiswa.\n2. Mengakselerasi digitalisasi layanan organisasi dan inkubasi karya prestasi mahasiswa lintas program studi.\n3. Memperluas jejaring kolaborasi strategis dengan mitra industri, alumni, dan masyarakat.",
            ],
            [
                'number'  => 2,
                'name'    => 'Aulia Rahma Wijaya',
                'nim'     => '220102024',
                'jenjang' => 'S1',
                'prodi'   => 'Manajemen',
                'tagline' => 'Sinergi Karya untuk Kemajuan Bersama',
                'vision'  => 'Menjadikan organisasi kemahasiswaan sebagai ruang tumbuh yang progresif, berintegritas tinggi, dan berorientasi pada pengembangan kepemimpinan masa depan.',
                'mission' => "1. Menghadirkan program pengembangan soft-skill, kepemimpinan, dan kewirausahaan mahasiswa secara berkelanjutan.\n2. Menguatkan sinergi antar Himpunan Mahasiswa Jurusan (HMJ) dan Unit Kegiatan Mahasiswa (UKM).\n3. Menyelenggarakan tata kelola anggaran dan program kerja organisasi yang akuntabel serta terbuka.",
            ],
            [
                'number'  => 3,
                'name'    => 'Bagas Adi Nugraha',
                'nim'     => '220103018',
                'jenjang' => 'S1',
                'prodi'   => 'Sistem Informasi',
                'tagline' => 'Bergerak Bersama, Wujudkan Kampus Berdaya',
                'vision'  => 'Menciptakan ekosistem kemahasiswaan yang aspiratif, solutif, dan unggul dalam riset serta pengabdian masyarakat.',
                'mission' => "1. Mengoptimalkan forum aspirasi mahasiswa secara berkala bersama pimpinan kampus.\n2. Mendorong lahirnya komunitas riset, kompetisi nasional, dan pengabdian desa binaan.\n3. Menciptakan lingkungan kampus yang aman, ramah, dan inklusif bagi seluruh sivitas akademika.",
            ],
        ];

        $stmtCand = $pdo->prepare(
            'INSERT INTO candidates (election_id, candidate_number, name, nim, jenjang, program_studi, tagline, photo_path, vision, mission, created_at, updated_at)
             VALUES (:election_id, :number, :name, :nim, :jenjang, :prodi, :tagline, :photo, :vision, :mission, :created, :updated)'
        );

        foreach ($candidates as $c) {
            $stmtCand->execute([
                ':election_id' => $electionId,
                ':number'      => $c['number'],
                ':name'        => $c['name'],
                ':nim'         => $c['nim'],
                ':jenjang'     => $c['jenjang'],
                ':prodi'       => $c['prodi'],
                ':tagline'     => $c['tagline'],
                ':photo'       => null,
                ':vision'      => $c['vision'],
                ':mission'     => $c['mission'],
                ':created'     => $now,
                ':updated'     => $now,
            ]);
        }

        // 5. Seed Initial Audit Log
        $stmtAudit = $pdo->prepare(
            'INSERT INTO audit_logs (user_id, actor_name, actor_role, action, entity_type, entity_id, description, ip_address, user_agent, created_at)
             VALUES (:uid, :actor, :role, :action, :etype, :eid, :desc, :ip, :ua, :created)'
        );
        $stmtAudit->execute([
            ':uid'     => $adminId,
            ':actor'   => 'Administrator KPRM',
            ':role'    => 'admin',
            ':action'  => 'SYSTEM_INIT',
            ':etype'   => 'elections',
            ':eid'     => $electionId,
            ':desc'    => 'Inisialisasi awal sistem PEMIRA, dataset sampel mahasiswa aktif, dan periode pemilihan.',
            ':ip'      => '127.0.0.1',
            ':ua'      => 'System Bootstrapper',
            ':created' => $now,
        ]);
    }
}

