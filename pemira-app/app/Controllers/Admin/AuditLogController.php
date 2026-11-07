<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\AuditLog;

final class AuditLogController extends Controller
{
    public function index(): void
    {
        $actionFilter = trim((string) ($_GET['action'] ?? ''));
        $logs = AuditLog::getRecent(150, $actionFilter !== '' ? $actionFilter : null);
        $actions = AuditLog::getDistinctActions();

        $this->render('admin/audit_logs', [
            'title'        => 'Audit Aktivitas & Keamanan Sistem',
            'activeMenu'   => 'audit',
            'logs'         => $logs,
            'actions'      => $actions,
            'actionFilter' => $actionFilter,
        ], 'layouts/admin');
    }
}

