<?php

declare(strict_types=1);

namespace App\Models;

use App\Config\Database;

final class Election
{
    /**
     * Sinkronisasi status periode pemilihan secara real-time berdasarkan waktu server (WIB):
     * 1. Jika waktu saat ini sudah masuk rentang jadwal (start_time <= now <= end_time),
     *    otomatis buka pemilihan (status = 'active').
     * 2. Jika jadwal mulai masih di masa depan (start_time > now) namun status sebelumnya 'closed',
     *    aktifkan kembali jadwal (status = 'active') agar berstatus 'Menunggu Jadwal Buka' dan otomatis terbuka saat waktunya tiba.
     * 3. Jika waktu selesai (end_time < now) telah terlewati, otomatis tutup pemilihan (status = 'closed').
     */
    public static function syncRealTimeStatuses(): void
    {
        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');

        // 1. Otomatis buka periode pemilihan yang sudah memasuki jadwal aktif (start_time <= now <= end_time)
        $stmtOpen = $pdo->prepare(
            "UPDATE elections
             SET status = 'active', updated_at = :u
             WHERE start_time <= :now1 AND end_time >= :now2 AND status != 'active'"
        );
        $stmtOpen->execute([
            ':u'    => $now,
            ':now1' => $now,
            ':now2' => $now,
        ]);

        // 2. Pastikan jadwal yang dijadwalkan di masa depan (start_time > now) siap dibuka otomatis saat waktunya tiba
        $stmtScheduled = $pdo->prepare(
            "UPDATE elections
             SET status = 'active', updated_at = :u
             WHERE start_time > :now1 AND end_time > :now2 AND status = 'closed'"
        );
        $stmtScheduled->execute([
            ':u'    => $now,
            ':now1' => $now,
            ':now2' => $now,
        ]);

        // 3. Otomatis tutup periode pemilihan yang waktu selesainya sudah lewat (end_time < now)
        $stmtClose = $pdo->prepare(
            "UPDATE elections
             SET status = 'closed', updated_at = :u
             WHERE end_time < :now AND status != 'closed'"
        );
        $stmtClose->execute([
            ':u'   => $now,
            ':now' => $now,
        ]);
    }

    public static function getActiveOrLatest(?int $preferredId = null): ?array
    {
        self::syncRealTimeStatuses();
        $pdo = Database::connection();

        if ($preferredId !== null && $preferredId > 0) {
            $found = self::findById($preferredId);
            if ($found !== null) {
                return $found;
            }
        }

        $stmt = $pdo->query("SELECT * FROM elections WHERE status = 'active' ORDER BY id ASC LIMIT 1");
        $active = $stmt->fetch();
        if ($active) {
            return $active;
        }

        $stmtLatest = $pdo->query('SELECT * FROM elections ORDER BY id DESC LIMIT 1');
        $latest = $stmtLatest->fetch();
        return $latest ?: null;
    }

    /**
     * Mengambil seluruh periode pemilihan (baik yang sedang terbuka/'active' maupun yang telah ditutup/'closed')
     * untuk ditampilkan secara bersamaan dan real-time pada portal mahasiswa.
     */
    public static function getConcurrentForStudent(): array
    {
        self::syncRealTimeStatuses();
        $pdo = Database::connection();
        $stmt = $pdo->query(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) AS candidate_count,
                    (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS vote_count
             FROM elections e
             WHERE e.status IN ('active', 'closed') OR e.is_result_published = 1
             ORDER BY
                 CASE WHEN e.status = 'active' THEN 0 ELSE 1 END,
                 e.id ASC"
        );
        $rows = $stmt->fetchAll();

        if (!empty($rows)) {
            return $rows;
        }

        // Fallback apabila seluruh periode masih berstatus draft
        $stmtAll = $pdo->query(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) AS candidate_count,
                    (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS vote_count
             FROM elections e
             ORDER BY e.id ASC'
        );
        return $stmtAll->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        self::syncRealTimeStatuses();
        $pdo = Database::connection();
        $stmt = $pdo->prepare('SELECT * FROM elections WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getAll(): array
    {
        self::syncRealTimeStatuses();
        $pdo = Database::connection();
        $stmt = $pdo->query(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) AS candidate_count,
                    (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS vote_count
             FROM elections e
             ORDER BY
                 CASE WHEN e.status = \'active\' THEN 0 ELSE 1 END,
                 e.id DESC'
        );
        return $stmt->fetchAll();
    }

    /**
     * Memeriksa apakah periode sedang terbuka untuk voting (FR-08, AC-03, Bab 8)
     */
    public static function isVotingOpen(?array $election): bool
    {
        if ($election === null || ($election['status'] ?? '') !== 'active') {
            return false;
        }
        $now = time();
        $start = strtotime((string) $election['start_time']);
        $end = strtotime((string) $election['end_time']);
        if ($start === false || $end === false) {
            return false;
        }
        return $now >= $start && $now <= $end;
    }

    public static function getTimeState(?array $election): array
    {
        if ($election === null) {
            return ['code' => 'none', 'label' => 'Belum Ada Periode', 'can_vote' => false];
        }
        if ($election['status'] === 'draft') {
            return ['code' => 'draft', 'label' => 'Draft (Persiapan)', 'can_vote' => false];
        }
        if ($election['status'] === 'closed') {
            return ['code' => 'closed', 'label' => 'Pemilihan Telah Ditutup', 'can_vote' => false];
        }

        $now = time();
        $start = strtotime((string) $election['start_time']);
        $end = strtotime((string) $election['end_time']);

        if ($now < $start) {
            return ['code' => 'before_start', 'label' => 'Dijadwalkan Buka Otomatis', 'can_vote' => false];
        }
        if ($now > $end) {
            return ['code' => 'after_end', 'label' => 'Pemilihan Telah Ditutup', 'can_vote' => false];
        }
        return ['code' => 'open', 'label' => 'Sedang Berlangsung (Aktif)', 'can_vote' => true];
    }

    /**
     * Membuat periode pemilihan baru (Mendukung beberapa periode berstatus 'active' secara bersamaan)
     */
    public static function create(array $data): int
    {
        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            'INSERT INTO elections (title, organization_name, period_label, description, start_time, end_time, status, is_result_published, created_at, updated_at)
             VALUES (:title, :org, :period, :desc, :start, :end, :status, :pub, :created, :updated)'
        );
        $stmt->execute([
            ':title'   => trim((string) $data['title']),
            ':org'     => trim((string) $data['organization_name']),
            ':period'  => trim((string) $data['period_label']),
            ':desc'    => trim((string) ($data['description'] ?? '')),
            ':start'   => $data['start_time'],
            ':end'     => $data['end_time'],
            ':status'  => $data['status'] ?? 'draft',
            ':pub'     => !empty($data['is_result_published']) ? 1 : 0,
            ':created' => $now,
            ':updated' => $now,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Memperbarui periode pemilihan (Mendukung multi-periode aktif bersamaan)
     */
    public static function update(int $id, array $data): void
    {
        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare(
            'UPDATE elections
             SET title = :title,
                 organization_name = :org,
                 period_label = :period,
                 description = :desc,
                 start_time = :start,
                 end_time = :end,
                 status = :status,
                 is_result_published = :pub,
                 updated_at = :updated
             WHERE id = :id'
        );
        $stmt->execute([
            ':title'   => trim((string) $data['title']),
            ':org'     => trim((string) $data['organization_name']),
            ':period'  => trim((string) $data['period_label']),
            ':desc'    => trim((string) ($data['description'] ?? '')),
            ':start'   => $data['start_time'],
            ':end'     => $data['end_time'],
            ':status'  => $data['status'],
            ':pub'     => !empty($data['is_result_published']) ? 1 : 0,
            ':updated' => $now,
            ':id'      => $id,
        ]);
    }

    /**
     * Mengubah status periode pemilihan tanpa menutup periode aktif lainnya.
     * Apabila diaktifkan ('active') dan jadwal mulai masih di masa depan atau jadwal selesai sudah lewat,
     * sesuaikan jadwal secara otomatis agar pemilihan langsung terbuka untuk mahasiswa.
     */
    public static function updateStatus(int $id, string $status): void
    {
        $pdo = Database::connection();
        $nowTs = time();
        $now = date('Y-m-d H:i:s', $nowTs);

        if ($status === 'active') {
            $existing = self::findById($id);
            if ($existing !== null) {
                $startTs = strtotime((string) $existing['start_time']);
                $endTs   = strtotime((string) $existing['end_time']);

                $newStart = ($startTs === false || $startTs > $nowTs) ? $now : (string) $existing['start_time'];
                $newEnd   = ($endTs === false || $endTs <= $nowTs) ? date('Y-m-d H:i:s', $nowTs + (3 * 86400)) : (string) $existing['end_time'];

                $stmt = $pdo->prepare(
                    'UPDATE elections
                     SET status = :status, start_time = :start, end_time = :end, updated_at = :u
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':status' => $status,
                    ':start'  => $newStart,
                    ':end'    => $newEnd,
                    ':u'      => $now,
                    ':id'     => $id,
                ]);
                return;
            }
        }

        if ($status === 'closed') {
            $existing = self::findById($id);
            if ($existing !== null) {
                $endTs = strtotime((string) $existing['end_time']);
                $closedEnd = ($endTs !== false && $endTs >= $nowTs)
                    ? date('Y-m-d H:i:s', $nowTs - 1)
                    : (string) $existing['end_time'];

                $stmt = $pdo->prepare(
                    'UPDATE elections
                     SET status = :status, end_time = :end, updated_at = :u
                     WHERE id = :id'
                );
                $stmt->execute([
                    ':status' => 'closed',
                    ':end'    => $closedEnd,
                    ':u'      => $now,
                    ':id'     => $id,
                ]);
                return;
            }
        }

        $stmt = $pdo->prepare('UPDATE elections SET status = :status, updated_at = :u WHERE id = :id');
        $stmt->execute([
            ':status' => $status,
            ':u'      => $now,
            ':id'     => $id,
        ]);
    }

    /**
     * Mengubah status seluruh periode pemilihan sekaligus (misal: Aktifkan Semua / Tutup Semua).
     */
    public static function updateAllStatuses(string $status): int
    {
        if (!in_array($status, ['active', 'closed', 'draft'], true)) {
            return 0;
        }

        $pdo = Database::connection();
        $stmtIds = $pdo->query('SELECT id FROM elections');
        $ids = $stmtIds->fetchAll(\PDO::FETCH_COLUMN);
        if (empty($ids)) {
            return 0;
        }

        $pdo->beginTransaction();
        try {
            foreach ($ids as $id) {
                self::updateStatus((int) $id, $status);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return count($ids);
    }

    public static function togglePublish(int $id, bool $publish): void
    {
        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare('UPDATE elections SET is_result_published = :pub, updated_at = :u WHERE id = :id');
        $stmt->execute([
            ':pub' => $publish ? 1 : 0,
            ':u'   => $now,
            ':id'  => $id,
        ]);
    }

    /**
     * Menghapus satu periode pemilihan beserta seluruh kandidat, daftar hadir pemilih, dan kotak suara di dalamnya.
     */
    public static function deleteById(int $id): ?array
    {
        $pdo = Database::connection();
        $stmtInfo = $pdo->prepare(
            'SELECT e.*,
                    (SELECT COUNT(*) FROM candidates c WHERE c.election_id = e.id) AS candidate_count,
                    (SELECT COUNT(*) FROM votes v WHERE v.election_id = e.id) AS vote_count
             FROM elections e
             WHERE e.id = :id
             LIMIT 1'
        );
        $stmtInfo->execute([':id' => $id]);
        $existing = $stmtInfo->fetch();
        if (!$existing) {
            return null;
        }

        // Ambil foto kandidat untuk dibersihkan dari disk setelah transaksi berhasil
        $stmtPhotos = $pdo->prepare('SELECT photo_path FROM candidates WHERE election_id = :id AND photo_path IS NOT NULL');
        $stmtPhotos->execute([':id' => $id]);
        $photos = $stmtPhotos->fetchAll(\PDO::FETCH_COLUMN);

        $inTx = $pdo->inTransaction();
        if (!$inTx) {
            $pdo->beginTransaction();
        }

        try {
            $pdo->prepare('DELETE FROM voter_attendances WHERE election_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM votes WHERE election_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM candidates WHERE election_id = :id')->execute([':id' => $id]);
            $pdo->prepare('DELETE FROM elections WHERE id = :id')->execute([':id' => $id]);

            if (!$inTx) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if (!$inTx && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        foreach ($photos as $photoPath) {
            if (is_string($photoPath) && $photoPath !== '') {
                $fullPath = base_path('public/' . ltrim($photoPath, '/'));
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }
        }

        return $existing;
    }

    /**
     * Menghapus beberapa periode pemilihan sekaligus secara massal (Bulk Delete).
     */
    public static function deleteByIds(array $ids): int
    {
        $cleanIds = array_values(array_unique(array_filter(array_map('intval', $ids), static fn(int $v): bool => $v > 0)));
        if (empty($cleanIds)) {
            return 0;
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        $deletedCount = 0;

        try {
            foreach ($cleanIds as $id) {
                if (self::deleteById($id) !== null) {
                    $deletedCount++;
                }
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        return $deletedCount;
    }
}
