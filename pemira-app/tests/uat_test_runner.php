<?php

declare(strict_types=1);

/**
 * Automated Functional & User Acceptance Test (UAT) Runner
 * Menggunakan isolated SQLite mode agar tidak mengubah database produksi MySQL (pemira_db).
 */
putenv('DB_DRIVER=sqlite');

require_once dirname(__DIR__) . '/app/Core/Helpers.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Config\Database;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Student;
use App\Models\User;
use App\Models\Vote;
use App\Services\CsvImportService;
use App\Services\VotingService;

echo "======================================================================\n";
echo " PEMIRA APP - AUTOMATED UAT & FUNCTIONAL TEST RUNNER (PHP " . PHP_VERSION . ")\n";
echo "======================================================================\n";

$pdo = Database::connection();
echo "[PASS] 1. Inisialisasi Skema Database & Seed Awal\n";

// AC-01: Validasi Pemilih Aktif vs Tidak Aktif
$active = Student::findByNim('230101001');
$cuti   = Student::findByNim('220101007');
assert($active !== null && Student::isEligible($active) === true);
assert($cuti !== null && Student::isEligible($cuti) === false);
echo "[PASS] 2. AC-01: Validasi Hak Pilih Mahasiswa Aktif vs Cuti/Non-Aktif\n";

// AC-07: Import CSV Dataset Mahasiswa
$tmpCsv = sys_get_temp_dir() . '/pemira_uat_import.csv';
file_put_contents($tmpCsv, implode("\n", [
    'NIM,Nama,Jenjang,Program Studi,Periode masuk,Status Mahasiswa,Status Semester',
    '250101901,Ichsan Pratama,S1,Ilmu Hukum,20251,Aktif,Aktif',
    '250101902,Indah Permata,S1,Ilmu Hukum,20251,Aktif,Aktif',
    '250101903,Irfan Hakim,S1,Ilmu Hukum,20251,Aktif,Aktif',
]));
$report = CsvImportService::importFromFile($tmpCsv, 'pemira_uat_import.csv');
@unlink($tmpCsv);
assert($report['inserted_count'] === 3 && $report['failed_count'] === 0);
echo "[PASS] 3. AC-07: Import Dataset CSV Sesuai Standar Kolom Resmi\n";

// Uji Autocomplete Nama Mahasiswa (Huruf Awal)
$suggestions = Student::searchSuggestions('ich', 5);
assert(!empty($suggestions) && stripos($suggestions[0]['nama'], 'Ichsan') === 0);
echo "[PASS] 4. FR-14: Autocomplete Pencarian Nama Berdasarkan Huruf Awal ('ich' -> 'Ichsan Pratama')\n";

// AC-02 & AC-04: Voting Satu Kali & Secret Ballot
$election = Election::getActiveOrLatest();
$candidates = Candidate::getByElection((int) $election['id']);
$ichsan = Student::findByNim('250101901');
$receipt = VotingService::castVote((int) $ichsan['id'], (int) $election['id'], (int) $candidates[0]['id']);
assert(str_starts_with($receipt, 'PMR-'));

$doubleBlocked = false;
try {
    VotingService::castVote((int) $ichsan['id'], (int) $election['id'], (int) $candidates[0]['id']);
} catch (DomainException) {
    $doubleBlocked = true;
}
assert($doubleBlocked === true);
echo "[PASS] 5. AC-02 & AC-04: Secret Ballot Voting & Pencegahan Voting Ganda\n";

// Uji Hapus Mahasiswa Satu Per Satu & Secara Massal
$indah = Student::findByNim('250101902');
$irfan = Student::findByNim('250101903');
$deletedSingle = Student::deleteById((int) $indah['id']);
assert($deletedSingle !== null && Student::findByNim('250101902') === null);

$deletedBulkCount = Student::deleteByIds([(int) $ichsan['id'], (int) $irfan['id']]);
assert($deletedBulkCount === 2 && Student::findByNim('250101901') === null && Student::findByNim('250101903') === null);
echo "[PASS] 6. Fitur Hapus Mahasiswa Satu Per Satu & Hapus Massal (Bulk Delete)\n";

// 7. Uji Periode Pemilihan Berjalan Bersamaan (Concurrent Elections)
$secondElectionId = Election::create([
    'title'               => 'Pemilihan Ketua Himpunan Mahasiswa (HMP)',
    'organization_name'   => 'HMP Ilmu Hukum',
    'period_label'        => '2026/2027',
    'description'         => 'Pemilihan Ketua HMP Ilmu Hukum berjalan bersamaan dengan Pemilihan BEM.',
    'start_time'          => date('Y-m-d H:i:s', strtotime('-1 hour')),
    'end_time'            => date('Y-m-d H:i:s', strtotime('+3 days')),
    'status'              => 'active',
    'is_result_published' => false,
]);
$secondCandidateId = Candidate::create([
    'election_id'      => $secondElectionId,
    'candidate_number' => 1,
    'name'             => 'Calon Ketua HMP 01',
    'nim'              => '230101099',
    'jenjang'          => 'S1',
    'program_studi'    => 'Ilmu Hukum',
    'fakultas'         => 'Fakultas Hukum',
    'angkatan'         => '2023',
    'vision'           => 'Visi HMP',
    'mission'          => 'Misi HMP',
    'photo_path'       => null,
]);

// Pastikan pemilihan pertama masih berstatus active (tidak tertutup otomatis)
$firstElectionAfter = Election::findById((int) $election['id']);
assert($firstElectionAfter !== null && $firstElectionAfter['status'] === 'active');

// Uji mahasiswa memberikan suara di kedua pemilihan yang berjalan bersamaan
$pdo->exec("DELETE FROM voter_attendances WHERE student_id = " . (int) $active['id']);
$concurrentBefore = Election::getConcurrentForStudent((int) $active['id']);
$activeCount = count(array_filter($concurrentBefore, static fn(array $e): bool => $e['status'] === 'active'));
assert($activeCount >= 2);

$receiptBem = VotingService::castVote((int) $active['id'], (int) $election['id'], (int) $candidates[0]['id']);
assert(Vote::getAttendance((int) $election['id'], (int) $active['id']) !== null);
assert(Vote::getAttendance($secondElectionId, (int) $active['id']) === null);

$receiptHmp = VotingService::castVote((int) $active['id'], $secondElectionId, $secondCandidateId);
assert(Vote::getAttendance($secondElectionId, (int) $active['id']) !== null);
assert($receiptBem !== $receiptHmp);

// Uji ketika seluruh periode pemilihan ditutup: SEMUA periode (2 periode) tetap tampil di mahasiswa dengan status tertutup
Election::updateAllStatuses('closed');
$concurrentWhenClosed = Election::getConcurrentForStudent();
assert(count($concurrentWhenClosed) >= 2);
foreach ($concurrentWhenClosed as $closedEl) {
    $ts = Election::getTimeState($closedEl);
    assert($ts['can_vote'] === false && $ts['code'] === 'closed');
}

// Uji ketika seluruh periode pemilihan diaktifkan kembali: SEMUA periode tampil terbuka secara bersamaan
Election::updateAllStatuses('active');
$concurrentWhenOpen = Election::getConcurrentForStudent();
assert(count($concurrentWhenOpen) >= 2);
foreach ($concurrentWhenOpen as $openEl) {
    $ts = Election::getTimeState($openEl);
    assert($ts['can_vote'] === true && $ts['code'] === 'open');
}

// Uji Pembukaan Otomatis Sesuai Jadwal Real-Time (Auto-Open & Auto-Close)
Election::update($secondElectionId, [
    'title'               => 'Pemilihan Ketua Himpunan Mahasiswa (HMP)',
    'organization_name'   => 'HMP Ilmu Hukum',
    'period_label'        => '2026/2027',
    'description'         => 'Uji jadwal masa depan',
    'start_time'          => date('Y-m-d H:i:s', time() + 600),
    'end_time'            => date('Y-m-d H:i:s', time() + 3600),
    'status'              => 'closed',
    'is_result_published' => false,
]);
$scheduledEl = Election::findById($secondElectionId);
$scheduledTs = Election::getTimeState($scheduledEl);
assert($scheduledEl['status'] === 'active' && $scheduledTs['code'] === 'before_start' && $scheduledTs['can_vote'] === false);

// Simulasikan waktu mulai telah tiba (start_time <= now <= end_time) -> harus otomatis terbuka walaupun status awalnya closed
Election::update($secondElectionId, [
    'title'               => 'Pemilihan Ketua Himpunan Mahasiswa (HMP)',
    'organization_name'   => 'HMP Ilmu Hukum',
    'period_label'        => '2026/2027',
    'description'         => 'Uji jadwal tiba',
    'start_time'          => date('Y-m-d H:i:s', time() - 10),
    'end_time'            => date('Y-m-d H:i:s', time() + 3600),
    'status'              => 'closed',
    'is_result_published' => false,
]);
$autoOpenedEl = Election::findById($secondElectionId);
$autoOpenedTs = Election::getTimeState($autoOpenedEl);
assert($autoOpenedEl['status'] === 'active' && $autoOpenedTs['code'] === 'open' && $autoOpenedTs['can_vote'] === true);
echo "[PASS] 7. Skema Multi-Periode Pemilihan Real-Time (Otomatis Buka & Tutup Sesuai Jadwal)\n";

// 8. Uji Hapus Periode Pemilihan Satu Per Satu & Secara Massal (Beserta Cascade Kandidat & Suara)
$deletedElection = Election::deleteById($secondElectionId);
assert($deletedElection !== null && $deletedElection['title'] === 'Pemilihan Ketua Himpunan Mahasiswa (HMP)');
assert(Election::findById($secondElectionId) === null);
assert(Candidate::findById($secondCandidateId) === null);
assert(Vote::getAttendance($secondElectionId, (int) $active['id']) === null);

$bulkEl1 = Election::create([
    'title'             => 'Pemilihan Uji Hapus Massal 1',
    'organization_name' => 'UKM Olahraga',
    'period_label'      => '2026/2027',
    'start_time'        => date('Y-m-d H:i:s'),
    'end_time'          => date('Y-m-d H:i:s', strtotime('+1 day')),
    'status'            => 'draft',
]);
$bulkEl2 = Election::create([
    'title'             => 'Pemilihan Uji Hapus Massal 2',
    'organization_name' => 'UKM Seni',
    'period_label'      => '2026/2027',
    'start_time'        => date('Y-m-d H:i:s'),
    'end_time'          => date('Y-m-d H:i:s', strtotime('+1 day')),
    'status'            => 'closed',
]);
$bulkDeletedCount = Election::deleteByIds([$bulkEl1, $bulkEl2]);
assert($bulkDeletedCount === 2 && Election::findById($bulkEl1) === null && Election::findById($bulkEl2) === null);
echo "[PASS] 8. Fitur Hapus Periode Pemilihan (Satu Per Satu & Massal + Cascade Data)\n";

// Bersihkan data uji sementara pada pemilihan pertama
$pdo->exec("DELETE FROM voter_attendances WHERE student_id = " . (int) $active['id']);
$pdo->exec("DELETE FROM votes WHERE election_id = " . (int) $election['id']);
$pdo->exec("DELETE FROM import_logs WHERE filename = 'pemira_uat_import.csv'");

echo "======================================================================\n";
echo " SEMUA PENGUJIAN UAT & FUNGSIONAL LULUS 100%\n";
echo "======================================================================\n";
