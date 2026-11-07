<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Election;
use App\Models\Student;
use App\Models\Vote;
use App\Services\AuditService;

final class ResultController extends Controller
{
    public function index(): void
    {
        $elections = Election::getAll();
        $selectedId = isset($_GET['election_id']) ? (int) $_GET['election_id'] : null;
        $election = Election::getActiveOrLatest($selectedId);

        $electionId = $election ? (int) $election['id'] : null;
        $stats = Student::getSummaryStats($electionId);
        $results = $electionId ? Vote::getResultsByElection($electionId) : ['total_votes' => 0, 'candidates' => []];
        $prodiTurnout = $electionId ? Vote::getTurnoutByProdi($electionId) : [];

        $this->render('admin/results', [
            'title'        => 'Rekapitulasi & Laporan Hasil Pemilihan',
            'activeMenu'   => 'results',
            'elections'    => $elections,
            'election'     => $election,
            'stats'        => $stats,
            'results'      => $results,
            'prodiTurnout' => $prodiTurnout,
        ], 'layouts/admin');
    }

    public function exportCsv(): never
    {
        $selectedId = isset($_GET['election_id']) ? (int) $_GET['election_id'] : null;
        $election = Election::getActiveOrLatest($selectedId);

        $electionId = $election ? (int) $election['id'] : 0;
        $stats = Student::getSummaryStats($electionId);
        $results = $electionId ? Vote::getResultsByElection($electionId) : ['total_votes' => 0, 'candidates' => []];
        $prodiTurnout = $electionId ? Vote::getTurnoutByProdi($electionId) : [];

        AuditService::log(
            'EXPORT_RESULT_CSV',
            'elections',
            $electionId,
            'Mengekspor laporan rekapitulasi hasil pemilihan ke format CSV/Excel.'
        );

        $filename = 'Laporan_Rekapitulasi_PEMIRA_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        $out = fopen('php://output', 'wb');
        $csvWrite = static fn(array $row) => fputcsv($out, $row, ',', '"', '');

        $csvWrite(['LAPORAN REKAPITULASI HASIL PEMILIHAN RAYA MAHASISWA']);
        $csvWrite(['Nama Pemilihan', $election['title'] ?? '-']);
        $csvWrite(['Organisasi & Periode', ($election['organization_name'] ?? '-') . ' (' . ($election['period_label'] ?? '-') . ')']);
        $csvWrite(['Jadwal Mulai', $election['start_time'] ?? '-']);
        $csvWrite(['Jadwal Selesai', $election['end_time'] ?? '-']);
        $csvWrite(['Waktu Cetak Laporan', date('Y-m-d H:i:s') . ' WIB']);
        $csvWrite([]);

        $csvWrite(['RINGKASAN DATA PEMILIH']);
        $csvWrite(['Total Mahasiswa Memiliki Hak Pilih (Aktif)', $stats['total_eligible']]);
        $csvWrite(['Total Mahasiswa Sudah Memilih', $stats['total_voted']]);
        $csvWrite(['Total Mahasiswa Belum Memilih', $stats['total_not_voted']]);
        $csvWrite(['Total Suara Sah Masuk', $results['total_votes']]);
        $csvWrite(['Tingkat Partisipasi Pemilih (%)', $stats['turnout_percent'] . '%']);
        $csvWrite([]);

        $csvWrite(['PEROLEHAN SUARA PER CALON KETUA']);
        $csvWrite(['Nomor Urut', 'NIM Calon', 'Nama Calon Ketua', 'Jenjang', 'Program Studi', 'Jumlah Suara', 'Persentase (%)']);
        foreach ($results['candidates'] as $c) {
            $csvWrite([
                $c['candidate_number'],
                $c['nim'],
                $c['name'],
                $c['jenjang'],
                $c['program_studi'],
                $c['vote_count'],
                $c['percentage'] . '%',
            ]);
        }
        $csvWrite([]);

        $csvWrite(['REKAPITULASI PARTISIPASI PER PROGRAM STUDI']);
        $csvWrite(['Jenjang', 'Program Studi', 'Total Pemilih Aktif', 'Sudah Memilih', 'Belum Memilih', 'Partisipasi (%)']);
        foreach ($prodiTurnout as $p) {
            $csvWrite([
                $p['jenjang'],
                $p['program_studi'],
                $p['total_eligible'],
                $p['total_voted'],
                $p['total_not_voted'],
                $p['percentage'] . '%',
            ]);
        }

        fclose($out);
        exit;
    }

    public function printReport(): void
    {
        $selectedId = (int) ($_GET['election_id'] ?? 0);
        $election = $selectedId > 0
            ? Election::findById($selectedId)
            : Election::getActiveOrLatest();

        $electionId = $election ? (int) $election['id'] : 0;
        $stats = Student::getSummaryStats($electionId);
        $results = $electionId ? Vote::getResultsByElection($electionId) : ['total_votes' => 0, 'candidates' => []];
        $prodiTurnout = $electionId ? Vote::getTurnoutByProdi($electionId) : [];

        AuditService::log(
            'PRINT_RESULT_REPORT',
            'elections',
            $electionId,
            'Membuka dokumen cetak Berita Acara & Laporan Hasil Pemilihan (PDF/Print).'
        );

        $this->render('admin/print_report', [
            'title'        => 'Berita Acara & Rekapitulasi Hasil Pemilihan',
            'election'     => $election,
            'stats'        => $stats,
            'results'      => $results,
            'prodiTurnout' => $prodiTurnout,
        ], null);
    }
}
