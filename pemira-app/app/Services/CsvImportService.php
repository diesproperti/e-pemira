<?php

declare(strict_types=1);

namespace App\Services;

use App\Config\Database;
use App\Core\Session;
use RuntimeException;

final class CsvImportService
{
    private const REQUIRED_COLUMNS = [
        'nim'              => 'NIM',
        'nama'             => 'Nama',
        'jenjang'          => 'Jenjang',
        'program_studi'    => 'Program Studi',
        'periode_masuk'    => 'Periode masuk',
        'status_mahasiswa' => 'Status Mahasiswa',
        'status_semester'  => 'Status Semester',
    ];

    public static function importFromFile(string $filePath, string $originalFilename): array
    {
        if (!is_file($filePath)) {
            throw new RuntimeException('File dataset tidak ditemukan.');
        }

        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            throw new RuntimeException('Format file tidak didukung. Harap unggah file dengan ekstensi .csv (CSV UTF-8).');
        }

        $rawContent = @file_get_contents($filePath);
        if ($rawContent === false) {
            throw new RuntimeException('File dataset tidak dapat dibaca.');
        }
        $content = $rawContent;
        // Hilangkan UTF-8 BOM jika diekspor dari Microsoft Excel
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];
        if (count($lines) < 2) {
            throw new RuntimeException('File CSV kosong atau tidak memiliki baris data mahasiswa.');
        }

        $headerLine = $lines[0];
        $delimiter = self::detectDelimiter($headerLine);
        $rawHeaders = str_getcsv($headerLine, $delimiter, '"', '');

        $columnMap = self::mapHeaders($rawHeaders);

        $missingColumns = [];
        foreach (self::REQUIRED_COLUMNS as $key => $label) {
            if (!array_key_exists($key, $columnMap)) {
                $missingColumns[] = $label;
            }
        }

        if (!empty($missingColumns)) {
            throw new RuntimeException(
                'Header kolom CSV tidak sesuai standar. Kolom yang belum ditemukan: ' . implode(', ', $missingColumns) .
                '. Pastikan urutan/nama kolom adalah: NIM, Nama, Jenjang, Program Studi, Periode masuk, Status Mahasiswa, Status Semester.'
            );
        }

        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');

        $totalRows = 0;
        $insertedCount = 0;
        $updatedCount = 0;
        $failedCount = 0;
        $errors = [];
        $seenNimsInFile = [];

        $stmtFindStudent = $pdo->prepare('SELECT id, user_id FROM students WHERE nim = :nim LIMIT 1');
        $stmtInsertUser = $pdo->prepare(
            'INSERT INTO users (username, name, password_hash, role, is_active, must_change_password, created_at, updated_at)
             VALUES (:username, :name, :hash, :role, :active, :must_change, :created, :updated)'
        );
        $stmtInsertStudent = $pdo->prepare(
            'INSERT INTO students (user_id, nim, nama, jenjang, program_studi, periode_masuk, status_mahasiswa, status_semester, created_at, updated_at)
             VALUES (:uid, :nim, :nama, :jenjang, :prodi, :periode, :status_mhs, :status_smt, :created, :updated)'
        );
        $stmtUpdateUser = $pdo->prepare(
            'UPDATE users SET name = :name, is_active = :active, updated_at = :updated WHERE id = :id'
        );
        $stmtUpdateStudent = $pdo->prepare(
            'UPDATE students
             SET nama = :nama,
                 jenjang = :jenjang,
                 program_studi = :prodi,
                 periode_masuk = :periode,
                 status_mahasiswa = :status_mhs,
                 status_semester = :status_smt,
                 updated_at = :updated
             WHERE id = :id'
        );

        for ($i = 1, $count = count($lines); $i < $count; $i++) {
            $line = trim($lines[$i]);
            if ($line === '') {
                continue;
            }

            $totalRows++;
            $rowNumber = $i + 1;
            $cols = str_getcsv($line, $delimiter, '"', '');

            $nim       = trim((string) ($cols[$columnMap['nim']] ?? ''));
            $nama      = trim((string) ($cols[$columnMap['nama']] ?? ''));
            $jenjang   = trim((string) ($cols[$columnMap['jenjang']] ?? ''));
            $prodi     = trim((string) ($cols[$columnMap['program_studi']] ?? ''));
            $periode   = trim((string) ($cols[$columnMap['periode_masuk']] ?? ''));
            $statusMhs = trim((string) ($cols[$columnMap['status_mahasiswa']] ?? ''));
            $statusSmt = trim((string) ($cols[$columnMap['status_semester']] ?? ''));

            // 1. Validasi kolom wajib kosong
            if ($nim === '' || $nama === '' || $jenjang === '' || $prodi === '' || $periode === '' || $statusMhs === '' || $statusSmt === '') {
                $failedCount++;
                $errors[] = [
                    'row'    => $rowNumber,
                    'nim'    => $nim !== '' ? $nim : '(Kosong)',
                    'nama'   => $nama !== '' ? $nama : '-',
                    'reason' => 'Terdapat kolom wajib yang kosong (NIM, Nama, Jenjang, Program Studi, Periode masuk, Status Mahasiswa, atau Status Semester).',
                ];
                continue;
            }

            // 2. Validasi format NIM
            if (!preg_match('/^[A-Za-z0-9.\-_]{4,30}$/', $nim)) {
                $failedCount++;
                $errors[] = [
                    'row'    => $rowNumber,
                    'nim'    => $nim,
                    'nama'   => $nama,
                    'reason' => 'Format NIM tidak valid (4-30 karakter alfanumerik).',
                ];
                continue;
            }

            // 3. Pemeriksaan NIM duplikat di dalam file CSV yang sama
            $nimKey = strtoupper($nim);
            if (isset($seenNimsInFile[$nimKey])) {
                $failedCount++;
                $errors[] = [
                    'row'    => $rowNumber,
                    'nim'    => $nim,
                    'nama'   => $nama,
                    'reason' => 'NIM duplikat di dalam file CSV (sudah muncul pada baris ke-' . $seenNimsInFile[$nimKey] . ').',
                ];
                continue;
            }
            $seenNimsInFile[$nimKey] = $rowNumber;

            // 4. Validasi nilai Status Mahasiswa & Status Semester
            $normalizedMhs = self::normalizeStatusMahasiswa($statusMhs);
            if ($normalizedMhs === null) {
                $failedCount++;
                $errors[] = [
                    'row'    => $rowNumber,
                    'nim'    => $nim,
                    'nama'   => $nama,
                    'reason' => "Nilai Status Mahasiswa '{$statusMhs}' tidak dikenali (Gunakan: Aktif, Cuti, Non-Aktif, atau Lulus).",
                ];
                continue;
            }

            $normalizedSmt = self::normalizeStatusSemester($statusSmt);
            if ($normalizedSmt === null) {
                $failedCount++;
                $errors[] = [
                    'row'    => $rowNumber,
                    'nim'    => $nim,
                    'nama'   => $nama,
                    'reason' => "Nilai Status Semester '{$statusSmt}' tidak dikenali (Gunakan: Aktif, Cuti, atau Non-Aktif).",
                ];
                continue;
            }

            $isActiveVoter = ($normalizedMhs === 'Aktif' && $normalizedSmt === 'Aktif') ? 1 : 0;

            try {
                $pdo->beginTransaction();

                $stmtFindStudent->execute([':nim' => $nim]);
                $existing = $stmtFindStudent->fetch();

                if ($existing) {
                    $stmtUpdateUser->execute([
                        ':name'    => $nama,
                        ':active'  => $isActiveVoter,
                        ':updated' => $now,
                        ':id'      => (int) $existing['user_id'],
                    ]);
                    $stmtUpdateStudent->execute([
                        ':nama'       => $nama,
                        ':jenjang'    => strtoupper($jenjang),
                        ':prodi'      => $prodi,
                        ':periode'    => $periode,
                        ':status_mhs' => $normalizedMhs,
                        ':status_smt' => $normalizedSmt,
                        ':updated'    => $now,
                        ':id'         => (int) $existing['id'],
                    ]);
                    $updatedCount++;
                } else {
                    $stmtInsertUser->execute([
                        ':username'    => $nim,
                        ':name'        => $nama,
                        ':hash'        => password_hash($nim, PASSWORD_BCRYPT),
                        ':role'        => 'student',
                        ':active'      => $isActiveVoter,
                        ':must_change' => 1,
                        ':created'     => $now,
                        ':updated'     => $now,
                    ]);
                    $newUserId = (int) $pdo->lastInsertId();

                    $stmtInsertStudent->execute([
                        ':uid'        => $newUserId,
                        ':nim'        => $nim,
                        ':nama'       => $nama,
                        ':jenjang'    => strtoupper($jenjang),
                        ':prodi'      => $prodi,
                        ':periode'    => $periode,
                        ':status_mhs' => $normalizedMhs,
                        ':status_smt' => $normalizedSmt,
                        ':created'    => $now,
                        ':updated'    => $now,
                    ]);
                    $insertedCount++;
                }

                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $failedCount++;
                $errors[] = [
                    'row'    => $rowNumber,
                    'nim'    => $nim,
                    'nama'   => $nama,
                    'reason' => 'Gagal menyimpan ke database: ' . $e->getMessage(),
                ];
            }
        }

        // Catat ke import_logs
        $adminId = (int) (Session::user()['id'] ?? 1);
        $stmtLog = $pdo->prepare(
            'INSERT INTO import_logs (user_id, filename, total_rows, inserted_count, updated_count, failed_count, error_details, created_at)
             VALUES (:uid, :fname, :total, :ins, :upd, :fail, :err, :created)'
        );
        $stmtLog->execute([
            ':uid'     => $adminId,
            ':fname'   => $originalFilename,
            ':total'   => $totalRows,
            ':ins'     => $insertedCount,
            ':upd'     => $updatedCount,
            ':fail'    => $failedCount,
            ':err'     => json_encode($errors, JSON_UNESCAPED_UNICODE),
            ':created' => $now,
        ]);

        AuditService::log(
            'IMPORT_DATASET',
            'students',
            null,
            sprintf(
                'Import dataset CSV (%s): Total %d baris (%d baru, %d diperbarui, %d gagal).',
                $originalFilename,
                $totalRows,
                $insertedCount,
                $updatedCount,
                $failedCount
            )
        );

        return [
            'filename'       => $originalFilename,
            'total_rows'     => $totalRows,
            'inserted_count' => $insertedCount,
            'updated_count'  => $updatedCount,
            'failed_count'   => $failedCount,
            'errors'         => $errors,
        ];
    }

    private static function detectDelimiter(string $headerLine): string
    {
        $semicolons = substr_count($headerLine, ';');
        $commas = substr_count($headerLine, ',');
        $tabs = substr_count($headerLine, "\t");

        if ($semicolons > $commas && $semicolons >= $tabs) {
            return ';';
        }
        if ($tabs > $commas && $tabs > $semicolons) {
            return "\t";
        }
        return ',';
    }

    /**
     * @param list<string|null> $rawHeaders
     * @return array<string, int>
     */
    private static function mapHeaders(array $rawHeaders): array
    {
        $map = [];
        foreach ($rawHeaders as $idx => $raw) {
            $norm = strtolower(trim((string) $raw));
            $norm = preg_replace('/[\s\-_]+/', '_', $norm) ?? $norm;

            match ($norm) {
                'nim', 'nomor_induk_mahasiswa'                   => $map['nim'] = $idx,
                'nama', 'nama_mahasiswa', 'nama_lengkap'         => $map['nama'] = $idx,
                'jenjang', 'strata', 'jenjang_pendidikan'        => $map['jenjang'] = $idx,
                'program_studi', 'prodi', 'jurusan'              => $map['program_studi'] = $idx,
                'periode_masuk', 'angkatan', 'tahun_masuk'       => $map['periode_masuk'] = $idx,
                'status_mahasiswa', 'status'                     => $map['status_mahasiswa'] = $idx,
                'status_semester', 'status_smt', 'semester_aktif' => $map['status_semester'] = $idx,
                default                                          => null,
            };
        }
        return $map;
    }

    private static function normalizeStatusMahasiswa(string $val): ?string
    {
        $clean = strtolower(trim($val));
        return match ($clean) {
            'aktif', 'active', 'a'                                => 'Aktif',
            'cuti', 'c'                                           => 'Cuti',
            'non-aktif', 'nonaktif', 'non aktif', 'tidak aktif', 'n' => 'Non-Aktif',
            'lulus', 'alumni', 'l'                                => 'Lulus',
            'drop out', 'do', 'keluar', 'mengundurkan diri'       => 'Drop Out',
            default                                               => null,
        };
    }

    private static function normalizeStatusSemester(string $val): ?string
    {
        $clean = strtolower(trim($val));
        return match ($clean) {
            'aktif', 'active', 'a'                                => 'Aktif',
            'cuti', 'c'                                           => 'Cuti',
            'non-aktif', 'nonaktif', 'non aktif', 'tidak aktif', 'n' => 'Non-Aktif',
            default                                               => null,
        };
    }
}
