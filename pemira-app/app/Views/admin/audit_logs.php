<?php declare(strict_types=1); ?>

<div class="mb-6 bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <span class="text-xs font-bold uppercase tracking-wider text-indigo-600">Penelusuran Keamanan & Administrasi (FR-15)</span>
        <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 mt-0.5">Log Audit Aktivitas Sistem</h3>
        <p class="text-xs text-slate-500"></p>
    </div>

    <form action="<?= url('/admin/audit-logs') ?>" method="GET" class="w-full sm:w-auto">
        <select name="action" onchange="this.form.submit()" class="w-full sm:w-auto px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-bold bg-white">
            <option value="">Semua Kategori Aktivitas</option>
            <?php foreach ($actions as $act): ?>
                <option value="<?= e($act) ?>" <?= $actionFilter === $act ? 'selected' : '' ?>><?= e($act) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>

<div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
    <?php if (empty($logs)): ?>
        <div class="py-10 text-center text-xs text-slate-400">Belum ada catatan aktivitas.</div>
    <?php else: ?>
        <!-- Tampilan Mobile Cards (< md) -->
        <div class="md:hidden divide-y divide-slate-100 text-xs">
            <?php foreach ($logs as $log): ?>
                <div class="p-4 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <code class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono font-bold text-[11px]">
                            <?= e($log['action']) ?>
                        </code>
                        <span class="text-[11px] text-slate-400"><?= format_indo_datetime($log['created_at']) ?></span>
                    </div>
                    <p class="text-slate-700 font-medium leading-relaxed"><?= e($log['description']) ?></p>
                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-1">
                        <span>Oleh: <strong class="text-slate-800"><?= e($log['actor_name']) ?></strong> (<?= e(strtoupper((string) $log['actor_role'])) ?>)</span>
                        <span class="font-mono text-slate-400"><?= e($log['ip_address']) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tampilan Tabel Desktop (md+) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[11px] font-bold uppercase text-slate-500">
                        <th class="py-3.5 px-4">Waktu (WIB)</th>
                        <th class="py-3.5 px-4">Aktor / Pengguna</th>
                        <th class="py-3.5 px-4">Role</th>
                        <th class="py-3.5 px-4">Aksi</th>
                        <th class="py-3.5 px-4">Deskripsi Aktivitas</th>
                        <th class="py-3.5 px-4">Alamat IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-xs">
                    <?php foreach ($logs as $log): ?>
                        <tr class="hover:bg-slate-50">
                            <td class="py-3.5 px-4 whitespace-nowrap text-slate-500"><?= format_indo_datetime($log['created_at']) ?></td>
                            <td class="py-3.5 px-4 font-bold text-slate-900"><?= e($log['actor_name']) ?></td>
                            <td class="py-3.5 px-4">
                                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold <?= $log['actor_role'] === 'admin' ? 'bg-violet-50 text-violet-700' : ($log['actor_role'] === 'student' ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-600') ?>">
                                    <?= e(strtoupper((string) $log['actor_role'])) ?>
                                </span>
                            </td>
                            <td class="py-3.5 px-4">
                                <code class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono font-bold text-[11px]">
                                    <?= e($log['action']) ?>
                                </code>
                            </td>
                            <td class="py-3.5 px-4 text-slate-700"><?= e($log['description']) ?></td>
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-400"><?= e($log['ip_address']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
