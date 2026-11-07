<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Student;
use App\Models\Vote;
use App\Services\VotingService;
use DomainException;

final class StudentController extends Controller
{
    public function dashboard(): void
    {
        $authUser = Session::user();
        $student = Student::findByUserId((int) $authUser['id']);
        if ($student === null) {
            Session::destroy();
            $this->redirect('/login');
        }

        $studentId = (int) $student['id'];
        $rawElections = Election::getConcurrentForStudent();
        $requestedElectionId = (int) ($_GET['election_id'] ?? 0);

        $concurrentElections = [];
        $selectedElection = null;
        $totalActiveCount = 0;
        $totalOpenCount = 0;
        $totalVotedCount = 0;
        $nextUnvotedElection = null;
        $hashPayload = [];

        foreach ($rawElections as $el) {
            $eid = (int) $el['id'];
            $tState = Election::getTimeState($el);
            $att = Vote::getAttendance($eid, $studentId);
            $cands = Candidate::getByElection($eid);
            $pubRes = ((int) ($el['is_result_published'] ?? 0) === 1)
                ? Vote::getResultsByElection($eid)
                : null;

            if ($att !== null) {
                $totalVotedCount++;
            }

            if ($el['status'] === 'active') {
                $totalActiveCount++;
            }

            if ($tState['can_vote']) {
                $totalOpenCount++;
                if ($att === null && $nextUnvotedElection === null) {
                    $nextUnvotedElection = $el;
                }
            }

            $hashPayload[] = [
                'id'              => $eid,
                'status'          => (string) $el['status'],
                'state_code'      => (string) $tState['code'],
                'can_vote'        => (bool) $tState['can_vote'],
                'start_time'      => (string) $el['start_time'],
                'end_time'        => (string) $el['end_time'],
                'published'       => (int) ($el['is_result_published'] ?? 0),
                'candidate_count' => count($cands),
                'has_voted'       => ($att !== null),
            ];

            $enriched = [
                'election'         => $el,
                'timeState'        => $tState,
                'candidates'       => $cands,
                'attendance'       => $att,
                'publishedResults' => $pubRes,
            ];

            $concurrentElections[$eid] = $enriched;

            if ($requestedElectionId > 0 && $eid === $requestedElectionId) {
                $selectedElection = $enriched;
            }
        }

        // Jika belum memilih tab tertentu, prioritaskan periode yang sedang terbuka & belum dipilih mahasiswa
        if ($selectedElection === null && !empty($concurrentElections)) {
            foreach ($concurrentElections as $item) {
                if ($item['attendance'] === null && ($item['timeState']['can_vote'] ?? false)) {
                    $selectedElection = $item;
                    break;
                }
            }
            if ($selectedElection === null) {
                $selectedElection = reset($concurrentElections);
            }
        }

        $liveStateHash = md5((string) json_encode($hashPayload));

        $this->render('student/dashboard', [
            'title'               => 'Bilik Suara Mahasiswa',
            'student'             => $student,
            'concurrentElections' => array_values($concurrentElections),
            'selectedItem'        => $selectedElection,
            'election'            => $selectedElection['election'] ?? null,
            'timeState'           => $selectedElection['timeState'] ?? ['code' => 'none', 'label' => 'Belum Ada Periode', 'can_vote' => false],
            'candidates'          => $selectedElection['candidates'] ?? [],
            'attendance'          => $selectedElection['attendance'] ?? null,
            'publishedResults'    => $selectedElection['publishedResults'] ?? null,
            'totalPeriodsCount'   => count($concurrentElections),
            'totalActiveCount'    => $totalActiveCount,
            'totalOpenCount'      => $totalOpenCount,
            'totalVotedCount'     => $totalVotedCount,
            'nextUnvotedElection' => $nextUnvotedElection,
            'liveStateHash'       => $liveStateHash,
            'requestedElectionId' => $requestedElectionId,
        ], 'layouts/student');
    }

    public function liveStatus(): never
    {
        $authUser = Session::user();
        $student = $authUser ? Student::findByUserId((int) $authUser['id']) : null;
        if ($student === null) {
            $this->json(['error' => 'Unauthorized'], 401);
        }

        $studentId = (int) $student['id'];
        $rawElections = Election::getConcurrentForStudent();
        $hashPayload = [];
        $electionsData = [];
        $totalOpenCount = 0;
        $totalVotedCount = 0;

        foreach ($rawElections as $el) {
            $eid = (int) $el['id'];
            $tState = Election::getTimeState($el);
            $att = Vote::getAttendance($eid, $studentId);
            $hasVoted = ($att !== null);

            if ($hasVoted) {
                $totalVotedCount++;
            }
            if ($tState['can_vote']) {
                $totalOpenCount++;
            }

            $itemState = [
                'id'              => $eid,
                'status'          => (string) $el['status'],
                'state_code'      => (string) $tState['code'],
                'can_vote'        => (bool) $tState['can_vote'],
                'start_time'      => (string) $el['start_time'],
                'end_time'        => (string) $el['end_time'],
                'published'       => (int) ($el['is_result_published'] ?? 0),
                'candidate_count' => (int) ($el['candidate_count'] ?? 0),
                'has_voted'       => $hasVoted,
            ];
            $hashPayload[] = $itemState;

            $electionsData[] = array_merge($itemState, [
                'title'        => (string) $el['title'],
                'org'          => (string) $el['organization_name'],
                'period'       => (string) $el['period_label'],
                'state_label'  => (string) $tState['label'],
                'end_ts_ms'    => (int) strtotime((string) $el['end_time']) * 1000,
                'receipt_code' => $att['receipt_code'] ?? null,
            ]);
        }

        $this->json([
            'server_time'   => date('d M Y, H:i:s') . ' WIB',
            'server_ts_ms'  => time() * 1000,
            'state_hash'    => md5((string) json_encode($hashPayload)),
            'total_periods' => count($electionsData),
            'total_open'    => $totalOpenCount,
            'total_voted'   => $totalVotedCount,
            'elections'     => $electionsData,
        ]);
    }

    public function vote(): void
    {
        $authUser = Session::user();
        $student = Student::findByUserId((int) $authUser['id']);
        if ($student === null) {
            $this->redirect('/login');
        }

        $electionId  = (int) ($_POST['election_id'] ?? 0);
        $candidateId = (int) ($_POST['candidate_id'] ?? 0);

        if ($electionId <= 0 || $candidateId <= 0) {
            Session::flash('error', 'Pilihan kandidat tidak valid. Silakan pilih salah satu calon terlebih dahulu.');
            $this->redirect('/student/dashboard');
        }

        try {
            $receiptCode = VotingService::castVote((int) $student['id'], $electionId, $candidateId);

            // Periksa apakah masih ada periode pemilihan aktif lain yang belum dipilih oleh mahasiswa ini
            $remainingActive = [];
            foreach (Election::getConcurrentForStudent() as $el) {
                $eid = (int) $el['id'];
                if ($eid !== $electionId && Election::isVotingOpen($el)) {
                    $att = Vote::getAttendance($eid, (int) $student['id']);
                    if ($att === null) {
                        $remainingActive[] = $el['title'];
                    }
                }
            }

            $msg = 'Terima kasih! Suara Anda berhasil disimpan secara rahasia (Secret Ballot) dengan Kode Bukti: ' . $receiptCode . '.';
            if (!empty($remainingActive)) {
                $msg .= ' Anda masih memiliki hak pilih aktif pada: ' . implode(', ', $remainingActive) . '.';
            }

            Session::flash('success', $msg);
            $this->redirect('/student/receipt?code=' . urlencode($receiptCode));
        } catch (DomainException $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect('/student/dashboard?election_id=' . $electionId);
        }
    }

    public function receipt(): void
    {
        $authUser = Session::user();
        $student = Student::findByUserId((int) $authUser['id']);
        if ($student === null) {
            $this->redirect('/login');
        }

        $code = trim((string) ($_GET['code'] ?? ''));
        $attendance = null;

        if ($code !== '') {
            $attendance = Vote::getAttendanceByReceipt($code);
            if ($attendance && (int) $attendance['student_id'] !== (int) $student['id']) {
                $attendance = null;
            }
        }

        if ($attendance === null) {
            $election = Election::getActiveOrLatest();
            if ($election !== null) {
                $att = Vote::getAttendance((int) $election['id'], (int) $student['id']);
                if ($att !== null) {
                    $attendance = Vote::getAttendanceByReceipt((string) $att['receipt_code']);
                }
            }
        }

        if ($attendance === null) {
            Session::flash('error', 'Bukti partisipasi voting belum ditemukan.');
            $this->redirect('/student/dashboard');
        }

        // Cari apakah masih ada periode pemilihan aktif lain yang belum dipilih mahasiswa ini
        $nextUnvotedElection = null;
        foreach (Election::getConcurrentForStudent() as $el) {
            $eid = (int) $el['id'];
            if (Election::isVotingOpen($el) && Vote::getAttendance($eid, (int) $student['id']) === null) {
                $nextUnvotedElection = $el;
                break;
            }
        }

        $this->render('student/receipt', [
            'title'               => 'Tanda Bukti Partisipasi Pemilihan',
            'student'             => $student,
            'attendance'          => $attendance,
            'nextUnvotedElection' => $nextUnvotedElection,
        ], 'layouts/student');
    }
}
