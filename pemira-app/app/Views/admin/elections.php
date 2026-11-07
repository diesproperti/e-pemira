<?php
declare(strict_types=1);

$activeCount = 0;
$adminHashItems = [];
foreach ($elections as $eItem) {
    $ts = \App\Models\Election::getTimeState($eItem);
    if ($ts['can_vote']) {
        $activeCount++;
    }
    $adminHashItems[] = [
        'id'              => (int) $eItem['id'],
        'title'           => (string) $eItem['title'],
        'status'          => (string) $eItem['status'],
        'state_code'      => (string) $ts['code'],
        'state_label'     => (string) $ts['label'],
        'can_vote'        => (bool) $ts['can_vote'],
        'start_time'      => (string) $eItem['start_time'],
        'end_time'        => (string) $eItem['end_time'],
        'end_ts_ms'       => (int) strtotime((string) $eItem['end_time']) * 1000,
        'candidate_count' => (int) ($eItem['candidate_count'] ?? 0),
        'vote_count'      => (int) ($eItem['vote_count'] ?? 0),
        'published'       => (int) ($eItem['is_result_published'] ?? 0),
    ];
}
$adminInitialHash = md5((string) json_encode($adminHashItems));
?>

<!-- Real-Time Sync Toast Notification -->
<div id="adminRealtimeToast" class="hidden fixed bottom-5 right-5 z-50 max-w-sm bg-slate-900 text-white px-5 py-4 rounded-2xl shadow-2xl border border-indigo-500/40 flex items-center gap-3">
    <span class="w-3 h-3 rounded-full bg-emerald-400 animate-ping shrink-0"></span>
    <div class="text-xs">
        <p class="font-extrabold text-white">Sinkronisasi Jadwal Real-Time</p>
        <p class="text-slate-300 mt-0.5">Status periode pemilihan diperbarui otomatis...</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
    <!-- Form Buat / Edit Periode Pemilihan (FR-08) -->
    <div class="lg:col-span-5 lg:sticky lg:top-24">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Konfigurasi Jadwal</span>
                    <h3 class="text-base font-extrabold text-slate-900">
                        <?= $editElection ? 'Edit Periode Pemilihan' : 'Buat Periode Pemilihan Baru' ?>
                    </h3>
                </div>
                <?php if ($editElection): ?>
                    <a href="<?= url('/admin/elections') ?>" class="px-3 py-1.5 rounded-xl bg-indigo-50 text-indigo-700 text-xs font-bold hover:bg-indigo-100 transition">
                        + Buat Baru
                    </a>
                <?php endif; ?>
            </div>

            <form action="<?= url($editElection ? '/admin/elections/update' : '/admin/elections/store') ?>" method="POST" class="space-y-3.5 text-xs">
                <?= csrf_field() ?>
                <?php if ($editElection): ?>
                    <input type="hidden" name="id" value="<?= (int) $editElection['id'] ?>">
                <?php endif; ?>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Nama Pemilihan</label>
                    <input type="text" name="title" required
                           placeholder="Contoh: Pemilihan Ketua BEM Universitas"
                           value="<?= e($editElection['title'] ?? '') ?>"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Organisasi</label>
                        <input type="text" name="organization_name" required
                               placeholder="Contoh: BEM Universitas"
                               value="<?= e($editElection['organization_name'] ?? 'BEM Universitas') ?>"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Label Periode</label>
                        <input type="text" name="period_label" required
                               placeholder="Contoh: 2026/2027"
                               value="<?= e($editElection['period_label'] ?? '2026/2027') ?>"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Jadwal Mulai (WIB)</label>
                        <input type="datetime-local" name="start_time" required
                               value="<?= e(isset($editElection['start_time']) ? date('Y-m-d\TH:i', strtotime((string) $editElection['start_time'])) : date('Y-m-d\TH:i')) ?>"
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-300">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Jadwal Selesai (WIB)</label>
                        <input type="datetime-local" name="end_time" required
                               value="<?= e(isset($editElection['end_time']) ? date('Y-m-d\TH:i', strtotime((string) $editElection['end_time'])) : date('Y-m-d\TH:i', strtotime('+3 days'))) ?>"
                               class="w-full px-3 py-2.5 rounded-xl border border-slate-300">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Status Periode Pemilihan</label>
                    <select name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white font-semibold">
                        <option value="active" <?= (($editElection['status'] ?? 'active') !== 'draft') ? 'selected' : '' ?>>Active (Otomatis Buka &amp; Tutup Sesuai Jadwal Real-Time)</option>
                        <option value="draft" <?= ($editElection['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft (Persiapan)</option>
                        <option value="closed" <?= false ? 'selected' : '' ?>>Closed (Tutup Paksa Sekarang)</option>
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">
                        Sistem akan <strong>otomatis membuka pemilihan tepat saat Jadwal Mulai tiba</strong> dan <strong>otomatis menutupnya saat Jadwal Selesai berakhir</strong> secara real-time.
                    </p>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Deskripsi / Pengumuman Pemilihan</label>
                    <textarea name="description" rows="3" placeholder="Penjelasan singkat mengenai pelaksanaan pemilihan..."
                              class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300"><?= e($editElection['description'] ?? '') ?></textarea>
                </div>

                <label class="flex items-center gap-2.5 p-3 rounded-2xl bg-slate-50 border border-slate-200 cursor-pointer">
                    <input type="checkbox" name="is_result_published" value="1"
                           <?= !empty($editElection['is_result_published']) ? 'checked' : '' ?>
                           class="w-4 h-4 rounded text-indigo-600">
                    <span class="font-semibold text-slate-700">Publikasikan hasil perolehan suara ke halaman mahasiswa</span>
                </label>

                <button type="submit" class="w-full py-3 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs shadow-md transition">
                    <?= $editElection ? 'Simpan Perubahan Periode' : 'Buat Periode Pemilihan' ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Daftar Periode Pemilihan -->
    <div class="lg:col-span-7 space-y-4">
        <!-- Header Ringkasan & Kontrol Pilih Semua / Aktifkan & Tutup Semua / Hapus Massal -->
        <div class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-sm space-y-3">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <?php if (!empty($elections)): ?>
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none text-xs font-bold text-slate-700">
                            <input type="checkbox" id="selectAllElectionsCheckbox" onchange="toggleSelectAllElections(this.checked)"
                                   class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <span>Pilih Semua (<?= count($elections) ?> Periode)</span>
                        </label>
                    <?php else: ?>
                        <span class="text-xs font-bold text-slate-700">Daftar Periode Pemilihan</span>
                    <?php endif; ?>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-900 text-white">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Real-Time</span>
                        <span id="adminLiveClock" data-server-ts="<?= time() * 1000 ?>" class="font-mono text-indigo-200"><?= date('H:i:s') ?> WIB</span>
                    </span>

                    <?php if ($activeCount > 0): ?>
                        <span class="px-3 py-1 rounded-full text-[11px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            ● <?= $activeCount ?> dari <?= count($elections) ?> Periode Aktif Bersamaan
                        </span>
                    <?php else: ?>
                        <span class="px-3 py-1 rounded-full text-[11px] font-extrabold bg-rose-50 text-rose-700 border border-rose-200">
                            🔒 Semua Periode Ditutup (0 Aktif)
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (count($elections) > 1): ?>
                <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                    <span class="text-[11px] font-bold text-slate-500">Kontrol Serentak Seluruh Periode:</span>
                    <div class="flex flex-wrap items-center gap-2">
                        <form action="<?= url('/admin/elections/status-all') ?>" method="POST" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="active">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-[11px] shadow-xs transition">
                                <span>● Aktifkan Semua Periode (<?= count($elections) ?>)</span>
                            </button>
                        </form>

                        <form action="<?= url('/admin/elections/status-all') ?>" method="POST" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="status" value="closed">
                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-[11px] shadow-xs transition">
                                <span>🔒 Tutup Semua Periode (<?= count($elections) ?>)</span>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bar Aksi Hapus Massal (Muncul saat ada checkbox periode yang dicentang) -->
        <div id="electionBulkActionBar" class="hidden bg-slate-900 text-white rounded-2xl p-4 shadow-xl border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <span id="selectedElectionCountBadge" class="w-8 h-8 rounded-xl bg-rose-600 text-white font-extrabold text-xs flex items-center justify-center">
                    0
                </span>
                <div>
                    <p class="text-xs font-extrabold">Periode Pemilihan Dipilih</p>
                    <p class="text-[11px] text-slate-400">Hapus beberapa jadwal pemilihan sekaligus beserta data kandidat &amp; suaranya</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" onclick="toggleSelectAllElections(false)"
                        class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                    Batal Pilih
                </button>
                <button type="button" onclick="openBulkDeleteElectionModal()"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-extrabold shadow-md transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Hapus Periode Terpilih</span>
                </button>
            </div>
        </div>

        <?php if (empty($elections)): ?>
            <div class="bg-white rounded-3xl p-10 text-center border border-slate-200/80 shadow-sm">
                <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-500 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h4 class="text-sm font-extrabold text-slate-900">Belum Ada Periode Pemilihan</h4>
                <p class="text-xs text-slate-500 mt-1">Silakan buat periode pemilihan baru melalui formulir di sebelah kiri.</p>
            </div>
        <?php endif; ?>

        <?php foreach ($elections as $el): ?>
            <?php
                $tState = \App\Models\Election::getTimeState($el);
                $isEditingThis = ($editElection && (int) $editElection['id'] === (int) $el['id']);
            ?>
            <div class="election-card bg-white rounded-3xl p-5 sm:p-6 border <?= $isEditingThis ? 'border-indigo-500 ring-2 ring-indigo-500/15' : 'border-slate-200/80' ?> shadow-sm transition">
                <div class="flex flex-wrap items-start justify-between gap-3 mb-3">
                    <div class="flex items-start gap-3">
                        <input type="checkbox"
                               value="<?= (int) $el['id'] ?>"
                               data-title="<?= e($el['title']) ?>"
                               onchange="syncElectionSelection()"
                               class="election-checkbox mt-1 w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="px-2.5 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-[11px]">
                                    <?= e($el['organization_name']) ?> &bull; <?= e($el['period_label']) ?>
                                </span>
                                <?php if ($tState['can_vote']): ?>
                                    <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[11px]">
                                        ● <?= e($tState['label']) ?>
                                    </span>
                                <?php elseif ($tState['code'] === 'before_start'): ?>
                                    <span class="px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-800 font-bold text-[11px]">
                                        ⏳ <?= e($tState['label']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 font-bold text-[11px]">
                                        <?= e($tState['label']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h4 class="text-base font-extrabold text-slate-900"><?= e($el['title']) ?></h4>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5 shrink-0">
                        <a href="<?= url('/admin/elections?edit_id=' . (int) $el['id']) ?>"
                           class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 font-bold text-xs transition">
                            Edit Jadwal
                        </a>
                        <button type="button"
                                onclick='openSingleDeleteElectionModal(<?= json_encode([
                                    "id"         => (int) $el["id"],
                                    "title"      => (string) $el["title"],
                                    "org"        => (string) $el["organization_name"],
                                    "period"     => (string) $el["period_label"],
                                    "candidates" => (int) $el["candidate_count"],
                                    "votes"      => (int) $el["vote_count"],
                                ], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200/80 font-bold text-xs transition"
                                title="Hapus Periode Pemilihan">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            <span>Hapus</span>
                        </button>
                    </div>
                </div>

                <?php if (!empty($el['description'])): ?>
                    <p class="text-xs text-slate-500 mb-4"><?= e($el['description']) ?></p>
                <?php endif; ?>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-100 text-xs mb-4">
                    <div>
                        <span class="text-slate-400 block text-[11px]">Waktu Mulai</span>
                        <strong class="text-slate-800"><?= format_indo_datetime($el['start_time']) ?></strong>
                        <?php if ($tState['code'] === 'before_start'): ?>
                            <span data-admin-countdown-start="<?= (int) strtotime((string) $el['start_time']) * 1000 ?>"
                                  class="block font-mono text-[11px] font-extrabold text-indigo-600 mt-0.5">
                                Buka dlm: Menghitung...
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Waktu Selesai</span>
                        <strong class="text-slate-800"><?= format_indo_datetime($el['end_time']) ?></strong>
                        <?php if ($tState['can_vote']): ?>
                            <span data-admin-countdown="<?= (int) strtotime((string) $el['end_time']) * 1000 ?>"
                                  class="block font-mono text-[11px] font-extrabold text-emerald-600 mt-0.5">
                                Sisa: Menghitung...
                            </span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Kandidat</span>
                        <strong class="text-slate-800"><?= (int) $el['candidate_count'] ?> Calon</strong>
                    </div>
                    <div>
                        <span class="text-slate-400 block text-[11px]">Suara Masuk</span>
                        <strong class="text-indigo-600"><?= (int) $el['vote_count'] ?> Suara</strong>
                    </div>
                </div>

                <!-- Quick Controls -->
                <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 text-xs">
                    <div class="flex flex-wrap gap-2">
                        <?php if ($el['status'] !== 'active'): ?>
                            <form action="<?= url('/admin/elections/status') ?>" method="POST" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $el['id'] ?>">
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-sm transition">
                                    Aktifkan Periode
                                </button>
                            </form>
                        <?php else: ?>
                            <form action="<?= url('/admin/elections/status') ?>" method="POST" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="<?= (int) $el['id'] ?>">
                                <input type="hidden" name="status" value="closed">
                                <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold shadow-sm transition">
                                    Tutup Voting Sekarang
                                </button>
                            </form>
                        <?php endif; ?>

                        <form action="<?= url('/admin/elections/publish') ?>" method="POST" class="inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $el['id'] ?>">
                            <button type="submit" class="px-3 py-1.5 rounded-xl border border-slate-300 hover:bg-slate-50 font-bold text-slate-700 transition">
                                <?= ((int) $el['is_result_published'] === 1) ? 'Sembunyikan Hasil dari Mahasiswa' : 'Publikasikan Hasil ke Mahasiswa' ?>
                            </button>
                        </form>
                    </div>

                    <a href="<?= url('/admin/candidates?election_id=' . (int) $el['id']) ?>" class="font-bold text-indigo-600 hover:underline">
                        Kelola Kandidat &rarr;
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Satu Periode Pemilihan -->
<div id="deleteSingleElectionModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-200">
        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>

        <h3 class="text-lg font-extrabold text-slate-900">Hapus Periode Pemilihan?</h3>
        <p class="text-xs text-slate-500 mt-1">
            Anda akan menghapus secara permanen periode pemilihan berikut:
        </p>

        <div class="my-4 p-4 rounded-2xl bg-slate-50 border border-slate-200/80 text-xs space-y-1.5">
            <div class="font-extrabold text-slate-900 text-sm" id="delElectionTitle">-</div>
            <div class="text-slate-600 font-semibold" id="delElectionOrg">-</div>
            <div class="pt-2 mt-2 border-t border-slate-200/80 flex items-center justify-between text-[11px]">
                <span class="text-slate-500">Data Kandidat Terkait: <strong id="delElectionCandidates" class="text-slate-800">0 Calon</strong></span>
                <span class="text-slate-500">Data Suara Masuk: <strong id="delElectionVotes" class="text-rose-600">0 Suara</strong></span>
            </div>
        </div>

        <p class="text-[11px] text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-3 mb-5">
            <strong>Perhatian:</strong> Seluruh data calon ketua, daftar hadir pemilih, dan perolehan suara pada periode ini juga akan ikut dihapus.
        </p>

        <form action="<?= url('/admin/elections/delete') ?>" method="POST" class="flex gap-3">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="delElectionIdInput" value="">
            <button type="button" onclick="document.getElementById('deleteSingleElectionModal').classList.add('hidden')"
                    class="w-1/2 py-2.5 px-4 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-50 transition">
                Batal
            </button>
            <button type="submit"
                    class="w-1/2 py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs shadow-md transition">
                Ya, Hapus Periode
            </button>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Massal Periode Pemilihan -->
<div id="deleteBulkElectionModal" class="hidden fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border border-slate-200">
        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>

        <h3 class="text-lg font-extrabold text-slate-900">Hapus <span id="bulkDelElectionCountText">0</span> Periode Terpilih?</h3>
        <p class="text-xs text-slate-500 mt-1 mb-4">
            Seluruh periode pemilihan yang Anda centang beserta data kandidat dan kotak suara di dalamnya akan dihapus secara permanen.
        </p>

        <form action="<?= url('/admin/elections/bulk-delete') ?>" method="POST">
            <?= csrf_field() ?>
            <div id="bulkDeleteElectionHiddenInputs"></div>

            <div class="flex gap-3">
                <button type="button" onclick="document.getElementById('deleteBulkElectionModal').classList.add('hidden')"
                        class="w-1/2 py-2.5 px-4 rounded-xl border border-slate-300 text-slate-700 font-bold text-xs hover:bg-slate-50 transition">
                    Batal
                </button>
                <button type="submit"
                        class="w-1/2 py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs shadow-md transition">
                    Ya, Hapus Semua Terpilih
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSingleDeleteElectionModal(data) {
        document.getElementById('delElectionIdInput').value = data.id;
        document.getElementById('delElectionTitle').textContent = data.title;
        document.getElementById('delElectionOrg').textContent = data.org + ' • Periode ' + data.period;
        document.getElementById('delElectionCandidates').textContent = data.candidates + ' Calon';
        document.getElementById('delElectionVotes').textContent = data.votes + ' Suara';
        document.getElementById('deleteSingleElectionModal').classList.remove('hidden');
    }

    function syncElectionSelection() {
        const boxes = Array.from(document.querySelectorAll('.election-checkbox'));
        const checked = boxes.filter(cb => cb.checked);
        const bar = document.getElementById('electionBulkActionBar');
        const badge = document.getElementById('selectedElectionCountBadge');
        const master = document.getElementById('selectAllElectionsCheckbox');

        if (badge) badge.textContent = String(checked.length);
        if (bar) {
            if (checked.length > 0) {
                bar.classList.remove('hidden');
            } else {
                bar.classList.add('hidden');
            }
        }
        if (master && boxes.length > 0) {
            master.checked = checked.length === boxes.length;
        }
    }

    function toggleSelectAllElections(state) {
        document.querySelectorAll('.election-checkbox').forEach(cb => {
            cb.checked = state;
        });
        syncElectionSelection();
    }

    function openBulkDeleteElectionModal() {
        const checked = Array.from(document.querySelectorAll('.election-checkbox:checked'));
        if (checked.length === 0) return;

        document.getElementById('bulkDelElectionCountText').textContent = String(checked.length);
        const container = document.getElementById('bulkDeleteElectionHiddenInputs');
        container.innerHTML = '';
        checked.forEach(cb => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'election_ids[]';
            input.value = cb.value;
            container.appendChild(input);
        });

        document.getElementById('deleteBulkElectionModal').classList.remove('hidden');
    }

    (function() {
        const initialHash = <?= json_encode($adminInitialHash) ?>;
        const liveUrl = <?= json_encode(url('/admin/elections/live-status')) ?>;
        const clockEl = document.getElementById('adminLiveClock');
        const initialServerTs = clockEl ? Number(clockEl.getAttribute('data-server-ts') || Date.now()) : Date.now();
        const clientStartTs = Date.now();
        let isReloading = false;

        function getSyncedNow() {
            return initialServerTs + (Date.now() - clientStartTs);
        }

        function triggerReload(force = false) {
            if (isReloading) return;
            if (!force) {
                const activeTag = document.activeElement ? document.activeElement.tagName : '';
                if (activeTag === 'INPUT' || activeTag === 'TEXTAREA' || activeTag === 'SELECT') {
                    return;
                }
            }
            isReloading = true;
            const toast = document.getElementById('adminRealtimeToast');
            if (toast) toast.classList.remove('hidden');
            setTimeout(function() {
                window.location.reload();
            }, 400);
        }

        function formatCountdownDiff(diff) {
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            return `${d}h ${String(h).padStart(2, '0')}j ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}d`;
        }

        function tickAdminClock() {
            const now = getSyncedNow();
            if (clockEl) {
                const dt = new Date(now);
                const hh = String(dt.getHours()).padStart(2, '0');
                const mm = String(dt.getMinutes()).padStart(2, '0');
                const ss = String(dt.getSeconds()).padStart(2, '0');
                clockEl.textContent = `${hh}:${mm}:${ss} WIB`;
            }

            document.querySelectorAll('[data-admin-countdown-start]').forEach(el => {
                const startTs = Number(el.getAttribute('data-admin-countdown-start') || 0);
                const diff = Math.max(0, startTs - now);
                if (diff <= 0) {
                    el.textContent = 'Jadwal Tiba — Membuka...';
                    pollAdminLiveStatus(true);
                    return;
                }
                el.textContent = 'Buka dlm: ' + formatCountdownDiff(diff);
            });

            document.querySelectorAll('[data-admin-countdown]').forEach(el => {
                const endTs = Number(el.getAttribute('data-admin-countdown') || 0);
                const diff = Math.max(0, endTs - now);
                if (diff <= 0) {
                    el.textContent = 'Waktu Habis — Menutup...';
                    pollAdminLiveStatus(true);
                    return;
                }
                el.textContent = 'Sisa: ' + formatCountdownDiff(diff);
            });
        }

        async function pollAdminLiveStatus(force = false) {
            if (isReloading || !liveUrl) return;
            try {
                const res = await fetch(liveUrl + '?_t=' + Date.now(), {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data && data.state_hash && data.state_hash !== initialHash) {
                    triggerReload(force);
                }
            } catch (e) {
                // Ignore transient errors
            }
        }

        tickAdminClock();
        setInterval(tickAdminClock, 1000);
        setInterval(pollAdminLiveStatus, 3000);
    })();
</script>
