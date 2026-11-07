<?php declare(strict_types=1); ?>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Form Tambah / Edit Calon Ketua (FR-06) -->
    <div class="lg:col-span-5">
        <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm sticky top-24">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Formulir Kandidat</span>
                    <h3 class="text-base font-extrabold text-slate-900">
                        <?= $editCandidate ? 'Edit Calon Nomor Urut 0' . (int) $editCandidate['candidate_number'] : 'Tambah Calon Ketua Baru' ?>
                    </h3>
                </div>
                <?php if ($editCandidate): ?>
                    <a href="<?= url('/admin/candidates?election_id=' . ($election['id'] ?? '')) ?>" class="text-xs font-bold text-slate-500 hover:text-slate-800">
                        + Buat Baru
                    </a>
                <?php endif; ?>
            </div>

            <?php if (!$election): ?>
                <p class="text-xs text-rose-600">Silakan buat periode pemilihan terlebih dahulu.</p>
            <?php else: ?>
                <form action="<?= url($editCandidate ? '/admin/candidates/update' : '/admin/candidates/store') ?>"
                      method="POST" enctype="multipart/form-data" class="space-y-3.5 text-xs">
                    <?= csrf_field() ?>
                    <?php if ($editCandidate): ?>
                        <input type="hidden" name="id" value="<?= (int) $editCandidate['id'] ?>">
                        <input type="hidden" name="election_id" value="<?= (int) $election['id'] ?>">
                    <?php endif; ?>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Periode Pemilihan</label>
                        <?php if ($editCandidate): ?>
                            <div class="w-full px-3.5 py-2.5 rounded-xl border border-indigo-200 bg-indigo-50/60 text-indigo-900 font-bold">
                                <?= e($election['title']) ?> (<?= e($election['organization_name']) ?> &bull; <?= e($election['period_label']) ?>)
                            </div>
                        <?php else: ?>
                            <select name="election_id"
                                    onchange="window.location.href='<?= url('/admin/candidates?election_id=') ?>' + this.value"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-indigo-300 bg-indigo-50/40 text-slate-900 font-bold">
                                <?php foreach ($elections as $elOpt): ?>
                                    <option value="<?= (int) $elOpt['id'] ?>" <?= ((int) $election['id'] === (int) $elOpt['id']) ? 'selected' : '' ?>>
                                        <?= e($elOpt['title']) ?> (<?= e($elOpt['organization_name']) ?> &bull; <?= e($elOpt['period_label']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Nomor Urut</label>
                            <input type="number" name="candidate_number" min="1" max="20" required
                                   value="<?= e($editCandidate['candidate_number'] ?? (count($candidates) + 1)) ?>"
                                   class="w-full px-3 py-2.5 rounded-xl border border-slate-300 font-bold">
                        </div>
                        <div class="col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">NIM Calon Ketua</label>
                            <input type="text" name="nim" required placeholder="Contoh: 220101011"
                                   value="<?= e($editCandidate['nim'] ?? '') ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Nama Lengkap Calon Ketua</label>
                        <input type="text" name="name" required placeholder="Masukkan nama lengkap beserta gelar jika ada"
                               value="<?= e($editCandidate['name'] ?? '') ?>"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-bold text-slate-700 mb-1">Jenjang</label>
                            <select name="jenjang" class="w-full px-3 py-2.5 rounded-xl border border-slate-300 bg-white">
                                <?php foreach (['S1', 'D4', 'D3', 'S2'] as $j): ?>
                                    <option value="<?= $j ?>" <?= ($editCandidate['jenjang'] ?? 'S1') === $j ? 'selected' : '' ?>><?= $j ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block font-bold text-slate-700 mb-1">Program Studi</label>
                            <input type="text" name="program_studi" required placeholder="Contoh: Teknik Informatika"
                                   value="<?= e($editCandidate['program_studi'] ?? '') ?>"
                                   class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Tagline / Slogan Kampanye</label>
                        <input type="text" name="tagline" placeholder="Contoh: Kampus Kolaboratif dan Inovatif"
                               value="<?= e($editCandidate['tagline'] ?? '') ?>"
                               class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Foto Kandidat (Opsional - JPG/PNG/WEBP)</label>
                        <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp"
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700 border border-slate-300 rounded-xl p-1.5">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Visi Calon Ketua</label>
                        <textarea name="vision" rows="3" required placeholder="Tuliskan visi utama calon ketua..."
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300"><?= e($editCandidate['vision'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Misi & Program Kerja (Pisahkan dengan baris baru)</label>
                        <textarea name="mission" rows="4" required placeholder="1. Misi pertama...&#10;2. Misi kedua..."
                                  class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300"><?= e($editCandidate['mission'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" class="w-full py-3 px-5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs shadow-md transition">
                        <?= $editCandidate ? 'Simpan Perubahan Kandidat' : 'Tambahkan Calon Ketua' ?>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Daftar Calon Ketua -->
    <div class="lg:col-span-7 space-y-4">
        <div class="bg-white rounded-3xl p-5 border border-slate-200/80 shadow-sm flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Daftar Kandidat Terdaftar</h3>
                <p class="text-xs text-slate-500">Periode: <?= e($election['title'] ?? '-') ?></p>
            </div>

            <form action="<?= url('/admin/candidates') ?>" method="GET" class="flex items-center gap-2">
                <select name="election_id" onchange="this.form.submit()" class="px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold bg-white">
                    <?php foreach ($elections as $el): ?>
                        <option value="<?= (int) $el['id'] ?>" <?= ($election && (int) $election['id'] === (int) $el['id']) ? 'selected' : '' ?>>
                            <?= e($el['title']) ?> (<?= e($el['period_label']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>

        <?php if (empty($candidates)): ?>
            <div class="bg-white rounded-3xl p-10 text-center border border-slate-200/80 text-xs text-slate-400">
                Belum ada calon ketua pada periode pemilihan ini.
            </div>
        <?php else: ?>
            <?php foreach ($candidates as $cand): ?>
                <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row items-start justify-between gap-5">
                    <div class="flex items-start gap-4">
                        <?php if (!empty($cand['photo_path'])): ?>
                            <img src="<?= asset_url((string) $cand['photo_path']) ?>" alt="<?= e($cand['name']) ?>"
                                 class="w-16 h-16 rounded-2xl object-cover border border-slate-200 shrink-0">
                        <?php else: ?>
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white font-extrabold text-xl flex items-center justify-center shrink-0">
                                0<?= (int) $cand['candidate_number'] ?>
                            </div>
                        <?php endif; ?>

                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2.5 py-0.5 rounded-lg bg-slate-900 text-white font-extrabold text-[11px]">
                                    No. Urut 0<?= (int) $cand['candidate_number'] ?>
                                </span>
                                <span class="px-2.5 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 font-bold text-[11px]">
                                    <?= e($cand['jenjang']) ?> - <?= e($cand['program_studi']) ?>
                                </span>
                                <span class="text-xs text-slate-400 font-mono">NIM: <?= e($cand['nim']) ?></span>
                            </div>
                            <h4 class="text-base font-extrabold text-slate-900"><?= e($cand['name']) ?></h4>
                            <?php if (!empty($cand['tagline'])): ?>
                                <p class="text-xs text-indigo-600 italic font-medium">"<?= e($cand['tagline']) ?>"</p>
                            <?php endif; ?>
                            <p class="text-xs text-slate-600 pt-1"><strong>Visi:</strong> <?= e($cand['vision']) ?></p>
                            <div class="text-xs text-slate-500 whitespace-pre-line"><strong>Misi:</strong><?= "\n" . e($cand['mission']) ?></div>
                        </div>
                    </div>

                    <div class="flex sm:flex-col items-end gap-2 shrink-0 w-full sm:w-auto justify-end border-t sm:border-t-0 pt-3 sm:pt-0 border-slate-100">
                        <span class="px-3 py-1 rounded-xl bg-emerald-50 text-emerald-700 font-extrabold text-xs">
                            <?= (int) $cand['vote_count'] ?> Suara
                        </span>

                        <div class="flex items-center gap-1.5">
                            <a href="<?= url('/admin/candidates?election_id=' . $cand['election_id'] . '&edit_id=' . $cand['id']) ?>"
                               class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-700 font-bold text-xs transition">
                                Edit
                            </a>

                            <?php if ((int) $cand['vote_count'] === 0): ?>
                                <form action="<?= url('/admin/candidates/delete') ?>" method="POST"
                                      onsubmit="return confirm('Hapus kandidat <?= e(addslashes((string) $cand['name'])) ?>?')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $cand['id'] ?>">
                                    <button type="submit" class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs transition">
                                        Hapus
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

