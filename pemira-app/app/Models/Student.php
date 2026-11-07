<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class Student
{
    public static function findByUserId(int $userId): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT s.*, u.username, u.is_active, u.must_change_password, u.password_changed_at, u.last_login_at
             FROM students s
             INNER JOIN users u ON u.id = s.user_id
             WHERE s.user_id = :uid
             LIMIT 1'
        );
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT s.*, u.username, u.is_active, u.must_change_password, u.last_login_at
             FROM students s
             INNER JOIN users u ON u.id = s.user_id
             WHERE s.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByNim(string $nim): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT s.*, u.username, u.is_active, u.must_change_password
             FROM students s
             INNER JOIN users u ON u.id = s.user_id
             WHERE s.nim = :nim
             LIMIT 1'
        );
        $stmt->execute([':nim' => trim($nim)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Memeriksa apakah mahasiswa memenuhi syarat sebagai pemilih aktif (FR-02, AC-01, Bab 8)
     */
    public static function isEligible(array $student): bool
    {
        $statusMhs = trim((string) ($student['status_mahasiswa'] ?? ''));
        $statusSmt = trim((string) ($student['status_semester'] ?? ''));
        return strcasecmp($statusMhs, 'Aktif') === 0 && strcasecmp($statusSmt, 'Aktif') === 0;
    }

    public static function getAllWithVotingStatus(?int $electionId, array $filters = []): array
    {
        $pdo = Database::connection();

        $sql = 'SELECT s.*, u.must_change_password, u.last_login_at,
                       va.id AS attendance_id, va.voted_at, va.receipt_code
                FROM students s
                INNER JOIN users u ON u.id = s.user_id
                LEFT JOIN voter_attendances va
                       ON va.student_id = s.id AND va.election_id = :election_id
                WHERE 1=1';

        $params = [':election_id' => $electionId ?? 0];

        if (!empty($filters['q'])) {
            $rawQ = trim((string) $filters['q']);
            $q = '%' . $rawQ . '%';
            $sql .= ' AND (s.nama LIKE :q1 OR s.nim LIKE :q2 OR s.program_studi LIKE :q3)';
            $params[':q1'] = $q;
            $params[':q2'] = $q;
            $params[':q3'] = $q;
        }

        if (!empty($filters['alpha']) && preg_match('/^[A-Za-z]$/', (string) $filters['alpha'])) {
            $alphaPrefix = strtoupper((string) $filters['alpha']) . '%';
            $sql .= ' AND UPPER(s.nama) LIKE :alpha';
            $params[':alpha'] = $alphaPrefix;
        }

        if (!empty($filters['program_studi'])) {
            $sql .= ' AND s.program_studi = :prodi';
            $params[':prodi'] = (string) $filters['program_studi'];
        }

        if (!empty($filters['jenjang'])) {
            $sql .= ' AND s.jenjang = :jenjang';
            $params[':jenjang'] = (string) $filters['jenjang'];
        }

        if (!empty($filters['status_mahasiswa'])) {
            $sql .= ' AND LOWER(s.status_mahasiswa) = LOWER(:status_mhs)';
            $params[':status_mhs'] = (string) $filters['status_mahasiswa'];
        }

        if (!empty($filters['voting_status'])) {
            if ($filters['voting_status'] === 'voted') {
                $sql .= ' AND va.id IS NOT NULL';
            } elseif ($filters['voting_status'] === 'not_voted') {
                $sql .= ' AND va.id IS NULL AND LOWER(s.status_mahasiswa) = \'aktif\' AND LOWER(s.status_semester) = \'aktif\'';
            }
        }

        if (!empty($filters['q'])) {
            $prefixQ = trim((string) $filters['q']) . '%';
            $sql .= ' ORDER BY CASE WHEN LOWER(s.nama) LIKE LOWER(:prefix_order) THEN 0 ELSE 1 END, s.nama ASC, s.nim ASC';
            $params[':prefix_order'] = $prefixQ;
        } else {
            $sql .= ' ORDER BY s.nama ASC, s.nim ASC';
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Mengambil saran autocomplete nama/NIM mahasiswa berdasarkan huruf awal / kata kunci
     */
    public static function searchSuggestions(string $keyword, int $limit = 10): array
    {
        $clean = trim($keyword);
        if ($clean === '') {
            return [];
        }

        $pdo = Database::connection();
        $prefix = $clean . '%';
        $any    = '%' . $clean . '%';

        $sql = 'SELECT id, nim, nama, jenjang, program_studi, status_mahasiswa, status_semester
                FROM students
                WHERE LOWER(nama) LIKE LOWER(:q1)
                   OR nim LIKE :q2
                ORDER BY
                    CASE
                        WHEN LOWER(nama) LIKE LOWER(:prefix_nama) THEN 0
                        WHEN nim LIKE :prefix_nim THEN 1
                        ELSE 2
                    END,
                    nama ASC
                LIMIT ' . (int) $limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':q1'          => $any,
            ':q2'          => $any,
            ':prefix_nama' => $prefix,
            ':prefix_nim'  => $prefix,
        ]);

        return $stmt->fetchAll();
    }

    public static function getSummaryStats(?int $electionId): array
    {
        $pdo = Database::connection();

        $totalDataset = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();

        $stmtEligible = $pdo->query(
            "SELECT COUNT(*) FROM students WHERE LOWER(status_mahasiswa) = 'aktif' AND LOWER(status_semester) = 'aktif'"
        );
        $totalEligible = (int) $stmtEligible->fetchColumn();
        $totalNonEligible = max(0, $totalDataset - $totalEligible);

        $totalVoted = 0;
        if ($electionId !== null && $electionId > 0) {
            $stmtVoted = $pdo->prepare('SELECT COUNT(*) FROM voter_attendances WHERE election_id = :eid');
            $stmtVoted->execute([':eid' => $electionId]);
            $totalVoted = (int) $stmtVoted->fetchColumn();
        }

        $totalNotVoted = max(0, $totalEligible - $totalVoted);
        $turnoutPercent = $totalEligible > 0 ? round(($totalVoted / $totalEligible) * 100, 1) : 0.0;

        return [
            'total_dataset'      => $totalDataset,
            'total_eligible'     => $totalEligible,
            'total_non_eligible' => $totalNonEligible,
            'total_voted'        => $totalVoted,
            'total_not_voted'    => $totalNotVoted,
            'turnout_percent'    => $turnoutPercent,
        ];
    }

    public static function getDistinctProdi(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT DISTINCT program_studi FROM students ORDER BY program_studi ASC');
        return array_column($stmt->fetchAll(), 'program_studi');
    }

    public static function getDistinctJenjang(): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->query('SELECT DISTINCT jenjang FROM students ORDER BY jenjang ASC');
        return array_column($stmt->fetchAll(), 'jenjang');
    }

    /**
     * Menghapus satu mahasiswa beserta akun login-nya
     */
    public static function deleteById(int $studentId): ?array
    {
        $student = self::findById($studentId);
        if ($student === null) {
            return null;
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $stmtAtt = $pdo->prepare('DELETE FROM voter_attendances WHERE student_id = :sid');
            $stmtAtt->execute([':sid' => $studentId]);

            $stmtStu = $pdo->prepare('DELETE FROM students WHERE id = :sid');
            $stmtStu->execute([':sid' => $studentId]);

            $stmtUsr = $pdo->prepare("DELETE FROM users WHERE id = :uid AND role = 'student'");
            $stmtUsr->execute([':uid' => (int) $student['user_id']]);

            $pdo->commit();
            return $student;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Menghapus banyak mahasiswa sekaligus secara massal berdasarkan daftar ID
     *
     * @param list<int> $studentIds
     */
    public static function deleteByIds(array $studentIds): int
    {
        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $studentIds), static fn(int $id) => $id > 0)));
        if (empty($cleanIds)) {
            return 0;
        }

        $pdo = Database::connection();
        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));

        $pdo->beginTransaction();
        try {
            $stmtUsers = $pdo->prepare("SELECT user_id FROM students WHERE id IN ({$placeholders})");
            $stmtUsers->execute($cleanIds);
            $userIds = array_map('intval', array_column($stmtUsers->fetchAll(), 'user_id'));

            if (empty($userIds)) {
                $pdo->rollBack();
                return 0;
            }

            $stmtAtt = $pdo->prepare("DELETE FROM voter_attendances WHERE student_id IN ({$placeholders})");
            $stmtAtt->execute($cleanIds);

            $stmtStu = $pdo->prepare("DELETE FROM students WHERE id IN ({$placeholders})");
            $stmtStu->execute($cleanIds);
            $deletedCount = $stmtStu->rowCount();

            $userPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
            $stmtDelUsers = $pdo->prepare("DELETE FROM users WHERE id IN ({$userPlaceholders}) AND role = 'student'");
            $stmtDelUsers->execute($userIds);

            $pdo->commit();
            return $deletedCount;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Menghapus seluruh dataset mahasiswa
     */
    public static function deleteAll(): int
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $total = (int) $pdo->query('SELECT COUNT(*) FROM students')->fetchColumn();
            $pdo->exec('DELETE FROM voter_attendances');
            $pdo->exec('DELETE FROM students');
            $pdo->exec("DELETE FROM users WHERE role = 'student'");
            $pdo->commit();
            return $total;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
