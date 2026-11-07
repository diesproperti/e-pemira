<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Election;
use App\Services\AuditService;

final class ElectionController extends Controller
{
    public function index(): void
    {
        $elections = Election::getAll();
        $editId = (int) ($_GET['edit_id'] ?? 0);
        $editElection = $editId > 0 ? Election::findById($editId) : null;

        $this->render('admin/elections', [
            'title'        => 'Kelola Periode Pemilihan',
            'activeMenu'   => 'elections',
            'elections'    => $elections,
            'editElection' => $editElection,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $title       = trim((string) ($_POST['title'] ?? ''));
        $org         = trim((string) ($_POST['organization_name'] ?? 'BEM Universitas'));
        $period      = trim((string) ($_POST['period_label'] ?? '2026/2027'));
        $description = trim((string) ($_POST['description'] ?? ''));
        $startRaw    = trim((string) ($_POST['start_time'] ?? ''));
        $endRaw      = trim((string) ($_POST['end_time'] ?? ''));
        $status      = trim((string) ($_POST['status'] ?? 'draft'));
        $published   = !empty($_POST['is_result_published']);

        if ($title === '' || $startRaw === '' || $endRaw === '') {
            Session::flash('error', 'Nama pemilihan, waktu mulai, dan waktu selesai wajib diisi.');
            $this->redirect('/admin/elections');
        }

        $startTs = strtotime($startRaw);
        $endTs   = strtotime($endRaw);
        if ($startTs === false || $endTs === false || $endTs <= $startTs) {
            Session::flash('error', 'Jadwal waktu selesai harus lebih akhir dibandingkan waktu mulai.');
            $this->redirect('/admin/elections');
        }

        // Sinkronkan status otomatis berdasarkan jadwal waktu
        if ($endTs <= time()) {
            $status = 'closed';
        } elseif ($status !== 'draft') {
            $status = 'active';
        }

        $id = Election::create([
            'title'               => $title,
            'organization_name'   => $org,
            'period_label'        => $period,
            'description'         => $description,
            'start_time'          => date('Y-m-d H:i:s', $startTs),
            'end_time'            => date('Y-m-d H:i:s', $endTs),
            'status'              => $status,
            'is_result_published' => $published,
        ]);

        AuditService::log(
            'CREATE_ELECTION',
            'elections',
            $id,
            "Membuat periode pemilihan baru: '{$title}' ({$period}) dengan status {$status}."
        );

        Session::flash('success', "Periode pemilihan '{$title}' berhasil dibuat.");
        $this->redirect('/admin/elections');
    }

    public function update(): void
    {
        $id          = (int) ($_POST['id'] ?? 0);
        $title       = trim((string) ($_POST['title'] ?? ''));
        $org         = trim((string) ($_POST['organization_name'] ?? 'BEM Universitas'));
        $period      = trim((string) ($_POST['period_label'] ?? '2026/2027'));
        $description = trim((string) ($_POST['description'] ?? ''));
        $startRaw    = trim((string) ($_POST['start_time'] ?? ''));
        $endRaw      = trim((string) ($_POST['end_time'] ?? ''));
        $status      = trim((string) ($_POST['status'] ?? 'draft'));
        $published   = !empty($_POST['is_result_published']);

        $existing = Election::findById($id);
        if ($existing === null) {
            Session::flash('error', 'Periode pemilihan tidak ditemukan.');
            $this->redirect('/admin/elections');
        }

        $startTs = strtotime($startRaw);
        $endTs   = strtotime($endRaw);
        if ($startTs === false || $endTs === false || $endTs <= $startTs) {
            Session::flash('error', 'Jadwal waktu selesai harus lebih akhir dibandingkan waktu mulai.');
            $this->redirect('/admin/elections?edit_id=' . $id);
        }

        // Apabila jadwal selesai berada di masa depan, otomatis aktifkan jadwal agar langsung buka sesuai jadwal
        if ($endTs <= time()) {
            $status = 'closed';
        } elseif ($status !== 'draft') {
            $status = 'active';
        }

        Election::update($id, [
            'title'               => $title,
            'organization_name'   => $org,
            'period_label'        => $period,
            'description'         => $description,
            'start_time'          => date('Y-m-d H:i:s', $startTs),
            'end_time'            => date('Y-m-d H:i:s', $endTs),
            'status'              => $status,
            'is_result_published' => $published,
        ]);

        AuditService::log(
            'UPDATE_ELECTION',
            'elections',
            $id,
            "Memperbarui konfigurasi periode pemilihan '{$title}' (Status: {$status}, Jadwal: " . date('Y-m-d H:i', $startTs) . ' s.d. ' . date('Y-m-d H:i', $endTs) . ').'
        );

        Session::flash('success', "Periode pemilihan '{$title}' berhasil diperbarui.");
        $this->redirect('/admin/elections');
    }

    public function changeStatus(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $status = trim((string) ($_POST['status'] ?? ''));

        $existing = Election::findById($id);
        if ($existing === null || !in_array($status, ['draft', 'active', 'closed'], true)) {
            Session::flash('error', 'Permintaan perubahan status tidak valid.');
            $this->redirect('/admin/elections');
        }

        Election::updateStatus($id, $status);

        AuditService::log(
            'CHANGE_ELECTION_STATUS',
            'elections',
            $id,
            "Mengubah status periode '{$existing['title']}' dari '{$existing['status']}' menjadi '{$status}'."
        );

        Session::flash('success', "Status periode pemilihan '{$existing['title']}' berhasil diubah menjadi " . strtoupper($status) . '.');
        $this->redirect('/admin/elections');
    }

    public function changeAllStatus(): void
    {
        $status = trim((string) ($_POST['status'] ?? ''));
        if (!in_array($status, ['active', 'closed'], true)) {
            Session::flash('error', 'Status periode pemilihan tidak valid.');
            $this->redirect('/admin/elections');
        }

        $count = Election::updateAllStatuses($status);
        $label = $status === 'active' ? 'DIAKTIFKAN (DIBUKA)' : 'DITUTUP';

        AuditService::log(
            'CHANGE_ALL_ELECTION_STATUS',
            'elections',
            null,
            "Mengubah status seluruh periode pemilihan ({$count} periode) menjadi '{$status}'."
        );

        Session::flash('success', "Seluruh periode pemilihan ({$count} periode) berhasil {$label} secara bersamaan.");
        $this->redirect('/admin/elections');
    }

    public function liveStatus(): never
    {
        $elections = Election::getAll();
        $items = [];
        $activeOpenCount = 0;

        foreach ($elections as $el) {
            $tState = Election::getTimeState($el);
            if ($tState['can_vote']) {
                $activeOpenCount++;
            }
            $items[] = [
                'id'              => (int) $el['id'],
                'title'           => (string) $el['title'],
                'status'          => (string) $el['status'],
                'state_code'      => (string) $tState['code'],
                'state_label'     => (string) $tState['label'],
                'can_vote'        => (bool) $tState['can_vote'],
                'start_time'      => (string) $el['start_time'],
                'end_time'        => (string) $el['end_time'],
                'end_ts_ms'       => (int) strtotime((string) $el['end_time']) * 1000,
                'candidate_count' => (int) ($el['candidate_count'] ?? 0),
                'vote_count'      => (int) ($el['vote_count'] ?? 0),
                'published'       => (int) ($el['is_result_published'] ?? 0),
            ];
        }

        $stateHash = md5((string) json_encode($items));

        $this->json([
            'server_time'       => date('d M Y, H:i:s') . ' WIB',
            'server_ts_ms'      => time() * 1000,
            'active_open_count' => $activeOpenCount,
            'total_count'       => count($items),
            'state_hash'        => $stateHash,
            'elections'         => $items,
        ]);
    }

    public function togglePublish(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = Election::findById($id);
        if ($existing === null) {
            Session::flash('error', 'Periode pemilihan tidak ditemukan.');
            $this->redirect('/admin/elections');
        }

        $newState = ((int) $existing['is_result_published']) === 0;
        Election::togglePublish($id, $newState);

        AuditService::log(
            'TOGGLE_RESULT_PUBLISH',
            'elections',
            $id,
            ($newState ? 'Mempublikasikan' : 'Menyembunyikan') . " hasil pemilihan '{$existing['title']}' untuk mahasiswa."
        );

        Session::flash(
            'success',
            $newState
                ? "Hasil pemilihan '{$existing['title']}' kini dipublikasikan dan dapat dilihat oleh mahasiswa."
                : "Publikasi hasil pemilihan '{$existing['title']}' telah ditutup dari halaman mahasiswa."
        );
        $this->redirect('/admin/elections');
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) {
            Session::flash('error', 'ID periode pemilihan tidak valid.');
            $this->redirect('/admin/elections');
        }

        try {
            $deleted = Election::deleteById($id);
            if ($deleted === null) {
                Session::flash('error', 'Periode pemilihan tidak ditemukan.');
            } else {
                AuditService::log(
                    'DELETE_ELECTION',
                    'elections',
                    $id,
                    "Admin menghapus periode pemilihan '{$deleted['title']}' ({$deleted['organization_name']} • {$deleted['period_label']}) beserta {$deleted['candidate_count']} kandidat dan {$deleted['vote_count']} suara."
                );
                Session::flash(
                    'success',
                    "Periode pemilihan '{$deleted['title']}' ({$deleted['organization_name']} • {$deleted['period_label']}) berhasil dihapus."
                );
            }
        } catch (\Throwable $e) {
            Session::flash('error', 'Gagal menghapus periode pemilihan: ' . $e->getMessage());
        }

        $this->redirect('/admin/elections');
    }

    public function bulkDelete(): void
    {
        $rawIds = $_POST['election_ids'] ?? [];
        if (!is_array($rawIds) || empty($rawIds)) {
            Session::flash('warning', 'Pilih minimal satu periode pemilihan yang ingin dihapus terlebih dahulu.');
            $this->redirect('/admin/elections');
        }

        try {
            $count = Election::deleteByIds($rawIds);
            AuditService::log(
                'BULK_DELETE_ELECTIONS',
                'elections',
                null,
                "Admin menghapus {$count} periode pemilihan secara massal."
            );
            Session::flash('success', "Berhasil menghapus {$count} periode pemilihan yang dipilih.");
        } catch (\Throwable $e) {
            Session::flash('error', 'Gagal menghapus periode pemilihan secara massal: ' . $e->getMessage());
        }

        $this->redirect('/admin/elections');
    }
}

