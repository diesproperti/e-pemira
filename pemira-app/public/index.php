<?php

declare(strict_types=1);

date_default_timezone_set('Asia/Jakarta');

// PSR-4 Autoloader untuk namespace App\
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

require_once dirname(__DIR__) . '/app/Core/Helpers.php';

use App\Controllers\Admin\AuditLogController;
use App\Controllers\Admin\CandidateController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ElectionController;
use App\Controllers\Admin\ResultController;
use App\Controllers\Admin\VoterController;
use App\Controllers\AuthController;
use App\Controllers\StudentController;
use App\Core\Router;
use App\Core\Session;

Session::start();

$router = new Router();

// --- Public & Autentikasi (FR-02, FR-03, FR-04) ---
$router->get('/', [AuthController::class, 'home']);
$router->get('/login', [AuthController::class, 'showLogin'], ['guest']);
$router->post('/login', [AuthController::class, 'login'], ['guest']);
$router->post('/logout', [AuthController::class, 'logout'], ['auth']);
$router->post('/password/change', [AuthController::class, 'updatePassword'], ['auth']);

// --- Portal Mahasiswa / Bilik Suara (FR-05, FR-07, FR-09, FR-10, FR-11) ---
$router->get('/student/dashboard', [StudentController::class, 'dashboard'], ['student']);
$router->get('/student/live-status', [StudentController::class, 'liveStatus'], ['student']);
$router->post('/student/vote', [StudentController::class, 'vote'], ['student']);
$router->get('/student/receipt', [StudentController::class, 'receipt'], ['student']);

// --- Panel Administrasi KPRM (FR-01, FR-06, FR-08, FR-12, FR-13, FR-14, FR-15) ---
$router->get('/admin/dashboard', [DashboardController::class, 'index'], ['admin']);

$router->get('/admin/voters', [VoterController::class, 'index'], ['admin']);
$router->get('/admin/voters/suggest', [VoterController::class, 'suggest'], ['admin']);
$router->get('/admin/voters/export-csv', [VoterController::class, 'exportVotersCsv'], ['admin']);
$router->post('/admin/voters/import', [VoterController::class, 'importCsv'], ['admin']);
$router->get('/admin/voters/template', [VoterController::class, 'downloadTemplate'], ['admin']);
$router->post('/admin/voters/store', [VoterController::class, 'storeManual'], ['admin']);
$router->post('/admin/voters/reset-password', [VoterController::class, 'resetPassword'], ['admin']);
$router->post('/admin/voters/delete', [VoterController::class, 'delete'], ['admin']);
$router->post('/admin/voters/bulk-delete', [VoterController::class, 'bulkDelete'], ['admin']);

$router->get('/admin/candidates', [CandidateController::class, 'index'], ['admin']);
$router->post('/admin/candidates/store', [CandidateController::class, 'store'], ['admin']);
$router->post('/admin/candidates/update', [CandidateController::class, 'update'], ['admin']);
$router->post('/admin/candidates/delete', [CandidateController::class, 'delete'], ['admin']);

$router->get('/admin/elections', [ElectionController::class, 'index'], ['admin']);
$router->get('/admin/elections/live-status', [ElectionController::class, 'liveStatus'], ['admin']);
$router->post('/admin/elections/store', [ElectionController::class, 'store'], ['admin']);
$router->post('/admin/elections/update', [ElectionController::class, 'update'], ['admin']);
$router->post('/admin/elections/status', [ElectionController::class, 'changeStatus'], ['admin']);
$router->post('/admin/elections/status-all', [ElectionController::class, 'changeAllStatus'], ['admin']);
$router->post('/admin/elections/publish', [ElectionController::class, 'togglePublish'], ['admin']);
$router->post('/admin/elections/delete', [ElectionController::class, 'delete'], ['admin']);
$router->post('/admin/elections/bulk-delete', [ElectionController::class, 'bulkDelete'], ['admin']);

$router->get('/admin/results', [ResultController::class, 'index'], ['admin']);
$router->get('/admin/results/export-csv', [ResultController::class, 'exportCsv'], ['admin']);
$router->get('/admin/results/print', [ResultController::class, 'printReport'], ['admin']);

$router->get('/admin/audit-logs', [AuditLogController::class, 'index'], ['admin']);

$router->dispatch();

