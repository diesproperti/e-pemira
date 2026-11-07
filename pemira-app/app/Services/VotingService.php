<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Models\Election;
use App\Models\Student;
use DomainException;
use PDOException;

final class VotingService
{
    /**
     * Memproses pemberian suara mahasiswa secara Atomic & Secret Ballot (FR-09, FR-10, FR-11, AC-02, AC-03, AC-04)
     */
    public static function castVote(int $studentId, int $electionId, int $candidateId): string
    {
        $pdo = Database::connection();
        $driver = Database::driver();

        $pdo->beginTransaction();

        try {
            // 1. Kunci baris mahasiswa (MySQL FOR UPDATE) untuk mencegah Race Condition / Double Request
            $lockSql = 'SELECT * FROM students WHERE id = :sid';
            if ($driver === 'mysql') {
                $lockSql .= ' FOR UPDATE';
            }
            $stmtStudent = $pdo->prepare($lockSql);
            $stmtStudent->execute([':sid' => $studentId]);
            $student = $stmtStudent->fetch();

            if (!$student) {
                throw new DomainException('Data identitas mahasiswa tidak ditemukan.');
            }

            if (!Student::isEligible($student)) {
                throw new DomainException(
                    'Hak pilih ditolak: Status Mahasiswa (' . $student['status_mahasiswa'] . ') atau Status Semester (' . $student['status_semester'] . ') Anda tidak aktif.'
                );
            }

            // 2. Validasi periode pemilihan dan rentang waktu (FR-08, AC-03)
            $stmtElection = $pdo->prepare('SELECT * FROM elections WHERE id = :eid LIMIT 1');
            $stmtElection->execute([':eid' => $electionId]);
            $election = $stmtElection->fetch();

            if (!$election) {
                throw new DomainException('Periode pemilihan tidak ditemukan.');
            }

            if (!Election::isVotingOpen($election)) {
                $state = Election::getTimeState($election);
                throw new DomainException(
                    'Voting tidak dapat dilakukan: ' . $state['label'] . '. Pastikan memilih dalam jadwal yang ditentukan.'
                );
            }

            // 3. Validasi apakah kandidat terdaftar pada periode pemilihan ini
            $stmtCand = $pdo->prepare(
                'SELECT id, candidate_number, name FROM candidates WHERE id = :cid AND election_id = :eid LIMIT 1'
            );
            $stmtCand->execute([
                ':cid' => $candidateId,
                ':eid' => $electionId,
            ]);
            $candidate = $stmtCand->fetch();

            if (!$candidate) {
                throw new DomainException('Kandidat yang dipilih tidak sah untuk periode pemilihan ini.');
            }

            // 4. Pencegahan voting ganda (FR-10, AC-02)
            $stmtCheck = $pdo->prepare(
                'SELECT id, receipt_code FROM voter_attendances WHERE election_id = :eid AND student_id = :sid LIMIT 1'
            );
            $stmtCheck->execute([
                ':eid' => $electionId,
                ':sid' => $studentId,
            ]);
            if ($stmtCheck->fetch()) {
                throw new DomainException('Anda sudah menggunakan hak pilih pada periode pemilihan ini. Satu mahasiswa hanya dapat memberikan satu suara.');
            }

            $now = date('Y-m-d H:i:s');
            $receiptCode = 'PMR-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(5)));
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'Browser'), 0, 250);

            // 5. Catat daftar hadir memilih di voter_attendances (TANPA candidate_id)
            $stmtAttendance = $pdo->prepare(
                'INSERT INTO voter_attendances (election_id, student_id, receipt_code, ip_address, user_agent, voted_at)
                 VALUES (:eid, :sid, :receipt, :ip, :ua, :voted_at)'
            );
            $stmtAttendance->execute([
                ':eid'      => $electionId,
                ':sid'      => $studentId,
                ':receipt'  => $receiptCode,
                ':ip'       => $ip,
                ':ua'       => $ua,
                ':voted_at' => $now,
            ]);

            // 6. Masukkan surat suara anonim ke kotak suara (votes) TANPA student_id (Secret Ballot)
            $stmtVote = $pdo->prepare(
                'INSERT INTO votes (election_id, candidate_id, cast_at)
                 VALUES (:eid, :cid, :cast_at)'
            );
            $stmtVote->execute([
                ':eid'     => $electionId,
                ':cid'     => $candidateId,
                ':cast_at' => $now,
            ]);

            $pdo->commit();

            return $receiptCode;
        } catch (DomainException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Constraint unique violation fallback
            if (str_contains(strtolower($e->getMessage()), 'unique') || str_contains(strtolower($e->getMessage()), 'duplicate')) {
                throw new DomainException('Transaksi ditolak: Anda sudah memberikan suara pada periode pemilihan ini.');
            }
            throw new DomainException('Terjadi kesalahan sistem saat menyimpan suara. Silakan coba lagi.');
        }
    }
}

