<?php
declare(strict_types=1);

$directorySource = $allDirectory ?? $students ?? [];
$autocompleteItems = [];
$alphaCounts = array_fill_keys(range('A', 'Z'), 0);

foreach ($directorySource as $item) {
    $namaClean = trim((string) ($item['nama'] ?? ''));
    $firstChar = strtoupper(mb_substr($namaClean, 0, 1));
    if (isset($alphaCounts[$firstChar])) {
        $alphaCounts[$firstChar]++;
    }
    $autocompleteItems[] = [
        'id'      => (int) $item['id'],
        'nim'     => (string) $item['nim'],
        'nama'    => $namaClean,
        'jenjang' => (string) $item['jenjang'],
        'prodi'   => (string) $item['program_studi'],
        'periode' => (string) $item['periode_masuk'],
        'status'  => (string) $item['status_mahasiswa'],
        'voted'   => !empty($item['attendance_id']),
    ];
}
$activeAlpha = strtoupper((string) ($filters['alpha'] ?? ''));
?>

<!-- Top Action Header -->
<div class="mb-6 bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex flex-col xl:flex-row xl:items-center justify-between gap-4">
    <div>
        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Manajemen Pemilih & Dataset Akademik</span>
        <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 mt-0.5">Dataset Mahasiswa Aktif & Status Hak Pilih</h3>
        <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">
            Format Kolom CSV Resmi:
            <code class="px-2 py-0.5 rounded bg-slate-100 text-indigo-700 font-mono font-bold inline-block mt-0.5 sm:mt-0">
                NIM, Nama, Jenjang, Program Studi, Periode masuk, Status Mahasiswa, Status Semester
            </code>
        </p>
    </div>

    <div class="grid grid-cols-1 sm:flex sm:flex-wrap items-center gap-2.5 shrink-0">
        <?php if (!empty($students)): ?>
            <button type="button" onclick="document.getElementById('deleteAllModal').classList.remove('hidden')"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-xs font-bold transition">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span>Hapus Semua Dataset</span>
            </button>
        <?php endif; ?>

        <a href="<?= url('/admin/voters/export-csv?' . http_build_query($filters)) ?>"
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-700 border border-emerald-200 text-xs font-bold transition">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Export Daftar Pemilih</span>
        </a>

        <a href="<?= url('/admin/voters/template') ?>"
           class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition">
            <svg class="w-4 h-4 text-slate-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span>Unduh Template CSV</span>
        </a>

        <button type="button" onclick="openStudentModal()"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-white border border-slate-300 hover:bg-slate-50 text-slate-800 text-xs font-bold transition">
            <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Tambah Mahasiswa Manual</span>
        </button>

        <button type="button" onclick="document.getElementById('importCsvModal').classList.remove('hidden')"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold shadow-md shadow-indigo-600/20 transition">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <span>Import Dataset CSV</span>
        </button>
    </div>
</div>

<!-- Hasil Validasi Import Dataset Terakhir (AC-07, Bab 9.2) -->
<?php if (!empty($lastImportReport)): ?>
    <div class="mb-6 bg-white rounded-3xl p-5 sm:p-6 border-2 border-indigo-200 shadow-md">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Laporan Hasil Validasi Import Dataset</span>
                <h4 class="text-base font-extrabold text-slate-900">File: <?= e($lastImportReport['filename']) ?></h4>
            </div>
            <div class="flex flex-wrap gap-2 text-xs font-bold">
                <span class="px-3 py-1 rounded-xl bg-slate-100 text-slate-700">Total Baris: <?= (int) $lastImportReport['total_rows'] ?></span>
                <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200">Ditambahkan: <?= (int) $lastImportReport['inserted_count'] ?></span>
                <span class="px-3 py-1 rounded-xl bg-blue-50 text-blue-700 border border-blue-200">Diperbarui: <?= (int) $lastImportReport['updated_count'] ?></span>
                <span class="px-3 py-1 rounded-xl bg-rose-50 text-rose-700 border border-rose-200">Gagal Validasi: <?= (int) $lastImportReport['failed_count'] ?></span>
            </div>
        </div>

        <?php if (!empty($lastImportReport['errors'])): ?>
            <div class="overflow-x-auto rounded-2xl border border-rose-200 bg-rose-50/40 p-4">
                <p class="text-xs font-bold text-rose-800 mb-2">Rincian Baris yang Gagal Validasi:</p>
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="text-rose-900 border-b border-rose-200">
                            <th class="py-2 pr-3">Baris CSV</th>
                            <th class="py-2 pr-3">NIM</th>
                            <th class="py-2 pr-3">Nama</th>
                            <th class="py-2">Alasan Penolakan Validasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-rose-100 text-rose-800">
                        <?php foreach ($lastImportReport['errors'] as $err): ?>
                            <tr>
                                <td class="py-2 pr-3 font-mono font-bold">#<?= (int) $err['row'] ?></td>
                                <td class="py-2 pr-3 font-mono"><?= e($err['nim']) ?></td>
                                <td class="py-2 pr-3"><?= e($err['nama']) ?></td>
                                <td class="py-2 font-medium"><?= e($err['reason']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<!-- Statistik Ringkas Pemilih -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 sm:gap-4 mb-6">
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
        <span class="text-[11px] font-bold uppercase text-slate-400 block">Total Dataset</span>
        <strong class="text-xl sm:text-2xl font-extrabold text-slate-900"><?= (int) $stats['total_dataset'] ?></strong>
    </div>
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
        <span class="text-[11px] font-bold uppercase text-emerald-600 block">Pemilih Aktif (DPT)</span>
        <strong class="text-xl sm:text-2xl font-extrabold text-emerald-600"><?= (int) $stats['total_eligible'] ?></strong>
    </div>
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
        <span class="text-[11px] font-bold uppercase text-rose-500 block">Cuti / Non-Aktif</span>
        <strong class="text-xl sm:text-2xl font-extrabold text-rose-600"><?= (int) $stats['total_non_eligible'] ?></strong>
    </div>
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm">
        <span class="text-[11px] font-bold uppercase text-indigo-600 block">Sudah Memilih</span>
        <strong class="text-xl sm:text-2xl font-extrabold text-indigo-600"><?= (int) $stats['total_voted'] ?></strong>
    </div>
    <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-sm col-span-2 sm:col-span-1">
        <span class="text-[11px] font-bold uppercase text-amber-600 block">Belum Memilih</span>
        <strong class="text-xl sm:text-2xl font-extrabold text-amber-600"><?= (int) $stats['total_not_voted'] ?></strong>
    </div>
</div>

<!-- Filter, Pencarian Autocomplete Pintar, & Abjad Otomatis (A-Z) -->
<form id="voterFilterForm" action="<?= url('/admin/voters') ?>" method="GET" class="bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-sm mb-6 space-y-4">
    <input type="hidden" name="alpha" id="alphaInput" value="<?= e($activeAlpha) ?>">

    <?php if (!empty($allElections) && count($allElections) > 1): ?>
        <div class="pb-3 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                <span class="text-xs font-bold text-slate-700">Pantau Status Voting untuk Periode Pemilihan:</span>
            </div>
            <select name="election_id" onchange="this.form.submit()" class="px-3.5 py-2 rounded-xl border border-indigo-200 bg-indigo-50/60 text-indigo-900 text-xs font-extrabold">
                <?php foreach ($allElections as $elOpt): ?>
                    <option value="<?= (int) $elOpt['id'] ?>" <?= ((int) ($election['id'] ?? 0) === (int) $elOpt['id']) ? 'selected' : '' ?>>
                        <?= e($elOpt['title']) ?> (<?= e($elOpt['organization_name']) ?> &bull; <?= strtoupper((string) $elOpt['status']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php elseif (!empty($election)): ?>
        <input type="hidden" name="election_id" value="<?= (int) $election['id'] ?>">
    <?php endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
        <!-- Kolom Cari dengan Live Autocomplete Dropdown -->
        <div class="sm:col-span-2 lg:col-span-3 relative">
            <label for="voterSearchInput" class="block text-[11px] font-bold uppercase text-slate-500 mb-1.5">
                Cari NIM / Nama / Prodi
            </label>
            <div class="relative">
                <input type="text" id="voterSearchInput" name="q" value="<?= e($filters['q']) ?>"
                       autocomplete="off"
                       placeholder="Ketik huruf awal nama (misal: I, Fikri)..."
                       class="w-full pl-9 pr-8 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold text-slate-900 focus:border-indigo-600 focus:ring-3 focus:ring-indigo-500/15 outline-none transition">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <button type="button" id="clearSearchBtn" onclick="clearVoterSearch()"
                        class="<?= empty($filters['q']) ? 'hidden' : '' ?> absolute right-2.5 top-2.5 text-slate-400 hover:text-slate-700 p-0.5 rounded-md">
                    ✕
                </button>
            </div>

            <!-- Dropdown Kemungkinan Nama Mahasiswa (Autocomplete Otomatis) -->
            <div id="autocompleteDropdown"
                 class="hidden absolute left-0 right-0 top-full mt-1.5 bg-white rounded-2xl border border-slate-200 shadow-2xl z-40 max-h-80 overflow-y-auto divide-y divide-slate-100">
            </div>
        </div>

        <div class="lg:col-span-2">
            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1.5">Jenjang</label>
            <select name="jenjang" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs font-medium bg-white">
                <option value="">Semua Jenjang</option>
                <?php foreach ($jenjangList as $j): ?>
                    <option value="<?= e($j) ?>" <?= $filters['jenjang'] === $j ? 'selected' : '' ?>><?= e($j) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="lg:col-span-2">
            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1.5">Program Studi</label>
            <select name="program_studi" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs font-medium bg-white">
                <option value="">Semua Prodi</option>
                <?php foreach ($prodiList as $p): ?>
                    <option value="<?= e($p) ?>" <?= $filters['program_studi'] === $p ? 'selected' : '' ?>><?= e($p) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="lg:col-span-2">
            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1.5">Status Mahasiswa</label>
            <select name="status_mahasiswa" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs font-medium bg-white">
                <option value="">Semua Status</option>
                <option value="Aktif" <?= strcasecmp($filters['status_mahasiswa'], 'Aktif') === 0 ? 'selected' : '' ?>>Aktif</option>
                <option value="Cuti" <?= strcasecmp($filters['status_mahasiswa'], 'Cuti') === 0 ? 'selected' : '' ?>>Cuti</option>
                <option value="Non-Aktif" <?= strcasecmp($filters['status_mahasiswa'], 'Non-Aktif') === 0 ? 'selected' : '' ?>>Non-Aktif</option>
                <option value="Lulus" <?= strcasecmp($filters['status_mahasiswa'], 'Lulus') === 0 ? 'selected' : '' ?>>Lulus</option>
            </select>
        </div>

        <div class="lg:col-span-2">
            <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1.5">Status Voting</label>
            <select name="voting_status" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 text-xs font-medium bg-white">
                <option value="">Semua Status</option>
                <option value="voted" <?= $filters['voting_status'] === 'voted' ? 'selected' : '' ?>>Sudah Memilih</option>
                <option value="not_voted" <?= $filters['voting_status'] === 'not_voted' ? 'selected' : '' ?>>Belum Memilih</option>
            </select>
        </div>

        <div class="sm:col-span-2 lg:col-span-1 flex gap-2">
            <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition">
                Filter
            </button>
        </div>
    </div>

    <!-- Bar Abjad Otomatis (A-Z) untuk Filter Cepat Berdasarkan Huruf Awal Nama -->
    <div class="pt-3 border-t border-slate-100">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">
                Filter Cepat Huruf Awal Nama (A–Z):
            </span>
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleSelectAllVisible(true)"
                        class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 hover:underline">
                    Centang Semua yang Tampil
                </button>
                <span class="text-slate-300">|</span>
                <span id="liveMatchCount" class="text-[11px] font-bold text-slate-600">
                    Menampilkan <?= count($students) ?> Mahasiswa
                </span>
            </div>
        </div>

        <div class="flex items-center gap-1 overflow-x-auto pb-1">
            <button type="button" onclick="selectAlphaFilter('')"
                    data-alpha=""
                    class="alpha-btn shrink-0 px-3 py-1.5 rounded-lg text-[11px] font-extrabold transition <?= $activeAlpha === '' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>">
                Semua
            </button>
            <?php foreach (range('A', 'Z') as $letter): ?>
                <?php
                $countForLetter = $alphaCounts[$letter] ?? 0;
                $isActiveLetter = ($activeAlpha === $letter);
                ?>
                <button type="button"
                        onclick="selectAlphaFilter('<?= $letter ?>')"
                        data-alpha="<?= $letter ?>"
                        title="<?= $countForLetter ?> mahasiswa berawalan huruf <?= $letter ?>"
                        class="alpha-btn shrink-0 w-7 h-7 rounded-lg text-[11px] font-extrabold flex items-center justify-center transition <?= $isActiveLetter ? 'bg-indigo-600 text-white shadow-sm' : ($countForLetter > 0 ? 'bg-slate-100 text-slate-700 hover:bg-indigo-50 hover:text-indigo-700' : 'bg-slate-50 text-slate-300') ?>">
                    <?= $letter ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</form>

<!-- Toolbar Aksi Hapus Massal (Muncul Otomatis Saat Ada Mahasiswa yang Dicentang) -->
<form id="bulkDeleteForm" action="<?= url('/admin/voters/bulk-delete') ?>" method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="delete_mode" value="selected">
    <div id="bulkHiddenInputs"></div>

    <div id="bulkActionBar" class="hidden mb-4 p-4 rounded-2xl bg-slate-900 text-white shadow-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 sticky top-20 z-30 border border-slate-700">
        <div class="flex items-center gap-3">
            <span id="selectedCountBadge" class="px-3 py-1 rounded-xl bg-indigo-600 text-white font-extrabold text-xs">
                0 Dipilih
            </span>
            <span class="text-xs text-slate-300 font-medium">
                Mahasiswa telah dipilih untuk aksi penghapusan massal.
            </span>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" onclick="toggleSelectAllVisible(true)"
                    class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold transition">
                Pilih Semua yang Tampil
            </button>
            <button type="button" onclick="toggleSelectAllVisible(false)"
                    class="px-3 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold transition">
                Batal Pilih
            </button>
            <button type="button" onclick="confirmBulkDelete()"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-extrabold shadow-md transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <span id="bulkDeleteBtnLabel">Hapus Terpilih</span>
            </button>
        </div>
    </div>
</form>

<!-- Daftar Mahasiswa: Tabel untuk Desktop (md+) & Kartu Responsif untuk Mobile (< md) -->
<div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden mb-8">
    <div id="emptySearchState" class="<?= empty($students) ? '' : 'hidden' ?> py-12 px-4 text-center text-xs text-slate-400">
        Tidak ada nama atau NIM mahasiswa yang cocok dengan kata kunci pencarian.
    </div>

    <?php if (!empty($students)): ?>
        <!-- Tampilan Mobile Cards (< md) -->
        <div id="mobileStudentList" class="md:hidden divide-y divide-slate-100">
            <?php foreach ($students as $s): ?>
                <?php
                $sid = (int) $s['id'];
                $eligible = \App\Models\Student::isEligible($s);
                $hasVoted = !empty($s['attendance_id']);
                $firstLetter = strtoupper(mb_substr(trim((string) $s['nama']), 0, 1));
                ?>
                <div class="student-row-item p-4 space-y-3"
                     data-id="<?= $sid ?>"
                     data-nama="<?= e(strtolower((string) $s['nama'])) ?>"
                     data-nim="<?= e(strtolower((string) $s['nim'])) ?>"
                     data-prodi="<?= e(strtolower((string) $s['program_studi'])) ?>"
                     data-alpha="<?= e($firstLetter) ?>">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex items-start gap-3">
                            <input type="checkbox"
                                   value="<?= $sid ?>"
                                   onchange="onStudentCheckChange(<?= $sid ?>, this.checked)"
                                   class="student-checkbox mt-1 w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            <div>
                                <span class="font-mono text-xs font-extrabold text-indigo-600 block"><?= e($s['nim']) ?></span>
                                <h4 class="font-extrabold text-slate-900 text-sm"><?= e($s['nama']) ?></h4>
                                <p class="text-[11px] text-slate-500 mt-0.5">
                                    <span class="font-bold text-slate-700"><?= e($s['jenjang']) ?></span> - <?= e($s['program_studi']) ?> &bull; Masuk: <?= e($s['periode_masuk']) ?>
                                </p>
                            </div>
                        </div>

                        <?php if ($hasVoted): ?>
                            <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                ✓ Sudah Memilih
                            </span>
                        <?php elseif ($eligible): ?>
                            <span class="shrink-0 inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Belum Memilih
                            </span>
                        <?php else: ?>
                            <span class="shrink-0 text-[11px] text-slate-400 italic">Tidak Berhak</span>
                        <?php endif; ?>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= strcasecmp((string) $s['status_mahasiswa'], 'Aktif') === 0 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
                                Mhs: <?= e($s['status_mahasiswa']) ?>
                            </span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= strcasecmp((string) $s['status_semester'], 'Aktif') === 0 ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-amber-50 text-amber-700 border border-amber-200' ?>">
                                Smt: <?= e($s['status_semester']) ?>
                            </span>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button type="button"
                                    onclick='editStudent(<?= json_encode([
                                        "nim" => $s["nim"],
                                        "nama" => $s["nama"],
                                        "jenjang" => $s["jenjang"],
                                        "program_studi" => $s["program_studi"],
                                        "periode_masuk" => $s["periode_masuk"],
                                        "status_mahasiswa" => $s["status_mahasiswa"],
                                        "status_semester" => $s["status_semester"],
                                    ], JSON_UNESCAPED_UNICODE) ?>)'
                                    class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-indigo-50 text-slate-700 font-bold text-[11px]">
                                Edit
                            </button>
                            <form action="<?= url('/admin/voters/reset-password') ?>" method="POST" class="inline"
                                  onsubmit="return confirm('Reset password mahasiswa <?= e(addslashes((string) $s['nama'])) ?> kembali ke NIM (<?= e($s['nim']) ?>)?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="student_id" value="<?= $sid ?>">
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-amber-50 text-amber-800 font-bold text-[11px]">
                                    Reset Pass
                                </button>
                            </form>
                            <form action="<?= url('/admin/voters/delete') ?>" method="POST" class="inline"
                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus data mahasiswa <?= e(addslashes((string) $s['nama'])) ?> (NIM: <?= e($s['nim']) ?>)?')">
                                <?= csrf_field() ?>
                                <input type="hidden" name="student_id" value="<?= $sid ?>">
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[11px]">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tampilan Tabel Desktop (md+) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3.5 pl-4 pr-2 w-10">
                            <input type="checkbox" id="selectAllDesktop"
                                   onchange="toggleSelectAllVisible(this.checked)"
                                   title="Pilih Semua Mahasiswa yang Tampil"
                                   class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                        </th>
                        <th class="py-3.5 px-4">NIM</th>
                        <th class="py-3.5 px-4">Nama Mahasiswa</th>
                        <th class="py-3.5 px-4">Jenjang & Program Studi</th>
                        <th class="py-3.5 px-4">Periode Masuk</th>
                        <th class="py-3.5 px-4">Status Mahasiswa</th>
                        <th class="py-3.5 px-4">Status Semester</th>
                        <th class="py-3.5 px-4">Status Voting</th>
                        <th class="py-3.5 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody id="desktopStudentTableBody" class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($students as $s): ?>
                        <?php
                        $sid = (int) $s['id'];
                        $eligible = \App\Models\Student::isEligible($s);
                        $hasVoted = !empty($s['attendance_id']);
                        $firstLetter = strtoupper(mb_substr(trim((string) $s['nama']), 0, 1));
                        ?>
                        <tr class="student-row-item hover:bg-slate-50/80 transition"
                            data-id="<?= $sid ?>"
                            data-nama="<?= e(strtolower((string) $s['nama'])) ?>"
                            data-nim="<?= e(strtolower((string) $s['nim'])) ?>"
                            data-prodi="<?= e(strtolower((string) $s['program_studi'])) ?>"
                            data-alpha="<?= e($firstLetter) ?>">
                            <td class="py-3.5 pl-4 pr-2">
                                <input type="checkbox"
                                       value="<?= $sid ?>"
                                       onchange="onStudentCheckChange(<?= $sid ?>, this.checked)"
                                       class="student-checkbox w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                            </td>
                            <td class="py-3.5 px-4 font-mono font-bold text-slate-900"><?= e($s['nim']) ?></td>
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-900"><?= e($s['nama']) ?></div>
                                <div class="text-[11px] text-slate-400">
                                    <?= ((int) $s['must_change_password'] === 1) ? 'Password: Default (NIM)' : 'Password: Sudah Dikustomisasi ✓' ?>
                                </div>
                            </td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded bg-slate-100 font-bold text-slate-700 mr-1"><?= e($s['jenjang']) ?></span>
                                <span class="font-medium text-slate-700"><?= e($s['program_studi']) ?></span>
                            </td>
                            <td class="py-3.5 px-4 text-slate-600 font-medium"><?= e($s['periode_masuk']) ?></td>
                            <td class="py-3.5 px-4">
                                <?php if (strcasecmp((string) $s['status_mahasiswa'], 'Aktif') === 0): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                        <?= e($s['status_mahasiswa']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if (strcasecmp((string) $s['status_semester'], 'Aktif') === 0): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        <?= e($s['status_semester']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4">
                                <?php if ($hasVoted): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                        ✓ Sudah Memilih
                                    </span>
                                    <div class="text-[10px] text-slate-400 mt-0.5"><?= format_indo_datetime($s['voted_at']) ?></div>
                                <?php elseif ($eligible): ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        Belum Memilih
                                    </span>
                                <?php else: ?>
                                    <span class="text-[11px] text-slate-400 italic">Tidak Berhak Pilih</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3.5 px-4 text-right space-x-1 whitespace-nowrap">
                                <button type="button"
                                        onclick='editStudent(<?= json_encode([
                                            "nim" => $s["nim"],
                                            "nama" => $s["nama"],
                                            "jenjang" => $s["jenjang"],
                                            "program_studi" => $s["program_studi"],
                                            "periode_masuk" => $s["periode_masuk"],
                                            "status_mahasiswa" => $s["status_mahasiswa"],
                                            "status_semester" => $s["status_semester"],
                                        ], JSON_UNESCAPED_UNICODE) ?>)'
                                        class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 font-bold text-[11px] transition">
                                    Edit
                                </button>
                                <form action="<?= url('/admin/voters/reset-password') ?>" method="POST" class="inline"
                                      onsubmit="return confirm('Reset password mahasiswa <?= e(addslashes((string) $s['nama'])) ?> kembali ke NIM (<?= e($s['nim']) ?>)?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="student_id" value="<?= $sid ?>">
                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-800 font-bold text-[11px] transition">
                                        Reset Pass
                                    </button>
                                </form>
                                <form action="<?= url('/admin/voters/delete') ?>" method="POST" class="inline"
                                      onsubmit="return confirm('Apakah Anda yakin ingin menghapus data mahasiswa <?= e(addslashes((string) $s['nama'])) ?> (NIM: <?= e($s['nim']) ?>)?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="student_id" value="<?= $sid ?>">
                                    <button type="submit" class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-[11px] transition">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Riwayat Import Dataset CSV -->
<?php if (!empty($importLogs)): ?>
<div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
    <h4 class="text-sm font-extrabold text-slate-900 mb-3">Riwayat Import Dataset CSV</h4>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs min-w-[540px]">
            <thead>
                <tr class="border-b border-slate-200 text-slate-400 uppercase text-[11px]">
                    <th class="py-2.5 pr-4">Waktu Import</th>
                    <th class="py-2.5 pr-4">Nama File CSV</th>
                    <th class="py-2.5 pr-4">Diunggah Oleh</th>
                    <th class="py-2.5 pr-4">Total Baris</th>
                    <th class="py-2.5 pr-4">Baru</th>
                    <th class="py-2.5 pr-4">Diperbarui</th>
                    <th class="py-2.5">Gagal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($importLogs as $il): ?>
                    <tr>
                        <td class="py-2.5 pr-4 text-slate-500"><?= format_indo_datetime($il['created_at']) ?></td>
                        <td class="py-2.5 pr-4 font-bold text-slate-800"><?= e($il['filename']) ?></td>
                        <td class="py-2.5 pr-4 text-slate-600"><?= e($il['admin_name']) ?></td>
                        <td class="py-2.5 pr-4 font-bold"><?= (int) $il['total_rows'] ?></td>
                        <td class="py-2.5 pr-4 text-emerald-600 font-bold">+<?= (int) $il['inserted_count'] ?></td>
                        <td class="py-2.5 pr-4 text-blue-600 font-bold"><?= (int) $il['updated_count'] ?></td>
                        <td class="py-2.5 text-rose-600 font-bold"><?= (int) $il['failed_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Modal Konfirmasi Hapus Semua Dataset Mahasiswa -->
<div id="deleteAllModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl">
        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-4">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
        </div>
        <h3 class="text-lg font-extrabold text-slate-900 mb-1">Hapus Seluruh Dataset Mahasiswa?</h3>
        <p class="text-xs text-slate-600 leading-relaxed mb-5">
            Tindakan ini akan menghapus seluruh <strong><?= (int) $stats['total_dataset'] ?> data mahasiswa</strong> beserta akun login mahasiswa dari sistem. Anda dapat mengunggah kembali dataset CSV baru setelahnya.
        </p>
        <form action="<?= url('/admin/voters/bulk-delete') ?>" method="POST" class="flex justify-end gap-2">
            <?= csrf_field() ?>
            <input type="hidden" name="delete_mode" value="all">
            <button type="button" onclick="document.getElementById('deleteAllModal').classList.add('hidden')"
                    class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">
                Batal
            </button>
            <button type="submit"
                    class="px-5 py-2.5 rounded-xl text-xs font-extrabold text-white bg-rose-600 hover:bg-rose-700 shadow-md">
                Ya, Hapus Semua Dataset
            </button>
        </form>
    </div>
</div>

<!-- Modal Import CSV Dataset Mahasiswa (FR-01) -->
<div id="importCsvModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Unggah Dataset Pemilih</span>
                <h3 class="text-lg font-extrabold text-slate-900">Import Dataset Mahasiswa (CSV)</h3>
            </div>
            <button type="button" onclick="document.getElementById('importCsvModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs space-y-2 mb-5">
            <p class="font-bold text-slate-800">Struktur Kolom Wajib CSV:</p>
            <code class="block p-2.5 rounded-xl bg-slate-900 text-indigo-200 font-mono text-[11px] overflow-x-auto">
                NIM, Nama, Jenjang, Program Studi, Periode masuk, Status Mahasiswa, Status Semester
            </code>
            <ul class="list-disc list-inside text-slate-600 space-y-1 pt-1">
                <li>Mendukung pemisah koma (<code>,</code>) maupun titik koma (<code>;</code>) dari Microsoft Excel.</li>
                <li>Mahasiswa baru otomatis dibuatkan akun login dengan password awal = <strong>NIM</strong> (dan disarankan mengganti password saat login pertama).</li>
                <li>Hanya mahasiswa dengan <strong>Status Mahasiswa = Aktif</strong> dan <strong>Status Semester = Aktif</strong> yang memperoleh hak pilih.</li>
            </ul>
        </div>

        <form action="<?= url('/admin/voters/import') ?>" method="POST" enctype="multipart/form-data" class="space-y-4">
            <?= csrf_field() ?>
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-2">Pilih File Dataset (.csv)</label>
                <input type="file" name="csv_file" accept=".csv" required
                       class="w-full text-xs text-slate-600 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-slate-300 rounded-2xl p-2">
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3">
                <a href="<?= url('/admin/voters/template') ?>" class="text-xs font-bold text-indigo-600 hover:underline">
                    Unduh Contoh File CSV
                </a>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('importCsvModal').classList.add('hidden')"
                            class="px-4 py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-100">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl text-xs font-extrabold text-white bg-indigo-600 hover:bg-indigo-700 shadow-md">
                        Mulai Validasi & Import
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah / Edit Mahasiswa Manual -->
<div id="studentModal" class="hidden fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
            <h3 id="studentModalTitle" class="text-lg font-extrabold text-slate-900">Tambah / Perbarui Data Mahasiswa</h3>
            <button type="button" onclick="document.getElementById('studentModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <form action="<?= url('/admin/voters/store') ?>" method="POST" class="space-y-3.5 text-xs">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">NIM</label>
                    <input type="text" name="nim" id="f_nim" required placeholder="Contoh: 230101009"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Jenjang</label>
                    <select name="jenjang" id="f_jenjang" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white">
                        <option value="S1">S1</option>
                        <option value="D4">D4</option>
                        <option value="D3">D3</option>
                        <option value="S2">S2</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Mahasiswa</label>
                <input type="text" name="nama" id="f_nama" required placeholder="Masukkan nama lengkap"
                       class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Program Studi</label>
                    <input type="text" name="program_studi" id="f_prodi" required placeholder="Contoh: Teknik Informatika"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Periode Masuk</label>
                    <input type="text" name="periode_masuk" id="f_periode" required placeholder="Contoh: 2023/2024 Ganjil"
                           class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Status Mahasiswa</label>
                    <select name="status_mahasiswa" id="f_smhs" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white">
                        <option value="Aktif">Aktif</option>
                        <option value="Cuti">Cuti</option>
                        <option value="Non-Aktif">Non-Aktif</option>
                        <option value="Lulus">Lulus</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Status Semester</label>
                    <select name="status_semester" id="f_ssmt" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 bg-white">
                        <option value="Aktif">Aktif</option>
                        <option value="Cuti">Cuti</option>
                        <option value="Non-Aktif">Non-Aktif</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-3">
                <button type="button" onclick="document.getElementById('studentModal').classList.add('hidden')"
                        class="px-4 py-2.5 rounded-xl font-bold text-slate-600 hover:bg-slate-100">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2.5 rounded-xl font-extrabold text-white bg-indigo-600 hover:bg-indigo-700">
                    Simpan Data Mahasiswa
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const VOTER_DIRECTORY = <?= json_encode($autocompleteItems, JSON_UNESCAPED_UNICODE) ?>;
    const searchInput = document.getElementById('voterSearchInput');
    const dropdown = document.getElementById('autocompleteDropdown');
    const clearBtn = document.getElementById('clearSearchBtn');
    const alphaInput = document.getElementById('alphaInput');
    const liveMatchCount = document.getElementById('liveMatchCount');
    const emptyState = document.getElementById('emptySearchState');
    const selectedStudentIds = new Set();

    let activeSuggestionIndex = -1;
    let currentSuggestions = [];

    // --- Manajemen Seleksi Massal (Bulk Delete) ---
    function onStudentCheckChange(studentId, checked) {
        if (checked) {
            selectedStudentIds.add(Number(studentId));
        } else {
            selectedStudentIds.delete(Number(studentId));
        }

        document.querySelectorAll(`.student-checkbox[value="${studentId}"]`).forEach(cb => {
            cb.checked = checked;
        });

        updateBulkActionBar();
    }

    function toggleSelectAllVisible(checked) {
        const desktopRows = document.querySelectorAll('#desktopStudentTableBody .student-row-item');
        desktopRows.forEach(row => {
            if (!row.classList.contains('hidden')) {
                const sid = Number(row.getAttribute('data-id'));
                if (checked) {
                    selectedStudentIds.add(sid);
                } else {
                    selectedStudentIds.delete(sid);
                }
                document.querySelectorAll(`.student-checkbox[value="${sid}"]`).forEach(cb => {
                    cb.checked = checked;
                });
            }
        });

        const selectAllDesktop = document.getElementById('selectAllDesktop');
        if (selectAllDesktop) {
            selectAllDesktop.checked = checked;
        }
        updateBulkActionBar();
    }

    function updateBulkActionBar() {
        const bar = document.getElementById('bulkActionBar');
        const badge = document.getElementById('selectedCountBadge');
        const btnLabel = document.getElementById('bulkDeleteBtnLabel');
        const count = selectedStudentIds.size;

        if (count > 0) {
            bar.classList.remove('hidden');
            badge.textContent = `${count} Dipilih`;
            btnLabel.textContent = `Hapus ${count} Mahasiswa Terpilih`;
        } else {
            bar.classList.add('hidden');
            const selectAllDesktop = document.getElementById('selectAllDesktop');
            if (selectAllDesktop) {
                selectAllDesktop.checked = false;
            }
        }
    }

    function confirmBulkDelete() {
        const count = selectedStudentIds.size;
        if (count === 0) return;

        if (!confirm(`Apakah Anda yakin ingin menghapus ${count} data mahasiswa yang dipilih secara permanen?`)) {
            return;
        }

        const container = document.getElementById('bulkHiddenInputs');
        container.innerHTML = '';
        selectedStudentIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'student_ids[]';
            input.value = String(id);
            container.appendChild(input);
        });

        document.getElementById('bulkDeleteForm').submit();
    }

    // --- Autocomplete & Filter Abjad Otomatis ---
    function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, function(m) {
            return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[m];
        });
    }

    function highlightMatch(text, query) {
        if (!query) return escapeHtml(text);
        const safeText = String(text);
        const idx = safeText.toLowerCase().indexOf(query.toLowerCase());
        if (idx === -1) return escapeHtml(safeText);
        const before = escapeHtml(safeText.slice(0, idx));
        const match  = escapeHtml(safeText.slice(idx, idx + query.length));
        const after  = escapeHtml(safeText.slice(idx + query.length));
        return `${before}<mark class="bg-indigo-100 text-indigo-800 font-extrabold rounded px-0.5">${match}</mark>${after}`;
    }

    function getMatchingSuggestions(rawQuery) {
        const q = rawQuery.trim().toLowerCase();
        if (!q) return [];

        const startsWithName = [];
        const wordStartsWithName = [];
        const containsNameOrNim = [];

        for (const item of VOTER_DIRECTORY) {
            const namaLower = item.nama.toLowerCase();
            const nimLower  = item.nim.toLowerCase();

            if (namaLower.startsWith(q)) {
                startsWithName.push(item);
            } else {
                const words = namaLower.split(/\s+/);
                if (words.some(w => w.startsWith(q))) {
                    wordStartsWithName.push(item);
                } else if (namaLower.includes(q) || nimLower.includes(q)) {
                    containsNameOrNim.push(item);
                }
            }
        }

        return [...startsWithName, ...wordStartsWithName, ...containsNameOrNim].slice(0, 10);
    }

    function renderAutocomplete(query) {
        const q = query.trim();
        if (q.length === 0) {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
            activeSuggestionIndex = -1;
            return;
        }

        currentSuggestions = getMatchingSuggestions(q);

        if (currentSuggestions.length === 0) {
            dropdown.innerHTML = `
                <div class="p-3.5 text-center text-xs text-slate-400">
                    Tidak ditemukan nama mahasiswa dengan awalan / kata kunci "<strong>${escapeHtml(q)}</strong>"
                </div>
            `;
            dropdown.classList.remove('hidden');
            return;
        }

        let html = `
            <div class="px-3.5 py-2 bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center justify-between">
                <span>Kemungkinan Nama Mahasiswa (${currentSuggestions.length})</span>
                <span>Klik untuk Memilih</span>
            </div>
        `;

        currentSuggestions.forEach((item, idx) => {
            const initials = item.nama.split(/\s+/).slice(0, 2).map(w => w[0] || '').join('').toUpperCase();
            const statusClass = item.status.toLowerCase() === 'aktif'
                ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                : 'bg-rose-50 text-rose-700 border-rose-200';

            html += `
                <button type="button"
                        data-idx="${idx}"
                        onclick="chooseSuggestion(${idx})"
                        class="suggestion-item w-full text-left px-3.5 py-2.5 hover:bg-indigo-50/80 transition flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-700 font-extrabold text-[11px] flex items-center justify-center shrink-0">
                            ${escapeHtml(initials)}
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-slate-900 truncate">
                                ${highlightMatch(item.nama, q)}
                            </div>
                            <div class="text-[11px] text-slate-500 truncate">
                                <span class="font-mono font-semibold text-slate-700">${highlightMatch(item.nim, q)}</span>
                                &bull; ${escapeHtml(item.jenjang)} - ${escapeHtml(item.prodi)}
                            </div>
                        </div>
                    </div>
                    <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold border ${statusClass}">
                        ${escapeHtml(item.status)}
                    </span>
                </button>
            `;
        });

        dropdown.innerHTML = html;
        dropdown.classList.remove('hidden');
        activeSuggestionIndex = -1;
    }

    function filterRowsInstant(query, alphaFilter = '') {
        const q = query.trim().toLowerCase();
        const alpha = alphaFilter.trim().toUpperCase();
        const desktopRows = document.querySelectorAll('#desktopStudentTableBody .student-row-item');
        const mobileRows  = document.querySelectorAll('#mobileStudentList .student-row-item');

        let visibleDesktopCount = 0;

        desktopRows.forEach(row => {
            const nama  = row.getAttribute('data-nama') || '';
            const nim   = row.getAttribute('data-nim') || '';
            const prodi = row.getAttribute('data-prodi') || '';
            const rowAlpha = row.getAttribute('data-alpha') || '';

            const matchQ = !q || nama.includes(q) || nim.includes(q) || prodi.includes(q);
            const matchAlpha = !alpha || rowAlpha === alpha;

            if (matchQ && matchAlpha) {
                row.classList.remove('hidden');
                visibleDesktopCount++;
            } else {
                row.classList.add('hidden');
            }
        });

        mobileRows.forEach(row => {
            const nama  = row.getAttribute('data-nama') || '';
            const nim   = row.getAttribute('data-nim') || '';
            const prodi = row.getAttribute('data-prodi') || '';
            const rowAlpha = row.getAttribute('data-alpha') || '';

            const matchQ = !q || nama.includes(q) || nim.includes(q) || prodi.includes(q);
            const matchAlpha = !alpha || rowAlpha === alpha;

            if (matchQ && matchAlpha) {
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        });

        if (liveMatchCount) {
            liveMatchCount.textContent = `Menampilkan ${visibleDesktopCount} Mahasiswa`;
        }
        if (emptyState) {
            if (visibleDesktopCount === 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
        }
    }

    function chooseSuggestion(idx) {
        const item = currentSuggestions[idx];
        if (!item) return;
        searchInput.value = item.nama;
        clearBtn.classList.remove('hidden');
        dropdown.classList.add('hidden');
        alphaInput.value = '';
        updateAlphaButtonStyles('');
        filterRowsInstant(item.nama, '');
    }

    function clearVoterSearch() {
        searchInput.value = '';
        clearBtn.classList.add('hidden');
        dropdown.classList.add('hidden');
        filterRowsInstant('', alphaInput.value);
        searchInput.focus();
    }

    function updateAlphaButtonStyles(selectedLetter) {
        document.querySelectorAll('.alpha-btn').forEach(btn => {
            const btnAlpha = btn.getAttribute('data-alpha') || '';
            if (btnAlpha === selectedLetter) {
                btn.classList.remove('bg-slate-100', 'text-slate-600', 'text-slate-700', 'bg-slate-50', 'text-slate-300');
                btn.classList.add('bg-indigo-600', 'text-white', 'shadow-sm');
            } else {
                btn.classList.remove('bg-indigo-600', 'text-white', 'shadow-sm');
                btn.classList.add('bg-slate-100', 'text-slate-700');
            }
        });
    }

    function selectAlphaFilter(letter) {
        alphaInput.value = letter;
        updateAlphaButtonStyles(letter);
        filterRowsInstant(searchInput.value, letter);

        if (letter && searchInput.value.trim() === '') {
            currentSuggestions = VOTER_DIRECTORY.filter(item => item.nama.toUpperCase().startsWith(letter)).slice(0, 10);
            if (currentSuggestions.length > 0) {
                renderAutocomplete(letter);
            } else {
                dropdown.classList.add('hidden');
            }
        } else {
            dropdown.classList.add('hidden');
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const val = this.value;
            if (val.trim().length > 0) {
                clearBtn.classList.remove('hidden');
            } else {
                clearBtn.classList.add('hidden');
            }
            renderAutocomplete(val);
            filterRowsInstant(val, alphaInput.value);
        });

        searchInput.addEventListener('focus', function() {
            if (this.value.trim().length > 0) {
                renderAutocomplete(this.value);
            }
        });

        searchInput.addEventListener('keydown', function(e) {
            const items = dropdown.querySelectorAll('.suggestion-item');
            if (dropdown.classList.contains('hidden') || items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeSuggestionIndex = (activeSuggestionIndex + 1) % items.length;
                highlightActiveSuggestion(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeSuggestionIndex = (activeSuggestionIndex - 1 + items.length) % items.length;
                highlightActiveSuggestion(items);
            } else if (e.key === 'Enter' && activeSuggestionIndex >= 0) {
                e.preventDefault();
                chooseSuggestion(activeSuggestionIndex);
            } else if (e.key === 'Escape') {
                dropdown.classList.add('hidden');
            }
        });
    }

    function highlightActiveSuggestion(items) {
        items.forEach((el, i) => {
            if (i === activeSuggestionIndex) {
                el.classList.add('bg-indigo-50');
                el.scrollIntoView({ block: 'nearest' });
            } else {
                el.classList.remove('bg-indigo-50');
            }
        });
    }

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !dropdown.contains(e.target) && !e.target.classList.contains('alpha-btn')) {
            dropdown.classList.add('hidden');
        }
    });

    function openStudentModal() {
        document.getElementById('studentModalTitle').textContent = 'Tambah Data Mahasiswa';
        document.getElementById('f_nim').value = '';
        document.getElementById('f_nama').value = '';
        document.getElementById('f_jenjang').value = 'S1';
        document.getElementById('f_prodi').value = '';
        document.getElementById('f_periode').value = '2024/2025 Ganjil';
        document.getElementById('f_smhs').value = 'Aktif';
        document.getElementById('f_ssmt').value = 'Aktif';
        document.getElementById('studentModal').classList.remove('hidden');
    }
    function editStudent(data) {
        document.getElementById('studentModalTitle').textContent = 'Edit Data Mahasiswa (' + data.nim + ')';
        document.getElementById('f_nim').value = data.nim;
        document.getElementById('f_nama').value = data.nama;
        document.getElementById('f_jenjang').value = data.jenjang;
        document.getElementById('f_prodi').value = data.program_studi;
        document.getElementById('f_periode').value = data.periode_masuk;
        document.getElementById('f_smhs').value = data.status_mahasiswa;
        document.getElementById('f_ssmt').value = data.status_semester;
        document.getElementById('studentModal').classList.remove('hidden');
    }
</script>
