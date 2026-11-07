<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AuditLog;
use App\Models\Election;
use App\Models\Student;
use App\Models\Vote;

final class DashboardController extends Controller
{
    public function index(): void
    {
        $requestedElectionId = isset($_GET['election_id']) ? (int) $_GET['election_id'] : null;
        $election = Election::getActiveOrLatest($requestedElectionId);
        $electionId = $election ? (int) $election['id'] : null;

        $allElections = Election::getAll();
        $activeElections = array_values(array_filter(
            $allElections,
            static fn(array $el): bool => ($el['status'] ?? '') === 'active'
        ));

        $stats = Student::getSummaryStats($electionId);
        $timeState = Election::getTimeState($election);

        $results = $electionId ? Vote::getResultsByElection($electionId) : ['total_votes' => 0, 'candidates' => []];
        $prodiTurnout = $electionId ? Vote::getTurnoutByProdi($electionId) : [];
        $recentVoters = $electionId ? Vote::getRecentAttendances($electionId, 6) : [];
        $recentAudits = AuditLog::getRecent(6);

        $this->render('admin/dashboard', [
            'title'           => 'Dashboard Administrasi',
            'activeMenu'      => 'dashboard',
            'election'        => $election,
            'allElections'    => $allElections,
            'activeElections' => $activeElections,
            'timeState'       => $timeState,
            'stats'           => $stats,
            'results'         => $results,
            'prodiTurnout'    => $prodiTurnout,
            'recentVoters'    => $recentVoters,
            'recentAudits'    => $recentAudits,
        ], 'layouts/admin');
    }
}

