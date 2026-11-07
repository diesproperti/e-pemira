<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class Vote
{
    public static function getAttendance(int $electionId, int $studentId): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT * FROM voter_attendances
             WHERE election_id = :eid AND student_id = :sid
             LIMIT 1'
        );
        $stmt->execute([
            ':eid' => $electionId,
            ':sid' => $studentId,
        ]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getAttendanceByReceipt(string $receiptCode): ?array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT va.*, s.nim, s.nama, s.jenjang, s.program_studi, e.title AS election_title, e.period_label
             FROM voter_attendances va
             INNER JOIN students s ON s.id = va.student_id
             INNER JOIN elections e ON e.id = va.election_id
             WHERE va.receipt_code = :code
             LIMIT 1'
        );
        $stmt->execute([':code' => trim($receiptCode)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Rekapitulasi perolehan suara per kandidat (FR-12, AC-05)
     */
    public static function getResultsByElection(int $electionId): array
    {
        $pdo = Database::connection();

        $stmtTotal = $pdo->prepare('SELECT COUNT(*) FROM votes WHERE election_id = :eid');
        $stmtTotal->execute([':eid' => $electionId]);
        $totalVotes = (int) $stmtTotal->fetchColumn();

        $stmt = $pdo->prepare(
            'SELECT c.*,
                    COUNT(v.id) AS vote_count
             FROM candidates c
             LEFT JOIN votes v ON v.candidate_id = c.id AND v.election_id = :eid1
             WHERE c.election_id = :eid2
             GROUP BY c.id
             ORDER BY c.candidate_number ASC'
        );
        $stmt->execute([
            ':eid1' => $electionId,
            ':eid2' => $electionId,
        ]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $count = (int) $row['vote_count'];
            $row['vote_count'] = $count;
            $row['percentage'] = $totalVotes > 0 ? round(($count / $totalVotes) * 100, 2) : 0.0;
        }
        unset($row);

        return [
            'total_votes' => $totalVotes,
            'candidates'  => $rows,
        ];
    }

    /**
     * Rekapitulasi tingkat partisipasi pemilih per Program Studi & Jenjang (Tanpa membocorkan pilihan kandidat)
     */
    public static function getTurnoutByProdi(int $electionId): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            "SELECT s.jenjang,
                    s.program_studi,
                    COUNT(s.id) AS total_eligible,
                    COUNT(va.id) AS total_voted
             FROM students s
             LEFT JOIN voter_attendances va
                    ON va.student_id = s.id AND va.election_id = :eid
             WHERE LOWER(s.status_mahasiswa) = 'aktif'
               AND LOWER(s.status_semester) = 'aktif'
             GROUP BY s.jenjang, s.program_studi
             ORDER BY s.program_studi ASC"
        );
        $stmt->execute([':eid' => $electionId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $eligible = (int) $row['total_eligible'];
            $voted = (int) $row['total_voted'];
            $row['total_not_voted'] = max(0, $eligible - $voted);
            $row['percentage'] = $eligible > 0 ? round(($voted / $eligible) * 100, 1) : 0.0;
        }
        unset($row);

        return $rows;
    }

    public static function getRecentAttendances(int $electionId, int $limit = 8): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare(
            'SELECT va.voted_at, va.receipt_code, s.nim, s.nama, s.jenjang, s.program_studi
             FROM voter_attendances va
             INNER JOIN students s ON s.id = va.student_id
             WHERE va.election_id = :eid
             ORDER BY va.voted_at DESC, va.id DESC
             LIMIT ' . (int) $limit
        );
        $stmt->execute([':eid' => $electionId]);
        return $stmt->fetchAll();
    }
}

