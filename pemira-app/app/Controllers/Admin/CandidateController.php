<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Candidate;
use App\Models\Election;
use App\Services\AuditService;

final class CandidateController extends Controller
{
    public function index(): void
    {
        $elections = Election::getAll();
        $selectedElectionId = isset($_GET['election_id']) ? (int) $_GET['election_id'] : null;
        $election = Election::getActiveOrLatest($selectedElectionId);

        $candidates = $election ? Candidate::getByElection((int) $election['id']) : [];

        $editId = (int) ($_GET['edit_id'] ?? 0);
        $editCandidate = $editId > 0 ? Candidate::findById($editId) : null;

        $this->render('admin/candidates', [
            'title'         => 'Kelola Data Calon Ketua',
            'activeMenu'    => 'candidates',
            'elections'     => $elections,
            'election'      => $election,
            'candidates'    => $candidates,
            'editCandidate' => $editCandidate,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $electionId = (int) ($_POST['election_id'] ?? 0);
        $number     = (int) ($_POST['candidate_number'] ?? 0);
        $name       = trim((string) ($_POST['name'] ?? ''));
        $nim        = trim((string) ($_POST['nim'] ?? ''));
        $jenjang    = strtoupper(trim((string) ($_POST['jenjang'] ?? 'S1')));
        $prodi      = trim((string) ($_POST['program_studi'] ?? ''));
        $tagline    = trim((string) ($_POST['tagline'] ?? ''));
        $vision     = trim((string) ($_POST['vision'] ?? ''));
        $mission    = trim((string) ($_POST['mission'] ?? ''));

        if ($electionId <= 0 || $number <= 0 || $name === '' || $nim === '' || $prodi === '' || $vision === '' || $mission === '') {
            Session::flash('error', 'Harap lengkapi seluruh data wajib kandidat (Nomor Urut, Nama, NIM, Prodi, Visi, dan Misi).');
            $this->redirect('/admin/candidates?election_id=' . $electionId);
        }

        if (Candidate::existsNumberInElection($electionId, $number)) {
            Session::flash('error', "Nomor urut {$number} sudah digunakan oleh kandidat lain pada periode pemilihan ini.");
            $this->redirect('/admin/candidates?election_id=' . $electionId);
        }

        $photoPath = $this->handlePhotoUpload();

        $id = Candidate::create([
            'election_id'      => $electionId,
            'candidate_number' => $number,
            'name'             => $name,
            'nim'              => $nim,
            'jenjang'          => $jenjang,
            'program_studi'    => $prodi,
            'tagline'          => $tagline,
            'photo_path'       => $photoPath,
            'vision'           => $vision,
            'mission'          => $mission,
        ]);

        AuditService::log(
            'CREATE_CANDIDATE',
            'candidates',
            $id,
            "Menambahkan Calon Ketua Nomor Urut {$number}: {$name} (NIM: {$nim})."
        );

        Session::flash('success', "Calon Ketua Nomor Urut {$number} ({$name}) berhasil ditambahkan.");
        $this->redirect('/admin/candidates?election_id=' . $electionId);
    }

    public function update(): void
    {
        $id         = (int) ($_POST['id'] ?? 0);
        $electionId = (int) ($_POST['election_id'] ?? 0);
        $number     = (int) ($_POST['candidate_number'] ?? 0);
        $name       = trim((string) ($_POST['name'] ?? ''));
        $nim        = trim((string) ($_POST['nim'] ?? ''));
        $jenjang    = strtoupper(trim((string) ($_POST['jenjang'] ?? 'S1')));
        $prodi      = trim((string) ($_POST['program_studi'] ?? ''));
        $tagline    = trim((string) ($_POST['tagline'] ?? ''));
        $vision     = trim((string) ($_POST['vision'] ?? ''));
        $mission    = trim((string) ($_POST['mission'] ?? ''));

        $existing = Candidate::findById($id);
        if ($existing === null) {
            Session::flash('error', 'Data kandidat tidak ditemukan.');
            $this->redirect('/admin/candidates');
        }

        if (Candidate::existsNumberInElection((int) $existing['election_id'], $number, $id)) {
            Session::flash('error', "Nomor urut {$number} sudah digunakan oleh kandidat lain.");
            $this->redirect('/admin/candidates?election_id=' . $existing['election_id'] . '&edit_id=' . $id);
        }

        $photoPath = $this->handlePhotoUpload() ?? $existing['photo_path'];

        Candidate::update($id, [
            'candidate_number' => $number,
            'name'             => $name,
            'nim'              => $nim,
            'jenjang'          => $jenjang,
            'program_studi'    => $prodi,
            'tagline'          => $tagline,
            'photo_path'       => $photoPath,
            'vision'           => $vision,
            'mission'          => $mission,
        ]);

        AuditService::log(
            'UPDATE_CANDIDATE',
            'candidates',
            $id,
            "Memperbarui data Calon Ketua Nomor Urut {$number}: {$name} (NIM: {$nim})."
        );

        Session::flash('success', "Data Calon Ketua {$name} berhasil diperbarui.");
        $this->redirect('/admin/candidates?election_id=' . $existing['election_id']);
    }

    public function delete(): void
    {
        $id = (int) ($_POST['id'] ?? 0);
        $existing = Candidate::findById($id);

        if ($existing === null) {
            Session::flash('error', 'Kandidat tidak ditemukan.');
            $this->redirect('/admin/candidates');
        }

        if (Candidate::hasVotes($id)) {
            Session::flash('error', 'Kandidat tidak dapat dihapus karena sudah memiliki suara masuk di kotak suara.');
            $this->redirect('/admin/candidates?election_id=' . $existing['election_id']);
        }

        Candidate::delete($id);

        AuditService::log(
            'DELETE_CANDIDATE',
            'candidates',
            $id,
            "Menghapus Calon Ketua Nomor Urut {$existing['candidate_number']}: {$existing['name']}."
        );

        Session::flash('success', "Kandidat {$existing['name']} berhasil dihapus.");
        $this->redirect('/admin/candidates?election_id=' . $existing['election_id']);
    }

    private function handlePhotoUpload(): ?string
    {
        if (empty($_FILES['photo']) || ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES['photo'];
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            Session::flash('warning', 'Format foto kandidat harus berupa JPG, PNG, atau WEBP.');
            return null;
        }

        $uploadDir = base_path('public/uploads/candidates');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $filename = 'cand_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $target = $uploadDir . '/' . $filename;

        if (move_uploaded_file((string) $file['tmp_name'], $target)) {
            return 'uploads/candidates/' . $filename;
        }

        return null;
    }
}

