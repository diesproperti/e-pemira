<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Config\Database;
use App\Core\Controller;
use App\Core\Session;
use App\Models\AuditLog;
use App\Models\Election;
use App\Models\Student;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CsvImportService;
use RuntimeException;

final class VoterController extends Controller
{
    public function index(): void
    {
        $requestedElectionId = isset($_GET['election_id']) ? (int) $_GET['election_id'] : null;
        $election = Election::getActiveOrLatest($requestedElectionId);
        $electionId = $election ? (int) $election['id'] : null;
        $allElections = Election::getAll();

        $filters = [
            'election_id'      => $electionId,
            'q'                => trim((string) ($_GET['q'] ?? '')),
            'alpha'            => strtoupper(trim((string) ($_GET['alpha'] ?? ''))),
            'program_studi'    => trim((string) ($_GET['program_studi'] ?? '')),
            'jenjang'          => trim((string) ($_GET['jenjang'] ?? '')),
            'status_mahasiswa' => trim((string) ($_GET['status_mahasiswa'] ?? '')),
            'voting_status'    => trim((string) ($_GET['voting_status'] ?? '')),
        ];

        $students = Student::getAllWithVotingStatus($electionId, $filters);
        $allDirectory = Student::getAllWithVotingStatus($electionId, []);
        $stats = Student::getSummaryStats($electionId);
        $prodiList = Student::getDistinctProdi();
        $jenjangList = Student::getDistinctJenjang();
        $importLogs = AuditLog::getImportLogs(8);
        $lastImportReport = Session::getFlashData('import_report');

        $this->render('admin/voters', [
            'title'            => 'Dataset Mahasiswa & Daftar Pemilih',
            'activeMenu'       => 'voters',
            'election'         => $election,
            'allElections'     => $allElections,
            'students'         => $students,
            'allDirectory'     => $allDirectory,
            'stats'            => $stats,
            'filters'          => $filters,
            'prodiList'        => $prodiList,
            'jenjangList'      => $jenjangList,
            'importLogs'       => $importLogs,
            'lastImportReport' => $lastImportReport,
        ], 'layouts/admin');
    }

    public function suggest(): never
    {
        $q = trim((string) ($_GET['q'] ?? ''));
        $items = Student::searchSuggestions($q, 10);
        $this->json(['items' => $items]);
    }

    public function exportVotersCsv(): never
    {
        $requestedElectionId = isset($_GET['election_id']) ? (int) $_GET['election_id'] : null;
        $election = Election::getActiveOrLatest($requestedElectionId);
        $electionId = $election ? (int) $election['id'] : null;

        $filters = [
            'q'                => trim((string) ($_GET['q'] ?? '')),
            'alpha'            => strtoupper(trim((string) ($_GET['alpha'] ?? ''))),
            'program_studi'    => trim((string) ($_GET['program_studi'] ?? '')),
            'jenjang'          => trim((string) ($_GET['jenjang'] ?? '')),
            'status_mahasiswa' => trim((string) ($_GET['status_mahasiswa'] ?? '')),
            'voting_status'    => trim((string) ($_GET['voting_status'] ?? '')),
        ];

        $students = Student::getAllWithVotingStatus($electionId, $filters);

        AuditService::log(
            'EXPORT_VOTERS_CSV',
            'students',
            null,
            'Mengekspor daftar mahasiswa & status penggunaan hak pilih (' . count($students) . ' baris) ke CSV.'
        );

        $filename = 'Daftar_Pemilih_PEMIRA_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        $out = fopen('php://output', 'wb');
        $csvWrite = static fn(array $row) => fputcsv($out, $row, ',', '"', '');

        $csvWrite(['NIM', 'Nama', 'Jenjang', 'Program Studi', 'Periode masuk', 'Status Mahasiswa', 'Status Semester', 'Hak Pilih', 'Status Voting', 'Waktu Memilih', 'Kode Bukti Partisipasi']);

        foreach ($students as $s) {
            $eligible = Student::isEligible($s);
            $hasVoted = !empty($s['attendance_id']);
            $csvWrite([
                $s['nim'],
                $s['nama'],
                $s['jenjang'],
                $s['program_studi'],
                $s['periode_masuk'],
                $s['status_mahasiswa'],
                $s['status_semester'],
                $eligible ? 'Berhak Memilih' : 'Tidak Berhak',
                $hasVoted ? 'Sudah Memilih' : 'Belum Memilih',
                $s['voted_at'] ?? '-',
                $s['receipt_code'] ?? '-',
            ]);
        }

        fclose($out);
        exit;
    }

    public function importCsv(): void
    {
        if (empty($_FILES['csv_file']) || ($_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Harap pilih file CSV dataset mahasiswa terlebih dahulu.');
            $this->redirect('/admin/voters');
        }

        $file = $_FILES['csv_file'];
        $maxSize = 5 * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxSize) {
            Session::flash('error', 'Ukuran file melebihi batas maksimum 5 MB.');
            $this->redirect('/admin/voters');
        }

        try {
            $report = CsvImportService::importFromFile((string) $file['tmp_name'], (string) $file['name']);
            Session::setFlashData('import_report', $report);

            $msg = sprintf(
                'Proses Import Selesai! Total %d baris diproses: %d mahasiswa baru ditambahkan, %d diperbarui, %d gagal validasi.',
                $report['total_rows'],
                $report['inserted_count'],
                $report['updated_count'],
                $report['failed_count']
            );

            if ($report['failed_count'] > 0 && ($report['inserted_count'] + $report['updated_count']) === 0) {
                Session::flash('error', $msg);
            } elseif ($report['failed_count'] > 0) {
                Session::flash('warning', $msg);
            } else {
                Session::flash('success', $msg);
            }
        } catch (RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        $this->redirect('/admin/voters');
    }

    public function downloadTemplate(): never
    {
        $templatePath = base_path('database/template_mahasiswa.csv');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="template_dataset_mahasiswa.csv"');

        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        if (is_file($templatePath)) {
            readfile($templatePath);
        } else {
            echo "NIM,Nama,Jenjang,Program Studi,Periode masuk,Status Mahasiswa,Status Semester\n";
            echo "230101001,Budi Santoso,S1,Teknik Informatika,2023/2024 Ganjil,Aktif,Aktif\n";
        }
        exit;
    }

    public function storeManual(): void
    {
        $nim       = trim((string) ($_POST['nim'] ?? ''));
        $nama      = trim((string) ($_POST['nama'] ?? ''));
        $jenjang   = strtoupper(trim((string) ($_POST['jenjang'] ?? 'S1')));
        $prodi     = trim((string) ($_POST['program_studi'] ?? ''));
        $periode   = trim((string) ($_POST['periode_masuk'] ?? ''));
        $statusMhs = trim((string) ($_POST['status_mahasiswa'] ?? 'Aktif'));
        $statusSmt = trim((string) ($_POST['status_semester'] ?? 'Aktif'));

        if ($nim === '' || $nama === '' || $jenjang === '' || $prodi === '' || $periode === '') {
            Session::flash('error', 'Semua kolom data mahasiswa wajib diisi.');
            $this->redirect('/admin/voters');
        }

        $pdo = Database::connection();
        $now = date('Y-m-d H:i:s');
        $isActive = (strcasecmp($statusMhs, 'Aktif') === 0 && strcasecmp($statusSmt, 'Aktif') === 0) ? 1 : 0;

        try {
            $pdo->beginTransaction();

            $existing = Student::findByNim($nim);
            if ($existing) {
                $stmtU = $pdo->prepare('UPDATE users SET name = :name, is_active = :act, updated_at = :u WHERE id = :id');
                $stmtU->execute([
                    ':name' => $nama,
                    ':act'  => $isActive,
                    ':u'    => $now,
                    ':id'   => (int) $existing['user_id'],
                ]);

                $stmtS = $pdo->prepare(
                    'UPDATE students
                     SET nama = :nama, jenjang = :jenjang, program_studi = :prodi, periode_masuk = :periode,
                         status_mahasiswa = :smhs, status_semester = :ssmt, updated_at = :u
                     WHERE id = :id'
                );
                $stmtS->execute([
                    ':nama'    => $nama,
                    ':jenjang' => $jenjang,
                    ':prodi'   => $prodi,
                    ':periode' => $periode,
                    ':smhs'    => $statusMhs,
                    ':ssmt'    => $statusSmt,
                    ':u'       => $now,
                    ':id'      => (int) $existing['id'],
                ]);

                AuditService::log(
                    'UPDATE_STUDENT',
                    'students',
                    (int) $existing['id'],
                    "Admin memperbarui data mahasiswa NIM {$nim} ({$nama}) — Status: {$statusMhs} / Semester: {$statusSmt}."
                );
                Session::flash('success', "Data mahasiswa {$nama} (NIM: {$nim}) berhasil diperbarui.");
            } else {
                $stmtU = $pdo->prepare(
                    'INSERT INTO users (username, name, password_hash, role, is_active, must_change_password, created_at, updated_at)
                     VALUES (:u, :name, :hash, :role, :act, 1, :c, :up)'
                );
                $stmtU->execute([
                    ':u'    => $nim,
                    ':name' => $nama,
                    ':hash' => password_hash($nim, PASSWORD_BCRYPT),
                    ':role' => 'student',
                    ':act'  => $isActive,
                    ':c'    => $now,
                    ':up'   => $now,
                ]);
                $uid = (int) $pdo->lastInsertId();

                $stmtS = $pdo->prepare(
                    'INSERT INTO students (user_id, nim, nama, jenjang, program_studi, periode_masuk, status_mahasiswa, status_semester, created_at, updated_at)
                     VALUES (:uid, :nim, :nama, :jenjang, :prodi, :periode, :smhs, :ssmt, :c, :up)'
                );
                $stmtS->execute([
                    ':uid'     => $uid,
                    ':nim'     => $nim,
                    ':nama'    => $nama,
                    ':jenjang' => $jenjang,
                    ':prodi'   => $prodi,
                    ':periode' => $periode,
                    ':smhs'    => $statusMhs,
                    ':ssmt'    => $statusSmt,
                    ':c'       => $now,
                    ':up'      => $now,
                ]);
                $sid = (int) $pdo->lastInsertId();

                AuditService::log(
                    'CREATE_STUDENT',
                    'students',
                    $sid,
                    "Admin menambahkan mahasiswa baru NIM {$nim} ({$nama}) dengan password awal = NIM."
                );
                Session::flash('success', "Mahasiswa {$nama} (NIM: {$nim}) berhasil ditambahkan. Password awal: {$nim}.");
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Session::flash('error', 'Gagal menyimpan data mahasiswa: ' . $e->getMessage());
        }

        $this->redirect('/admin/voters');
    }

    public function resetPassword(): void
    {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        $student = Student::findById($studentId);

        if ($student === null) {
            Session::flash('error', 'Data mahasiswa tidak ditemukan.');
            $this->redirect('/admin/voters');
        }

        User::updatePassword((int) $student['user_id'], (string) $student['nim'], true);

        AuditService::log(
            'RESET_STUDENT_PASSWORD',
            'students',
            $studentId,
            "Admin mereset password mahasiswa NIM {$student['nim']} ({$student['nama']}) kembali ke NIM default."
        );

        Session::flash(
            'success',
            "Password untuk {$student['nama']} (NIM: {$student['nim']}) berhasil di-reset kembali ke NIM ({$student['nim']}). Mahasiswa akan diminta mengganti password saat login."
        );
        $this->redirect('/admin/voters');
    }

    public function delete(): void
    {
        $studentId = (int) ($_POST['student_id'] ?? 0);
        if ($studentId <= 0) {
            Session::flash('error', 'ID mahasiswa tidak valid.');
            $this->redirect('/admin/voters');
        }

        try {
            $deleted = Student::deleteById($studentId);
            if ($deleted === null) {
                Session::flash('error', 'Data mahasiswa tidak ditemukan.');
            } else {
                AuditService::log(
                    'DELETE_STUDENT',
                    'students',
                    $studentId,
                    "Admin menghapus data mahasiswa NIM {$deleted['nim']} ({$deleted['nama']} - {$deleted['jenjang']} {$deleted['program_studi']})."
                );
                Session::flash('success', "Data mahasiswa {$deleted['nama']} (NIM: {$deleted['nim']}) berhasil dihapus.");
            }
        } catch (\Throwable $e) {
            Session::flash('error', 'Gagal menghapus data mahasiswa: ' . $e->getMessage());
        }

        $this->redirect('/admin/voters');
    }

    public function bulkDelete(): void
    {
        $mode = trim((string) ($_POST['delete_mode'] ?? 'selected'));

        try {
            if ($mode === 'all') {
                $count = Student::deleteAll();
                AuditService::log(
                    'DELETE_ALL_STUDENTS',
                    'students',
                    null,
                    "Admin mengosongkan/menghapus seluruh dataset mahasiswa ({$count} data mahasiswa dihapus)."
                );
                Session::flash('success', "Seluruh dataset mahasiswa ({$count} data) berhasil dihapus.");
                $this->redirect('/admin/voters');
            }

            $rawIds = $_POST['student_ids'] ?? [];
            if (!is_array($rawIds) || empty($rawIds)) {
                Session::flash('warning', 'Pilih minimal satu mahasiswa yang ingin dihapus terlebih dahulu.');
                $this->redirect('/admin/voters');
            }

            $count = Student::deleteByIds($rawIds);
            AuditService::log(
                'BULK_DELETE_STUDENTS',
                'students',
                null,
                "Admin menghapus {$count} data mahasiswa secara massal."
            );

            Session::flash('success', "Berhasil menghapus {$count} data mahasiswa yang dipilih.");
        } catch (\Throwable $e) {
            Session::flash('error', 'Gagal menghapus data mahasiswa secara massal: ' . $e->getMessage());
        }

        $this->redirect('/admin/voters');
    }
}

