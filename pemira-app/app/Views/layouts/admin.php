<?php
declare(strict_types=1);
use App\Core\Session;

$authUser = Session::user();
$activeMenu = $activeMenu ?? 'dashboard';
$flashSuccess = Session::flash('success');
$flashError   = Session::flash('error');
$flashWarning = Session::flash('warning');

$navItems = [
    ['key' => 'dashboard',  'label' => 'Dashboard Utama',     'href' => '/admin/dashboard',  'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
    ['key' => 'voters',     'label' => 'Dataset Mahasiswa',   'href' => '/admin/voters',     'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
    ['key' => 'candidates', 'label' => 'Data Calon Ketua',    'href' => '/admin/candidates', 'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z'],
    ['key' => 'elections',  'label' => 'Periode Pemilihan',   'href' => '/admin/elections',  'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
    ['key' => 'results',    'label' => 'Rekapitulasi Hasil',  'href' => '/admin/results',    'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'],
    ['key' => 'audit',      'label' => 'Audit Aktivitas',     'href' => '/admin/audit-logs', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Panel Admin') ?> — PEMIRA Kampus</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        /* Custom scrollbar halus untuk sidebar */
        #adminSidebar::-webkit-scrollbar { width: 5px; }
        #adminSidebar::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.25); border-radius: 9999px; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen antialiased">

    <!-- Backdrop Overlay untuk Mobile Drawer -->
    <div id="sidebarBackdrop"
         onclick="toggleMobileSidebar(false)"
         class="fixed inset-0 z-40 bg-slate-950/60 backdrop-blur-sm hidden lg:hidden transition-opacity"></div>

    <!-- Fixed Sidebar (Desktop & Mobile Slide-Over Drawer) -->
    <aside id="adminSidebar"
           class="fixed inset-y-0 left-0 z-50 w-72 h-screen bg-slate-900 text-slate-300 flex flex-col justify-between overflow-y-auto transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out shadow-2xl lg:shadow-none">
        <div>
            <!-- Brand Header -->
            <div class="px-6 py-5 flex items-center justify-between gap-3 border-b border-slate-800/90 sticky top-0 bg-slate-900 z-10">
                <a href="<?= url('/admin/dashboard') ?>" class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/30 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-white font-extrabold text-base tracking-tight leading-none">PEMIRA KAMPUS</h1>
                        <p class="text-[11px] text-indigo-400 font-semibold mt-1">Control Panel KPRM</p>
                    </div>
                </a>

                <!-- Tombol Tutup Sidebar di Mobile -->
                <button type="button" onclick="toggleMobileSidebar(false)"
                        class="lg:hidden p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition"
                        aria-label="Tutup Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <!-- Kartu Profil Admin Ringkas di Mobile -->
            <div class="lg:hidden px-4 pt-4">
                <div class="p-3.5 rounded-2xl bg-slate-800/80 border border-slate-700/70 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white font-bold text-xs flex items-center justify-center shrink-0">
                        AD
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-white truncate"><?= e($authUser['name'] ?? 'Administrator KPRM') ?></p>
                        <p class="text-[10px] text-indigo-400 font-semibold uppercase tracking-wider">Administrator Aktif</p>
                    </div>
                </div>
            </div>

            <!-- Navigation Menu -->
            <div class="px-4 py-5">
                <p class="px-3 text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2.5">Menu Administrasi</p>
                <nav class="space-y-1.5">
                    <?php foreach ($navItems as $item): ?>
                        <?php $isActive = ($activeMenu === $item['key']); ?>
                        <a href="<?= url($item['href']) ?>"
                           class="flex items-center gap-3 px-3.5 py-3 rounded-xl text-sm font-semibold transition <?= $isActive ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30' : 'text-slate-400 hover:text-white hover:bg-slate-800/80' ?>">
                            <svg class="w-5 h-5 shrink-0 <?= $isActive ? 'text-white' : 'text-slate-400' ?>" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="<?= $item['icon'] ?>"/>
                            </svg>
                            <span><?= e($item['label']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>

        <!-- Sidebar Footer Info (Selalu menempel di bagian bawah viewport) -->
        <div class="p-4 border-t border-slate-800/90 bg-slate-900">
            <div class="p-3.5 rounded-2xl bg-slate-800/70 border border-slate-700/60">
                <div class="flex items-center gap-2 text-xs font-bold text-emerald-400 mb-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Secret Ballot Aktif</span>
                </div>
                <p class="text-[11px] text-slate-400 leading-relaxed">
                    Suara mahasiswa dienkripsi dan terpisah dari daftar hadir pemilih untuk menjamin asas rahasia.
                </p>
            </div>
        </div>
    </aside>

    <!-- Main Area (Diberi padding kiri lg:pl-72 mengikuti lebar fixed sidebar) -->
    <div class="lg:pl-72 flex flex-col min-h-screen">
        <!-- Top Header -->
        <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                    <!-- Mobile Menu Toggle -->
                    <button type="button" onclick="toggleMobileSidebar(true)"
                            class="lg:hidden p-2 rounded-xl text-slate-600 hover:bg-slate-100 border border-slate-200/80 shrink-0"
                            aria-label="Buka Menu">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <h2 class="text-sm sm:text-lg font-extrabold text-slate-900 truncate"><?= e($title ?? 'Dashboard Admin') ?></h2>
                </div>

                <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                    <div class="hidden md:flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-slate-100 border border-slate-200/60">
                        <div class="w-7 h-7 rounded-lg bg-slate-900 text-white font-bold text-xs flex items-center justify-center">
                            AD
                        </div>
                        <div class="text-left">
                            <p class="text-xs font-bold text-slate-800 leading-tight"><?= e($authUser['name'] ?? 'Administrator') ?></p>
                            <p class="text-[10px] text-indigo-600 font-bold uppercase tracking-wider">Admin KPRM</p>
                        </div>
                    </div>

                    <button type="button" onclick="document.getElementById('adminPassModal').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 px-2.5 sm:px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition">
                        <svg class="w-4 h-4 text-indigo-600 sm:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <span class="hidden sm:inline">Ganti Password</span>
                    </button>

                    <form action="<?= url('/logout') ?>" method="POST" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="inline-flex items-center gap-1.5 px-2.5 sm:px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/70 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span class="hidden sm:inline">Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <!-- Content Wrapper -->
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
            <?php if ($flashSuccess): ?>
                <div class="mb-5 flex items-start gap-3 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 shadow-sm">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm font-medium flex-1"><?= e($flashSuccess) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($flashWarning): ?>
                <div class="mb-5 flex items-start gap-3 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 shadow-sm">
                    <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div class="text-sm font-medium flex-1"><?= e($flashWarning) ?></div>
                </div>
            <?php endif; ?>

            <?php if ($flashError): ?>
                <div class="mb-5 flex items-start gap-3 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 shadow-sm">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm font-medium flex-1"><?= e($flashError) ?></div>
                </div>
            <?php endif; ?>

            <?= $content ?>
        </main>
    </div>

    <!-- Admin Password Modal -->
    <div id="adminPassModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-slate-900">Ganti Password Administrator</h3>
                <button type="button" onclick="document.getElementById('adminPassModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>
            <form action="<?= url('/password/change') ?>" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password Saat Ini</label>
                    <input type="password" name="current_password" required class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Password Baru</label>
                    <input type="password" name="new_password" required minlength="6" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" required minlength="6" class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('adminPassModal').classList.add('hidden')" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700">Simpan Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleMobileSidebar(open) {
            const sidebar = document.getElementById('adminSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (open) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
            }
        }
    </script>
</body>
</html>
