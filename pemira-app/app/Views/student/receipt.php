<?php declare(strict_types=1); ?>

<div class="max-w-2xl mx-auto">
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3 no-print">
        <a href="<?= url('/student/dashboard' . (!empty($attendance['election_id']) ? '?election_id=' . (int) $attendance['election_id'] : '')) ?>" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-indigo-600 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <span>Kembali ke Dashboard Mahasiswa</span>
        </a>

        <div class="flex flex-wrap items-center gap-2">
            <?php if (!empty($nextUnvotedElection)): ?>
                <a href="<?= url('/student/dashboard?election_id=' . (int) $nextUnvotedElection['id']) ?>"
                   class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md transition">
                    <span>Lanjut Memilih: <?= e($nextUnvotedElection['title']) ?></span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
            <?php endif; ?>

            <button type="button" onclick="window.print()"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold shadow-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Cetak / Simpan PDF</span>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-3xl border border-slate-200 shadow-xl overflow-hidden">
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white p-8 text-center relative">
            <div class="w-16 h-16 rounded-2xl bg-emerald-500/20 border border-emerald-400/40 text-emerald-300 flex items-center justify-center mx-auto mb-4">
                <svg class="w-9 h-9" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase tracking-wider bg-white/10 text-indigo-200 border border-white/15">
                Tanda Bukti Sah Partisipasi Pemilihan
            </span>
            <h1 class="text-2xl font-extrabold mt-3"><?= e($attendance['election_title']) ?></h1>
            <p class="text-xs text-slate-300 mt-1">Periode <?= e($attendance['period_label']) ?> &bull; Komisi Pemilihan Raya Mahasiswa</p>
        </div>

        <!-- Receipt Body -->
        <div class="p-8 space-y-6">
            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 text-center">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-1">
                    Kode Hash Bukti Partisipasi Digital
                </span>
                <p class="font-mono text-xl sm:text-2xl font-extrabold text-indigo-600 tracking-wider">
                    <?= e($attendance['receipt_code']) ?>
                </p>
            </div>

            <div class="divide-y divide-slate-100 text-sm">
                <div class="py-3 flex justify-between gap-4">
                    <span class="text-slate-500">Nama Lengkap Pemilih</span>
                    <strong class="text-slate-900 text-right"><?= e($attendance['nama']) ?></strong>
                </div>
                <div class="py-3 flex justify-between gap-4">
                    <span class="text-slate-500">Nomor Induk Mahasiswa (NIM)</span>
                    <strong class="text-slate-900 text-right"><?= e($attendance['nim']) ?></strong>
                </div>
                <div class="py-3 flex justify-between gap-4">
                    <span class="text-slate-500">Jenjang & Program Studi</span>
                    <strong class="text-slate-900 text-right"><?= e($attendance['jenjang']) ?> - <?= e($attendance['program_studi']) ?></strong>
                </div>
                <div class="py-3 flex justify-between gap-4">
                    <span class="text-slate-500">Waktu Transaksi Suara</span>
                    <strong class="text-slate-900 text-right"><?= format_indo_datetime($attendance['voted_at']) ?></strong>
                </div>
                <div class="py-3 flex justify-between gap-4">
                    <span class="text-slate-500">Status Suara</span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        TERCATAT & TERENKRIPSI (SECRET BALLOT)
                    </span>
                </div>
            </div>

            <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 text-xs text-indigo-900 leading-relaxed">
                <strong>Catatan Privasi (Secret Ballot):</strong> Demi menjaga kerahasiaan suara Anda sesuai asas Luber Jurdil, tanda bukti ini hanya mencatat kehadiran partisipasi Anda dan <strong>tidak menampilkan pilihan kandidat</strong> yang telah Anda pilih.
            </div>
        </div>
    </div>
</div>

