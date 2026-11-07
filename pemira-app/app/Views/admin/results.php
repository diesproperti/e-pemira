<?php
declare(strict_types=1);

$candidates = $results['candidates'] ?? [];
$totalVotes = (int) ($results['total_votes'] ?? 0);
$electionId = (int) ($election['id'] ?? 0);

// Integritas: Bandingkan jumlah kehadiran di voter_attendances dan jumlah suara di votes
$integrityMatched = ((int) $stats['total_voted'] === $totalVotes);
?>

<!-- Top Bar: Pilih Periode & Tombol Export / Print (Bab 12 & 12.1) -->
<div class="mb-6 bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex flex-col xl:flex-row xl:items-center justify-between gap-4">
    <div>
        <div class="flex flex-wrap items-center gap-2 mb-1">
            <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Rekapitulasi & Pelaporan Resmi</span>
            <?php if ($integrityMatched): ?>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    ✓ Integritas Kotak Suara Terverifikasi (<?= $totalVotes ?>/<?= (int) $stats['total_voted'] ?>)
                </span>
            <?php endif; ?>
        </div>
        <h3 class="text-lg sm:text-xl font-extrabold text-slate-900"><?= e($election['title'] ?? 'Hasil Pemilihan') ?></h3>
        <?php if ($election): ?>
            <p class="text-xs text-slate-500 mt-0.5">
                Waktu Pelaksanaan: <strong><?= format_indo_datetime($election['start_time']) ?></strong> s.d. <strong><?= format_indo_datetime($election['end_time']) ?></strong>
            </p>
        <?php endif; ?>
    </div>

    <div class="grid grid-cols-1 sm:flex sm:flex-wrap items-center gap-2.5">
        <form action="<?= url('/admin/results') ?>" method="GET" class="w-full sm:w-auto">
            <select name="election_id" onchange="this.form.submit()" class="w-full sm:w-auto px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold bg-white">
                <?php foreach ($elections as $el): ?>
                    <option value="<?= (int) $el['id'] ?>" <?= $electionId === (int) $el['id'] ? 'selected' : '' ?>>
                        <?= e($el['title']) ?> (<?= e($el['period_label']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <a href="<?= url('/admin/results/export-csv?election_id=' . $electionId) ?>"
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-sm transition">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span>Export Excel / CSV</span>
        </a>

        <a href="<?= url('/admin/results/print?election_id=' . $electionId) ?>" target="_blank"
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold shadow-sm transition">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            <span>Cetak Berita Acara / PDF</span>
        </a>
    </div>
</div>

<!-- Ringkasan Statistik Pemilihan (Bab 12 PRD) -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6">
    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
        <span class="text-[11px] font-bold text-slate-400 uppercase block">Pemilih Berhak Pilih</span>
        <strong class="text-xl sm:text-2xl font-extrabold text-slate-900 mt-1 block"><?= number_format((int) $stats['total_eligible']) ?></strong>
    </div>
    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
        <span class="text-[11px] font-bold text-emerald-600 uppercase block">Sudah Memilih</span>
        <strong class="text-xl sm:text-2xl font-extrabold text-emerald-600 mt-1 block"><?= number_format((int) $stats['total_voted']) ?></strong>
    </div>
    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
        <span class="text-[11px] font-bold text-amber-600 uppercase block">Belum Memilih</span>
        <strong class="text-xl sm:text-2xl font-extrabold text-amber-600 mt-1 block"><?= number_format((int) $stats['total_not_voted']) ?></strong>
    </div>
    <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
        <span class="text-[11px] font-bold text-indigo-600 uppercase block">Suara Sah Masuk</span>
        <strong class="text-xl sm:text-2xl font-extrabold text-indigo-600 mt-1 block"><?= number_format($totalVotes) ?> (<?= e($stats['turnout_percent']) ?>%)</strong>
    </div>
</div>

<!-- Tabel & Kartu Perolehan Suara Per Calon (AC-05) -->
<div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm mb-6">
    <h4 class="text-base font-extrabold text-slate-900 mb-4">Rincian Perolehan Suara per Calon Ketua</h4>

    <!-- Mobile View (< md) -->
    <div class="md:hidden space-y-3">
        <?php foreach ($candidates as $cand): ?>
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="px-2.5 py-1 rounded-xl bg-slate-900 text-white font-extrabold text-xs">
                        No. Urut 0<?= (int) $cand['candidate_number'] ?>
                    </span>
                    <div class="text-right">
                        <span class="text-sm font-extrabold text-slate-900"><?= number_format((int) $cand['vote_count']) ?> Suara</span>
                        <span class="text-xs font-extrabold text-indigo-600 ml-1">(<?= e($cand['percentage']) ?>%)</span>
                    </div>
                </div>
                <div>
                    <h5 class="font-extrabold text-slate-900 text-sm"><?= e($cand['name']) ?></h5>
                    <p class="text-xs text-slate-500"><?= e($cand['jenjang']) ?> - <?= e($cand['program_studi']) ?> &bull; NIM: <?= e($cand['nim']) ?></p>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-200 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-indigo-600 to-violet-600 rounded-full" style="width: <?= min(100, (float) $cand['percentage']) ?>%"></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Desktop Table (md+) -->
    <div class="hidden md:block overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">
                    <th class="py-3.5 px-4">Nomor Urut</th>
                    <th class="py-3.5 px-4">Nama Calon Ketua</th>
                    <th class="py-3.5 px-4">NIM</th>
                    <th class="py-3.5 px-4">Jenjang & Program Studi</th>
                    <th class="py-3.5 px-4 text-right">Jumlah Suara</th>
                    <th class="py-3.5 px-4 text-right">Persentase (%)</th>
                    <th class="py-3.5 px-4 w-48">Proporsi Visual</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                <?php foreach ($candidates as $cand): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="py-4 px-4">
                            <span class="px-3 py-1 rounded-xl bg-slate-900 text-white font-extrabold">
                                0<?= (int) $cand['candidate_number'] ?>
                            </span>
                        </td>
                        <td class="py-4 px-4 font-extrabold text-slate-900 text-sm"><?= e($cand['name']) ?></td>
                        <td class="py-4 px-4 font-mono text-slate-600"><?= e($cand['nim']) ?></td>
                        <td class="py-4 px-4 text-slate-600"><?= e($cand['jenjang']) ?> - <?= e($cand['program_studi']) ?></td>
                        <td class="py-4 px-4 text-right font-extrabold text-slate-900 text-sm"><?= number_format((int) $cand['vote_count']) ?> Suara</td>
                        <td class="py-4 px-4 text-right font-extrabold text-indigo-600 text-sm"><?= e($cand['percentage']) ?>%</td>
                        <td class="py-4 px-4">
                            <div class="w-full h-3 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-indigo-600 to-violet-600 rounded-full" style="width: <?= min(100, (float) $cand['percentage']) ?>%"></div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Rekapitulasi Partisipasi per Program Studi -->
<div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
    <h4 class="text-base font-extrabold text-slate-900 mb-4">Rekapitulasi Tingkat Partisipasi per Program Studi</h4>
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[480px]">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">
                    <th class="py-3 px-4">Jenjang</th>
                    <th class="py-3 px-4">Program Studi</th>
                    <th class="py-3 px-4 text-right">Pemilih Aktif (DPT)</th>
                    <th class="py-3 px-4 text-right">Sudah Memilih</th>
                    <th class="py-3 px-4 text-right">Belum Memilih</th>
                    <th class="py-3 px-4 text-right">Tingkat Partisipasi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
                <?php foreach ($prodiTurnout as $pt): ?>
                    <tr>
                        <td class="py-3 px-4 font-bold text-slate-700"><?= e($pt['jenjang']) ?></td>
                        <td class="py-3 px-4 font-bold text-slate-900"><?= e($pt['program_studi']) ?></td>
                        <td class="py-3 px-4 text-right font-semibold"><?= (int) $pt['total_eligible'] ?></td>
                        <td class="py-3 px-4 text-right font-bold text-emerald-600"><?= (int) $pt['total_voted'] ?></td>
                        <td class="py-3 px-4 text-right font-bold text-amber-600"><?= (int) $pt['total_not_voted'] ?></td>
                        <td class="py-3 px-4 text-right font-extrabold text-indigo-600"><?= e($pt['percentage']) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
