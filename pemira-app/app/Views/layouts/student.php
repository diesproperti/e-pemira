<?php
declare(strict_types=1);
use App\Core\Session;

$authUser = Session::user();
$flashSuccess = Session::flash('success');
$flashError   = Session::flash('error');
$flashWarning = Session::flash('warning');
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Bilik Suara Mahasiswa') ?> — PEMIRA Kampus</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col selection:bg-indigo-600 selection:text-white">

    <!-- Top Navigation Bar -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-40 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-18 py-3 flex items-center justify-between gap-4">
            <a href="<?= url('/student/dashboard') ?>" class="flex items-center gap-3 group">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20 group-hover:scale-105 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-extrabold text-slate-900 tracking-tight text-base sm:text-lg">PEMIRA KAMPUS</span>
                        <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200/60">
                            Secret Ballot
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 hidden sm:block">Portal Pemilihan Raya Ketua Organisasi Mahasiswa</p>
                </div>
            </a>

            <div class="flex items-center gap-2 sm:gap-3">
                <?php if ($authUser): ?>
                    <div class="hidden md:flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-slate-100/80 border border-slate-200/70">
                        <div class="w-8 h-8 rounded-lg bg-indigo-600 text-white font-bold text-xs flex items-center justify-center">
                            <?= e(initials((string) $authUser['name'])) ?>
                        </div>
                        <div class="text-left">
                            <p class="text-xs font-bold text-slate-800 leading-tight"><?= e($authUser['name']) ?></p>
                            <p class="text-[11px] text-slate-500 font-medium">NIM: <?= e($authUser['username']) ?></p>
                        </div>
                    </div>

                    <button type="button" onclick="document.getElementById('passwordModal').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 transition shadow-sm">
                        <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                        </svg>
                        <span>Ubah Password</span>
                        <?php if ((int) ($authUser['must_change_password'] ?? 0) === 1): ?>
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                        <?php endif; ?>
                    </button>

                    <form action="<?= url('/logout') ?>" method="POST" class="inline">
                        <?= csrf_field() ?>
                        <button type="submit" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-200/70 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span class="hidden sm:inline">Keluar</span>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Flash Notifications -->
    <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4 no-print">
        <?php if ($flashSuccess): ?>
            <div class="mb-4 flex items-start gap-3 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-900 shadow-sm">
                <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm font-medium flex-1"><?= e($flashSuccess) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($flashWarning): ?>
            <div class="mb-4 flex items-start gap-3 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 shadow-sm">
                <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="text-sm font-medium flex-1"><?= e($flashWarning) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($flashError): ?>
            <div class="mb-4 flex items-start gap-3 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 shadow-sm">
                <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm font-medium flex-1"><?= e($flashError) ?></div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <?= $content ?>
    </main>

    <!-- Modal Kustomisasi Password Mandiri Mahasiswa -->
    <div id="passwordModal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 no-print">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Kustomisasi Password Akun</h3>
                        <p class="text-xs text-slate-500">Perbarui password Anda untuk menjaga kerahasiaan akun</p>
                    </div>
                </div>
                <button type="button" onclick="document.getElementById('passwordModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form action="<?= url('/password/change') ?>" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password Saat Ini</label>
                    <input type="password" name="current_password" required
                           placeholder="Masukkan password saat ini (default: NIM)"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:border-indigo-600 focus:ring-3 focus:ring-indigo-500/15 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Password Baru (Minimal 6 Karakter)</label>
                    <input type="password" name="new_password" required minlength="6"
                           placeholder="Buat password baru pilihan Anda"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:border-indigo-600 focus:ring-3 focus:ring-indigo-500/15 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Konfirmasi Password Baru</label>
                    <input type="password" name="confirm_password" required minlength="6"
                           placeholder="Ulangi password baru Anda"
                           class="w-full px-3.5 py-2.5 text-sm rounded-xl border border-slate-300 focus:border-indigo-600 focus:ring-3 focus:ring-indigo-500/15 outline-none">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('passwordModal').classList.add('hidden')"
                            class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/20 transition">
                        Simpan Password Baru
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200/80 py-5 mt-12 no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-2 text-xs text-slate-500">
            <p>&copy; <?= date('Y') ?> <strong>Komisi Pemilihan Raya Mahasiswa (KPRM)</strong>. Hak Cipta Dilindungi.</p>
            <p class="flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                <span>Sistem E-Voting Terenkripsi &bull; 1 Mahasiswa 1 Suara (Secret Ballot)</span>
            </p>
        </div>
    </footer>

</body>
</html>

