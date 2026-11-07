<?php
declare(strict_types=1);
use App\Core\Session;

$flashSuccess = Session::flash('success');
$flashError   = Session::flash('error');
$flashWarning = Session::flash('warning');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal E-Voting — PEMIRA Kampus</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-800 min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-10 relative overflow-x-hidden">

    <!-- Decorative Ambient Glow -->
    <div class="fixed -top-40 -left-40 w-96 h-96 bg-indigo-600/25 rounded-full blur-3xl pointer-events-none"></div>
    <div class="fixed -bottom-40 -right-40 w-96 h-96 bg-violet-600/25 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-6xl w-full bg-white rounded-[2rem] shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-12 border border-slate-200/20 relative z-10">

        <!-- Left Hero Panel -->
        <div class="lg:col-span-7 bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white p-8 sm:p-12 flex flex-col justify-between relative overflow-hidden">
            <div class="absolute top-0 right-0 w-80 h-80 bg-indigo-500/10 rounded-full blur-2xl -mr-20 -mt-20"></div>

            <div>
                <!-- Brand Badge -->
                <div class="inline-flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/10 backdrop-blur-md border border-white/15 mb-8">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-xs font-bold tracking-wide uppercase text-indigo-200">Komisi Pemilihan Raya Mahasiswa</span>
                </div>

                <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight leading-tight mb-4">
                    Suara Anda Menentukan Masa Depan Organisasi Kampus.
                </h1>
                <p class="text-slate-300 text-sm sm:text-base leading-relaxed mb-8 max-w-xl">
                    Sistem E-Voting terintegrasi dengan validasi <strong class="text-white">Dataset Mahasiswa Aktif</strong> dan perlindungan <strong class="text-white">Secret Ballot</strong> untuk menjamin pemilihan yang jujur, adil, dan rahasia.
                </p>

                <!-- Active Election Card -->
                <?php if (!empty($election)): ?>
                    <div class="p-5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/15 mb-8">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-300">
                                <?= e($election['organization_name']) ?> &bull; Periode <?= e($election['period_label']) ?>
                            </span>
                            <?php if (($timeState['can_vote'] ?? false) === true): ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                    Bilik Suara Terbuka
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30">
                                    <?= e($timeState['label'] ?? 'Tidak Aktif') ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <h2 class="text-lg font-extrabold text-white mb-2"><?= e($election['title']) ?></h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-300 pt-2 border-t border-white/10">
                            <div>
                                <span class="text-slate-400 block">Waktu Mulai:</span>
                                <strong class="text-white"><?= format_indo_datetime($election['start_time']) ?></strong>
                            </div>
                            <div>
                                <span class="text-slate-400 block">Waktu Berakhir:</span>
                                <strong class="text-white"><?= format_indo_datetime($election['end_time']) ?></strong>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Feature Pills -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10">
                        <div class="text-indigo-400 font-extrabold text-xs uppercase mb-1">01. Validasi Aktif</div>
                        <p class="text-xs text-slate-300">Hanya mahasiswa berstatus aktif pada semester berjalan yang dapat memilih.</p>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10">
                        <div class="text-emerald-400 font-extrabold text-xs uppercase mb-1">02. Satu Suara</div>
                        <p class="text-xs text-slate-300">Proteksi transaksi database mencegah duplikasi suara secara mutlak.</p>
                    </div>
                    <div class="p-3.5 rounded-2xl bg-white/5 border border-white/10">
                        <div class="text-amber-400 font-extrabold text-xs uppercase mb-1">03. Rahasia Penuh</div>
                        <p class="text-xs text-slate-300">Pilihan kandidat dipisahkan dari identitas NIM pemilih di kotak suara.</p>
                    </div>
                </div>
            </div>

            <div class="pt-8 mt-8 border-t border-white/10 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-400">
                <span>Sistem Pemilihan Raya Kampus v1.1</span>
                <span>Server Time: <?= date('d M Y, H:i') ?> WIB</span>
            </div>
        </div>

        <!-- Right Login Form Panel -->
        <div class="lg:col-span-5 p-8 sm:p-12 flex flex-col justify-center bg-white">
            <div class="mb-7">
                <div class="w-12 h-12 rounded-2xl bg-indigo-600 text-white flex items-center justify-center shadow-lg shadow-indigo-600/25 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">Masuk ke Portal Pemilihan</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Masukkan <strong>NIM</strong> (bagi Mahasiswa) atau <strong>Username</strong> (bagi Admin KPRM).
                </p>
            </div>

            <?php if ($flashError): ?>
                <div class="mb-5 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs sm:text-sm font-medium flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?= e($flashError) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($flashSuccess): ?>
                <div class="mb-5 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs sm:text-sm font-medium flex items-start gap-2.5">
                    <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span><?= e($flashSuccess) ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= url('/login') ?>" method="POST" class="space-y-5">
                <?= csrf_field() ?>

                <div>
                    <label for="username" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        NIM Mahasiswa / Username Admin
                    </label>
                    <div class="relative">
                        <input type="text" id="username" name="username" required autofocus
                               placeholder="Contoh: 230101001"
                               class="w-full pl-11 pr-4 py-3 rounded-xl border border-slate-300 text-slate-900 text-sm font-medium focus:border-indigo-600 focus:ring-4 focus:ring-indigo-500/15 outline-none transition">
                        <svg class="w-5 h-5 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               placeholder="Masukkan password Anda"
                               class="w-full pl-11 pr-11 py-3 rounded-xl border border-slate-300 text-slate-900 text-sm font-medium focus:border-indigo-600 focus:ring-4 focus:ring-indigo-500/15 outline-none transition">
                        <svg class="w-5 h-5 text-slate-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <button type="button" onclick="togglePass()" class="absolute right-3.5 top-3 text-slate-400 hover:text-slate-600 text-xs font-semibold p-1">
                            Lihat
                        </button>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1.5">
                        * Password awal mahasiswa baru adalah <strong>NIM</strong>, dan disarankan untuk langsung diganti setelah login pertama.
                    </p>
                </div>

                <button type="submit"
                        class="w-full py-3.5 px-6 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white font-bold text-sm shadow-lg shadow-indigo-600/25 transition flex items-center justify-center gap-2">
                    <span>Masuk & Verifikasi Hak Pilih</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </form>

            <!-- Quick Fill Demo Credentials Box -->
            <div class="mt-8 pt-6 border-t border-slate-200/80">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-3">
                    Akun Simulasi Pengujian Cepat (Klik untuk Isi Otomatis)
                </p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button" onclick="fillDemo('230101001', '230101001')"
                            class="text-left p-2.5 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50/50 transition group">
                        <div class="text-xs font-bold text-slate-800 group-hover:text-indigo-700">Mahasiswa Aktif 1</div>
                        <div class="text-[11px] text-slate-500">NIM: 230101001 (S1 TI)</div>
                    </button>
                    <button type="button" onclick="fillDemo('230101002', '230101002')"
                            class="text-left p-2.5 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50/50 transition group">
                        <div class="text-xs font-bold text-slate-800 group-hover:text-indigo-700">Mahasiswa Aktif 2</div>
                        <div class="text-[11px] text-slate-500">NIM: 230101002 (S1 SI)</div>
                    </button>
                    <button type="button" onclick="fillDemo('220101007', '220101007')"
                            class="text-left p-2.5 rounded-xl border border-slate-200 hover:border-rose-400 hover:bg-rose-50/50 transition group">
                        <div class="text-xs font-bold text-slate-800 group-hover:text-rose-700">Mahasiswa Cuti (AC-01)</div>
                        <div class="text-[11px] text-slate-500">NIM: 220101007 (Ditolak)</div>
                    </button>
                    <button type="button" onclick="fillDemo('admin', 'admin123')"
                            class="text-left p-2.5 rounded-xl border border-slate-200 hover:border-violet-400 hover:bg-violet-50/50 transition group">
                        <div class="text-xs font-bold text-slate-800 group-hover:text-violet-700">Administrator KPRM</div>
                        <div class="text-[11px] text-slate-500">User: admin / admin123</div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePass() {
            const input = document.getElementById('password');
            input.type = input.type === 'password' ? 'text' : 'password';
        }
        function fillDemo(user, pass) {
            document.getElementById('username').value = user;
            document.getElementById('password').value = pass;
        }
    </script>
</body>
</html>

