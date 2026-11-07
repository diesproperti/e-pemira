<?php
declare(strict_types=1);

$concurrentElections = $concurrentElections ?? [];
$totalPeriodsCount   = count($concurrentElections);
$totalActiveCount    = (int) ($totalActiveCount ?? 0);
$totalOpenCount      = (int) ($totalOpenCount ?? 0);
$totalVotedCount     = (int) ($totalVotedCount ?? 0);
$nextUnvotedElection = $nextUnvotedElection ?? null;
$liveStateHash       = (string) ($liveStateHash ?? '');
$requestedElectionId = (int) ($requestedElectionId ?? 0);

$mustChangePass    = ((int) ($student['must_change_password'] ?? 0)) === 1;
$currentElectionId = (int) ($election['id'] ?? 0);
?>

<!-- Real-Time Sync Toast Notification (Hidden by default) -->
<div id="realtimeToast" class="hidden fixed bottom-5 right-5 z-50 max-w-sm bg-slate-900 text-white px-5 py-4 rounded-2xl shadow-2xl border border-indigo-500/40 flex items-center gap-3">
    <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping shrink-0"></span>
    <div class="text-xs">
        <p class="font-extrabold text-white">Pembaruan Status Real-Time</p>
        <p class="text-slate-300 mt-0.5">Status periode pemilihan telah berubah. Memuat ulang tampilan...</p>
    </div>
</div>

<!-- First-Time Login Password Customization Prompt -->
<?php if ($mustChangePass): ?>
    <div class="mb-6 p-5 sm:p-6 rounded-3xl bg-gradient-to-r from-amber-50 via-orange-50 to-amber-50 border border-amber-200/90 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 rounded-2xl bg-amber-500 text-white flex items-center justify-center shrink-0 shadow-md shadow-amber-500/20">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
            </div>
            <div>
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-200/70 text-amber-900 mb-1">
                    Rekomendasi Login Pertama
                </div>
                <h3 class="text-base font-extrabold text-slate-900">Kustomisasi Password Akun Mahasiswa Anda</h3>
                <p class="text-xs sm:text-sm text-slate-600 mt-0.5">
                    Saat ini Anda masih menggunakan password bawaan sistem. Demi menjaga keamanan hak pilih Anda, silakan buat password pribadi Anda.
                </p>
            </div>
        </div>
        <button type="button" onclick="document.getElementById('passwordModal').classList.remove('hidden')"
                class="shrink-0 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs sm:text-sm font-bold shadow-md shadow-amber-600/20 transition">
            <span>Kustomisasi Password Sekarang</span>
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
<?php endif; ?>

<!-- Identitas Pemilih Terverifikasi & Indikator Real-Time -->
<div class="bg-white rounded-3xl p-6 sm:p-7 border border-slate-200/80 shadow-sm mb-7">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5 pb-4 border-b border-slate-100">
        <div class="flex flex-wrap items-center gap-2.5">
            <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-600">Identitas Pemilih Terverifikasi</span>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Mahasiswa <?= e($student['status_mahasiswa']) ?>
            </span>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                Semester <?= e($student['status_semester']) ?>
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-slate-900 text-white">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Real-Time Sync</span>
                <span class="text-slate-400">&bull;</span>
                <span id="liveServerClock" data-server-ts="<?= time() * 1000 ?>" class="font-mono text-indigo-200"><?= date('H:i:s') ?> WIB</span>
            </span>

            <?php if ($totalOpenCount > 0): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    ● <?= $totalOpenCount ?> dari <?= $totalPeriodsCount ?> Periode Pemilihan Dibuka
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-rose-100 text-rose-800 border border-rose-300">
                    🔒 Semua Periode Pemilihan Ditutup (0 dari <?= $totalPeriodsCount ?> Dibuka)
                </span>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">
        <div class="lg:col-span-5 flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white font-extrabold text-xl flex items-center justify-center shadow-lg shadow-indigo-600/20 shrink-0">
                <?= e(initials((string) $student['nama'])) ?>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-extrabold text-slate-900 leading-tight"><?= e($student['nama']) ?></h1>
                <p class="text-sm text-slate-500 font-semibold mt-0.5">NIM: <span class="text-slate-800 font-mono"><?= e($student['nim']) ?></span></p>
            </div>
        </div>

        <div class="lg:col-span-7 grid grid-cols-2 sm:grid-cols-3 gap-3 text-xs">
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-medium block mb-0.5">Jenjang &amp; Prodi</span>
                <strong class="text-slate-800 font-bold"><?= e($student['jenjang']) ?> - <?= e($student['program_studi']) ?></strong>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-medium block mb-0.5">Periode Masuk</span>
                <strong class="text-slate-800 font-bold"><?= e($student['periode_masuk']) ?></strong>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 col-span-2 sm:col-span-1">
                <span class="text-slate-400 font-medium block mb-0.5">Progres Partisipasi</span>
                <?php if ($totalPeriodsCount > 0 && $totalVotedCount >= $totalPeriodsCount): ?>
                    <strong class="text-emerald-600 font-extrabold">SELESAI (<?= $totalVotedCount ?>/<?= $totalPeriodsCount ?> PEMILIHAN) ✓</strong>
                <?php elseif ($totalVotedCount > 0): ?>
                    <strong class="text-indigo-600 font-extrabold"><?= $totalVotedCount ?> DARI <?= $totalPeriodsCount ?> PEMILIHAN ✓</strong>
                <?php else: ?>
                    <strong class="text-amber-600 font-extrabold">BELUM MEMILIH (0/<?= $totalPeriodsCount ?>)</strong>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Daftar Seluruh Periode Pemilihan (Tampil Semua Secara Real-Time: Baik Terbuka Maupun Tertutup) -->
<div class="mb-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4">
        <div>
            <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-600">
                Daftar Periode Pemilihan Kampus (Real-Time)
            </span>
            <h2 class="text-lg sm:text-xl font-extrabold text-slate-900">
                Status Seluruh Periode Pemilihan (<?= $totalPeriodsCount ?> Periode Terdaftar)
            </h2>
            <p class="text-xs text-slate-500">
                Seluruh periode pemilihan ditampilkan di bawah ini secara real-time. Ketika periode dibuka Anda dapat langsung memilih, dan ketika ditutup status otomatis terkunci.
            </p>
        </div>

        <?php if ($totalPeriodsCount > 1): ?>
            <div class="flex flex-wrap items-center gap-1.5 bg-white p-1.5 rounded-2xl border border-slate-200/90 shadow-xs shrink-0">
                <button type="button" onclick="filterElectionBooth('all')" id="tabBtn-all"
                        class="booth-tab-btn px-3.5 py-2 rounded-xl text-xs font-extrabold transition <?= $requestedElectionId === 0 ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' ?>">
                    Tampilkan Semua (<?= $totalPeriodsCount ?>)
                </button>
                <?php foreach ($concurrentElections as $item): ?>
                    <?php $eidTab = (int) $item['election']['id']; ?>
                    <button type="button" onclick="filterElectionBooth(<?= $eidTab ?>)" id="tabBtn-<?= $eidTab ?>"
                            class="booth-tab-btn px-3.5 py-2 rounded-xl text-xs font-extrabold transition <?= $requestedElectionId === $eidTab ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100' ?>">
                        <?= e($item['election']['organization_name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($concurrentElections)): ?>
        <div class="bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 text-white rounded-3xl p-10 text-center shadow-xl">
            <p class="text-lg font-extrabold">Belum Ada Periode Pemilihan Terdaftar</p>
            <p class="text-xs text-slate-300 mt-1">Silakan tunggu pengumuman jadwal resmi dari Komisi Pemilihan Raya Mahasiswa (KPRM).</p>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 <?= count($concurrentElections) > 1 ? 'lg:grid-cols-2' : 'lg:grid-cols-1' ?> gap-5">
            <?php foreach ($concurrentElections as $item): ?>
                <?php
                $elCard       = $item['election'];
                $eidCard      = (int) $elCard['id'];
                $tStateCard   = $item['timeState'];
                $attCard      = $item['attendance'];
                $isOpenCard   = ($tStateCard['can_vote'] ?? false) === true;
                $hasVotedCard = ($attCard !== null);
                $candCount    = count($item['candidates'] ?? []);
                ?>
                <div class="rounded-3xl p-6 sm:p-7 text-white shadow-xl flex flex-col justify-between relative overflow-hidden border transition-all <?= $isOpenCard
                    ? 'bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 border-indigo-500/30'
                    : 'bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 border-slate-700/60' ?>">
                    <div class="absolute -right-12 -bottom-12 w-48 h-48 <?= $isOpenCard ? 'bg-indigo-500/15' : 'bg-rose-500/10' ?> rounded-full blur-2xl pointer-events-none"></div>

                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-white/10 text-indigo-200 border border-white/15">
                                <?= e($elCard['organization_name']) ?> &bull; <?= e($elCard['period_label']) ?>
                            </span>

                            <div class="flex items-center gap-2">
                                <?php if ($hasVotedCard): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-extrabold bg-emerald-500/25 text-emerald-200 border border-emerald-400/40">
                                        ✓ Sudah Memilih
                                    </span>
                                <?php endif; ?>

                                <?php if ($isOpenCard): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-400/40">
                                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                        <span>Sedang Berlangsung (Terbuka)</span>
                                    </span>
                                <?php elseif (($tStateCard['code'] ?? '') === 'before_start'): ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold bg-indigo-500/25 text-indigo-200 border border-indigo-400/40">
                                        <span class="w-2 h-2 rounded-full bg-indigo-400 animate-ping"></span>
                                        <span>⏳ <?= e($tStateCard['label']) ?></span>
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-extrabold bg-amber-500/20 text-amber-300 border border-amber-400/40">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                        <span><?= e($tStateCard['label']) ?></span>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <h3 class="text-lg sm:text-xl font-extrabold text-white leading-snug mb-1.5">
                            <?= e($elCard['title']) ?>
                        </h3>
                        <?php if (!empty($elCard['description'])): ?>
                            <p class="text-xs text-slate-300 leading-relaxed mb-4"><?= e($elCard['description']) ?></p>
                        <?php else: ?>
                            <p class="text-xs text-slate-400 mb-4">Pemilihan resmi <?= e($elCard['organization_name']) ?> periode <?= e($elCard['period_label']) ?>.</p>
                        <?php endif; ?>
                    </div>

                    <div class="space-y-3">
                        <div class="p-3.5 rounded-2xl bg-white/10 backdrop-blur-sm border border-white/10 text-xs space-y-1.5">
                            <div class="flex justify-between gap-2">
                                <span class="text-slate-300">Jadwal Buka:</span>
                                <strong class="text-white text-right"><?= format_indo_datetime($elCard['start_time']) ?></strong>
                            </div>
                            <div class="flex justify-between gap-2">
                                <span class="text-slate-300">Jadwal Tutup:</span>
                                <strong class="text-white text-right"><?= format_indo_datetime($elCard['end_time']) ?></strong>
                            </div>
                            <div class="pt-2 mt-1 border-t border-white/10 flex items-center justify-between gap-2">
                                <?php if ($isOpenCard): ?>
                                    <span class="text-indigo-200 font-semibold">Sisa Waktu Voting:</span>
                                    <span data-countdown-end="<?= (int) strtotime((string) $elCard['end_time']) * 1000 ?>"
                                          class="election-countdown font-mono font-extrabold text-emerald-300">
                                        Menghitung...
                                    </span>
                                <?php elseif (($tStateCard['code'] ?? '') === 'before_start'): ?>
                                    <span class="text-indigo-200 font-semibold">Dibuka Otomatis Dalam:</span>
                                    <span data-countdown-start="<?= (int) strtotime((string) $elCard['start_time']) * 1000 ?>"
                                          class="election-countdown font-mono font-extrabold text-indigo-300">
                                        Menghitung...
                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-300 font-semibold">Status Bilik Suara:</span>
                                    <span class="font-extrabold text-amber-300">
                                        🔒 <?= e($tStateCard['label']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-2 pt-1 text-xs">
                            <span class="text-slate-300 font-semibold">
                                <?= $candCount ?> Calon Ketua Terdaftar
                            </span>

                            <div class="flex items-center gap-2">
                                <?php if ($hasVotedCard): ?>
                                    <a href="<?= url('/student/receipt?code=' . urlencode((string) $attCard['receipt_code'])) ?>"
                                       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition shadow-md">
                                        <span>Cetak Bukti Pilih (<?= e($attCard['receipt_code']) ?>)</span>
                                    </a>
                                <?php endif; ?>

                                <a href="#booth-election-<?= $eidCard ?>"
                                   onclick="filterElectionBooth('all')"
                                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl <?= $isOpenCard && !$hasVotedCard ? 'bg-indigo-600 hover:bg-indigo-500 text-white' : 'bg-white/10 hover:bg-white/20 text-white border border-white/15' ?> font-extrabold text-xs transition">
                                    <span><?= ($isOpenCard && !$hasVotedCard) ? 'Buka Bilik Suara ↓' : 'Lihat Kandidat ↓' ?></span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Bilik Suara Digital untuk Setiap Periode Pemilihan -->
<div class="space-y-12">
    <?php foreach ($concurrentElections as $item): ?>
        <?php
        $elBooth          = $item['election'];
        $eidBooth         = (int) $elBooth['id'];
        $tStateBooth      = $item['timeState'];
        $candsBooth       = $item['candidates'] ?? [];
        $attBooth         = $item['attendance'];
        $pubResBooth      = $item['publishedResults'];
        $hasVotedBooth    = ($attBooth !== null);
        $isOpenBooth      = (($tStateBooth['can_vote'] ?? false) === true);
        $canVoteBoothNow  = $isOpenBooth && !$hasVotedBooth;
        $hiddenByFilter   = ($requestedElectionId > 0 && $requestedElectionId !== $eidBooth);
        ?>
        <section id="booth-election-<?= $eidBooth ?>"
                 data-booth-id="<?= $eidBooth ?>"
                 class="election-booth-section scroll-mt-24 bg-white/70 rounded-3xl p-5 sm:p-7 border border-slate-200/90 shadow-xs <?= $hiddenByFilter ? 'hidden' : '' ?>">

            <!-- Header Bilik Suara Periode Ini -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 mb-6 border-b border-slate-200/80">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-1.5">
                        <span class="px-3 py-1 rounded-xl bg-indigo-50 text-indigo-700 font-extrabold text-xs border border-indigo-200/60">
                            Bilik Suara Digital &bull; <?= e($elBooth['organization_name']) ?> (<?= e($elBooth['period_label']) ?>)
                        </span>

                        <?php if ($isOpenBooth): ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-300">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                                <span><?= e($tStateBooth['label']) ?></span>
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-extrabold bg-rose-100 text-rose-800 border border-rose-300">
                                🔒 <?= e($tStateBooth['label']) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                        Daftar Calon — <?= e($elBooth['title']) ?>
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">
                        Pelajari profil, visi, dan misi setiap kandidat sebelum mengonfirmasi pilihan Anda.
                    </p>
                </div>

                <div class="inline-flex items-center gap-2 px-3.5 py-2 rounded-2xl bg-indigo-50 border border-indigo-100 text-xs font-semibold text-indigo-800 shrink-0 self-start lg:self-center">
                    <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <span>Pilihan Anda dijamin 100% Rahasia (Secret Ballot)</span>
                </div>
            </div>

            <!-- Banner Bukti Partisipasi jika mahasiswa sudah memilih pada periode ini -->
            <?php if ($hasVotedBooth): ?>
                <div class="mb-6 p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <div class="w-12 h-12 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-white/20 text-white mb-1">
                                Hak Pilih Telah Digunakan &bull; <?= e($elBooth['organization_name']) ?>
                            </span>
                            <h3 class="text-base sm:text-lg font-extrabold">Terima Kasih Atas Partisipasi Anda!</h3>
                            <p class="text-xs text-emerald-50 mt-0.5">
                                Suara Anda pada <strong><?= e($elBooth['title']) ?></strong> tercatat pada <strong><?= format_indo_datetime($attBooth['voted_at']) ?></strong> dengan kode bukti:
                                <code class="px-2 py-0.5 rounded bg-black/20 font-mono font-bold text-white ml-1"><?= e($attBooth['receipt_code']) ?></code>
                            </p>
                        </div>
                    </div>

                    <a href="<?= url('/student/receipt?code=' . urlencode((string) $attBooth['receipt_code'])) ?>"
                       class="shrink-0 inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white text-emerald-800 hover:bg-emerald-50 font-extrabold text-xs shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Cetak Bukti Pilih</span>
                    </a>
                </div>
            <?php elseif (!$isOpenBooth): ?>
                <!-- Banner Pemberitahuan Periode Pemilihan Ditutup -->
                <div class="mb-6 p-4 sm:p-5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-900 flex items-center gap-3.5">
                    <div class="w-10 h-10 rounded-xl bg-rose-100 text-rose-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <div class="text-xs">
                        <p class="font-extrabold text-rose-900 text-sm">
                            <?= e($tStateBooth['label']) ?> — <?= e($elBooth['title']) ?>
                        </p>
                        <p class="text-rose-700 mt-0.5">
                            Pemberian suara pada periode pemilihan ini sedang ditutup (Jadwal: <?= format_indo_datetime($elBooth['start_time']) ?> s.d. <?= format_indo_datetime($elBooth['end_time']) ?>). Halaman ini akan otomatis terbuka secara real-time apabila diaktifkan kembali oleh Admin KPRM.
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Hasil Rekapitulasi Suara (Jika Dipublikasikan oleh Admin untuk Periode Ini) -->
            <?php if ($pubResBooth !== null): ?>
                <div class="mb-6 bg-indigo-50/50 rounded-2xl p-5 border border-indigo-100">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
                        <div>
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Pengumuman Resmi KPRM &bull; <?= e($elBooth['title']) ?></span>
                            <h3 class="text-base font-extrabold text-slate-900">Hasil Rekapitulasi Perolehan Suara</h3>
                        </div>
                        <span class="px-3 py-1 rounded-xl bg-white text-indigo-700 font-bold text-xs border border-indigo-200">
                            Total Suara Sah Masuk: <?= (int) $pubResBooth['total_votes'] ?> Suara
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <?php foreach ($pubResBooth['candidates'] as $candRes): ?>
                            <div class="p-4 rounded-2xl border border-slate-200 bg-white">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-900 text-white font-extrabold text-xs">
                                        No. Urut 0<?= (int) $candRes['candidate_number'] ?>
                                    </span>
                                    <span class="text-base font-extrabold text-indigo-600"><?= e($candRes['percentage']) ?>%</span>
                                </div>
                                <p class="font-bold text-slate-900 text-sm mb-0.5"><?= e($candRes['name']) ?></p>
                                <p class="text-xs text-slate-500 mb-3"><?= (int) $candRes['vote_count'] ?> Suara Sah</p>
                                <div class="w-full h-2.5 rounded-full bg-slate-200 overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-indigo-600 to-violet-600 rounded-full" style="width: <?= min(100, (float) $candRes['percentage']) ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Daftar Kandidat pada Periode Ini -->
            <?php if (empty($candsBooth)): ?>
                <div class="bg-slate-50 rounded-2xl p-10 text-center border border-slate-200/80">
                    <p class="text-sm font-bold text-slate-700">Data calon ketua belum tersedia untuk periode pemilihan <?= e($elBooth['title']) ?>.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($candsBooth as $cand): ?>
                        <?php
                        $num = (int) $cand['candidate_number'];
                        $missions = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $cand['mission']) ?: []));
                        ?>
                        <div class="bg-white rounded-3xl border border-slate-200/90 shadow-sm hover:shadow-xl hover:border-indigo-300 transition-all duration-300 flex flex-col justify-between overflow-hidden group">
                            <div>
                                <!-- Card Top Header / Avatar -->
                                <div class="relative bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 p-6 text-white">
                                    <div class="flex items-start justify-between gap-3 mb-4">
                                        <span class="inline-flex items-center justify-center px-3.5 py-1.5 rounded-2xl bg-white text-slate-900 font-extrabold text-sm shadow-md">
                                            Nomor Urut 0<?= $num ?>
                                        </span>
                                        <span class="px-2.5 py-1 rounded-xl bg-white/10 text-indigo-200 text-[11px] font-semibold border border-white/10">
                                            <?= e($cand['jenjang']) ?> - <?= e($cand['program_studi']) ?>
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <?php if (!empty($cand['photo_path'])): ?>
                                            <img src="<?= asset_url((string) $cand['photo_path']) ?>"
                                                 alt="<?= e($cand['name']) ?>"
                                                 class="w-20 h-20 rounded-2xl object-cover border-2 border-white/30 shadow-lg shrink-0">
                                        <?php else: ?>
                                            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white font-extrabold text-2xl flex items-center justify-center border-2 border-white/25 shadow-lg shrink-0">
                                                <?= e(initials((string) $cand['name'])) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h3 class="text-lg font-extrabold text-white leading-snug group-hover:text-indigo-200 transition">
                                                <?= e($cand['name']) ?>
                                            </h3>
                                            <p class="text-xs text-slate-300 font-medium mt-0.5">NIM: <?= e($cand['nim']) ?></p>
                                            <?php if (!empty($cand['tagline'])): ?>
                                                <p class="text-[11px] text-amber-300 italic mt-1.5 font-medium">"<?= e($cand['tagline']) ?>"</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vision & Mission Body -->
                                <div class="p-6 space-y-4">
                                    <div>
                                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-indigo-600 mb-1.5 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                                            <span>Visi Kandidat</span>
                                        </h4>
                                        <p class="text-xs sm:text-sm text-slate-700 leading-relaxed bg-slate-50 p-3.5 rounded-2xl border border-slate-100">
                                            <?= e($cand['vision']) ?>
                                        </p>
                                    </div>

                                    <div>
                                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-indigo-600 mb-1.5 flex items-center gap-1.5">
                                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                                            <span>Misi &amp; Program Kerja</span>
                                        </h4>
                                        <ul class="space-y-2 text-xs text-slate-600 leading-relaxed">
                                            <?php foreach ($missions as $m): ?>
                                                <li class="flex items-start gap-2">
                                                    <span class="text-indigo-600 font-bold mt-0.5">&bull;</span>
                                                    <span><?= e(preg_replace('/^\d+[\.\)]\s*/', '', $m)) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Button Footer -->
                            <div class="p-6 pt-0">
                                <?php if ($canVoteBoothNow): ?>
                                    <button type="button"
                                            onclick='openVoteModal(<?= json_encode([
                                                "electionId"    => $eidBooth,
                                                "electionTitle" => (string) $elBooth["title"],
                                                "candidateId"   => (int) $cand["id"],
                                                "numberLabel"   => "0" . $num,
                                                "candName"      => (string) $cand["name"],
                                                "candProdi"     => (string) ($cand["jenjang"] . " - " . $cand["program_studi"]),
                                            ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                            class="w-full py-3.5 px-5 rounded-2xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-sm shadow-lg shadow-indigo-600/20 hover:shadow-indigo-600/35 transition flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span>Pilih Nomor Urut 0<?= $num ?></span>
                                    </button>
                                <?php elseif ($hasVotedBooth): ?>
                                    <button type="button" disabled
                                            class="w-full py-3 px-5 rounded-2xl bg-emerald-50 text-emerald-700 font-extrabold text-xs cursor-not-allowed border border-emerald-200">
                                        ✓ Anda Sudah Memilih pada Periode Ini
                                    </button>
                                <?php else: ?>
                                    <button type="button" disabled
                                            class="w-full py-3 px-5 rounded-2xl bg-rose-50 text-rose-700 font-extrabold text-xs cursor-not-allowed border border-rose-200">
                                        🔒 <?= e($tStateBooth['label'] ?? 'Pemilihan Telah Ditutup') ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>

<!-- Modal Konfirmasi Pilihan Suara (Mendukung Multi-Periode Pemilihan) -->
<div id="voteConfirmModal" class="hidden fixed inset-0 z-50 bg-slate-900/70 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-8 shadow-2xl border border-slate-100">
        <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>

        <div class="text-center mb-5">
            <span id="modalElectionTitleBadge" class="text-xs font-bold uppercase tracking-wider text-indigo-600">
                Periode Pemilihan
            </span>
            <h3 class="text-xl font-extrabold text-slate-900 mt-1">Konfirmasi Pilihan Suara Anda</h3>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-center mb-5">
            <span id="modalCandNumber" class="inline-block px-3 py-1 rounded-full bg-indigo-600 text-white font-extrabold text-xs mb-2">
                Nomor Urut 01
            </span>
            <h4 id="modalCandName" class="text-lg font-extrabold text-slate-900">-</h4>
            <p id="modalCandProdi" class="text-xs text-slate-500 font-medium mt-0.5">-</p>
        </div>

        <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 text-xs leading-relaxed mb-6 flex items-start gap-2.5">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <span>
                <strong>Perhatian:</strong> Suara untuk <strong id="modalElectionTitleWarn">-</strong> yang telah dikonfirmasi bersifat <strong>final</strong> dan tidak dapat diubah kembali.
            </span>
        </div>

        <form action="<?= url('/student/vote') ?>" method="POST" class="grid grid-cols-2 gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="election_id" id="modalElectionId" value="">
            <input type="hidden" name="candidate_id" id="modalCandidateId" value="">

            <button type="button" onclick="closeVoteModal()"
                    class="py-3 px-4 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs sm:text-sm hover:bg-slate-50 transition">
                Kembali / Batal
            </button>
            <button type="submit"
                    class="py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs sm:text-sm shadow-lg shadow-indigo-600/25 transition">
                Ya, Kirim Suara Saya
            </button>
        </form>
    </div>
</div>

<script>
    window.__INITIAL_STATE_HASH__ = <?= json_encode($liveStateHash) ?>;
    window.__LIVE_STATUS_URL__ = <?= json_encode(url('/student/live-status')) ?>;

    function openVoteModal(payload) {
        document.getElementById('modalElectionId').value = payload.electionId;
        document.getElementById('modalCandidateId').value = payload.candidateId;
        document.getElementById('modalElectionTitleBadge').textContent = payload.electionTitle;
        document.getElementById('modalElectionTitleWarn').textContent = payload.electionTitle;
        document.getElementById('modalCandNumber').textContent = 'Nomor Urut ' + payload.numberLabel;
        document.getElementById('modalCandName').textContent = payload.candName;
        document.getElementById('modalCandProdi').textContent = payload.candProdi;
        document.getElementById('voteConfirmModal').classList.remove('hidden');
    }

    function closeVoteModal() {
        document.getElementById('voteConfirmModal').classList.add('hidden');
    }

    function filterElectionBooth(targetId) {
        const sections = document.querySelectorAll('.election-booth-section');
        sections.forEach(sec => {
            if (targetId === 'all' || Number(sec.getAttribute('data-booth-id')) === Number(targetId)) {
                sec.classList.remove('hidden');
            } else {
                sec.classList.add('hidden');
            }
        });

        document.querySelectorAll('.booth-tab-btn').forEach(btn => {
            btn.className = 'booth-tab-btn px-3.5 py-2 rounded-xl text-xs font-extrabold transition text-slate-600 hover:bg-slate-100';
        });
        const activeBtn = document.getElementById('tabBtn-' + targetId);
        if (activeBtn) {
            activeBtn.className = 'booth-tab-btn px-3.5 py-2 rounded-xl text-xs font-extrabold transition bg-indigo-600 text-white shadow-sm';
        }
    }

    (function() {
        const clockEl = document.getElementById('liveServerClock');
        const initialServerTs = clockEl ? Number(clockEl.getAttribute('data-server-ts') || Date.now()) : Date.now();
        const clientStartTs = Date.now();
        let isReloading = false;

        function getSyncedNow() {
            return initialServerTs + (Date.now() - clientStartTs);
        }

        function triggerRealtimeReload() {
            if (isReloading) return;
            isReloading = true;
            const toast = document.getElementById('realtimeToast');
            if (toast) toast.classList.remove('hidden');
            setTimeout(function() {
                window.location.reload();
            }, 600);
        }

        function formatDiff(diff) {
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            return `${d}h ${String(h).padStart(2, '0')}j ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}d`;
        }

        function tickCountdowns() {
            const now = getSyncedNow();
            if (clockEl) {
                const dt = new Date(now);
                const hh = String(dt.getHours()).padStart(2, '0');
                const mm = String(dt.getMinutes()).padStart(2, '0');
                const ss = String(dt.getSeconds()).padStart(2, '0');
                clockEl.textContent = `${hh}:${mm}:${ss} WIB`;
            }

            const startCountdowns = document.querySelectorAll('[data-countdown-start]');
            startCountdowns.forEach(el => {
                const startTs = Number(el.getAttribute('data-countdown-start') || 0);
                const diff = Math.max(0, startTs - now);
                if (diff <= 0) {
                    el.textContent = 'Jadwal Tiba — Membuka...';
                    pollLiveStatus();
                    return;
                }
                el.textContent = formatDiff(diff);
            });

            const countdowns = document.querySelectorAll('[data-countdown-end]');
            countdowns.forEach(el => {
                const endTs = Number(el.getAttribute('data-countdown-end') || 0);
                const diff = Math.max(0, endTs - now);
                if (diff <= 0) {
                    el.textContent = 'Waktu Habis — Menutup...';
                    pollLiveStatus();
                    return;
                }
                el.textContent = formatDiff(diff);
            });
        }

        async function pollLiveStatus() {
            if (isReloading || !window.__LIVE_STATUS_URL__) return;
            try {
                const res = await fetch(window.__LIVE_STATUS_URL__ + '?_t=' + Date.now(), {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data && data.state_hash && data.state_hash !== window.__INITIAL_STATE_HASH__) {
                    triggerRealtimeReload();
                }
            } catch (e) {
                // Ignore transient network errors
            }
        }

        tickCountdowns();
        setInterval(tickCountdowns, 1000);
        setInterval(pollLiveStatus, 3000);
    })();
</script>
