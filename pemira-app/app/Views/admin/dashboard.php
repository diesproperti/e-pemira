<?php
declare(strict_types=1);

$candidates = $results['candidates'] ?? [];
$totalVotes = (int) ($results['total_votes'] ?? 0);

$chartLabels = [];
$chartData   = [];
foreach ($candidates as $c) {
    $chartLabels[] = '0' . $c['candidate_number'] . ' - ' . $c['name'];
    $chartData[]   = (int) $c['vote_count'];
}
$allElections    = $allElections ?? [];
$activeElections = $activeElections ?? [];
$currentId       = (int) ($election['id'] ?? 0);

$dashHashItems = [];
foreach ($allElections as $eItem) {
    $ts = \App\Models\Election::getTimeState($eItem);
    $dashHashItems[] = [
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
$dashInitialHash = md5((string) json_encode($dashHashItems));
?>

<?php if (count($allElections) > 1): ?>
<div class="mb-5 bg-white rounded-3xl p-4 sm:p-5 border border-slate-200/80 shadow-sm">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-3">
        <div class="flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                Pantau Periode Pemilihan 
            </h3>
        </div>
        <a href="<?= url('/admin/elections') ?>" class="text-xs font-bold text-indigo-600 hover:underline">
            Kelola Semua Jadwal Pemilihan &rarr;
        </a>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
        <?php foreach ($allElections as $elItem): ?>
            <?php
                $isCurrent = ((int) $elItem['id'] === $currentId);
                $elState   = \App\Models\Election::getTimeState($elItem);
            ?>
            <a href="<?= url('/admin/dashboard?election_id=' . (int) $elItem['id']) ?>"
               class="p-3.5 rounded-2xl border transition flex items-start justify-between gap-3 <?= $isCurrent ? 'bg-indigo-600 text-white border-indigo-600 shadow-md shadow-indigo-500/20' : 'bg-slate-50/80 hover:bg-indigo-50/50 text-slate-800 border-slate-200' ?>">
                <div class="min-w-0">
                    <span class="text-[10px] font-bold uppercase tracking-wider <?= $isCurrent ? 'text-indigo-200' : 'text-indigo-600' ?> block truncate">
                        <?= e($elItem['organization_name']) ?> &bull; <?= e($elItem['period_label']) ?>
                    </span>
                    <p class="text-xs font-extrabold truncate mt-0.5"><?= e($elItem['title']) ?></p>
                    <p class="text-[11px] <?= $isCurrent ? 'text-indigo-100' : 'text-slate-500' ?> mt-1">
                        <?= (int) ($elItem['candidate_count'] ?? 0) ?> Calon &bull; <strong><?= (int) ($elItem['vote_count'] ?? 0) ?> Suara</strong>
                    </p>
                </div>
                <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold <?= $elState['can_vote'] ? ($isCurrent ? 'bg-emerald-400/25 text-emerald-100 border border-emerald-300/30' : 'bg-emerald-100 text-emerald-700') : ($isCurrent ? 'bg-white/15 text-white' : 'bg-slate-200 text-slate-600') ?>">
                    <?= $elState['can_vote'] ? '● Aktif' : e(ucfirst((string) $elItem['status'])) ?>
                </span>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Active Election Banner -->
<div class="mb-6 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-3xl p-6 sm:p-7 text-white shadow-lg flex flex-col lg:flex-row lg:items-center justify-between gap-5">
    <div>
        <div class="flex flex-wrap items-center gap-2 mb-2">
            <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-white/10 text-indigo-200 border border-white/15">
                <?= e($election['organization_name'] ?? 'Organisasi Kampus') ?> &bull; Periode <?= e($election['period_label'] ?? '-') ?>
            </span>
            <?php if (($timeState['can_vote'] ?? false) === true): ?>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                    <?= e($timeState['label']) ?>
                </span>
            <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-500/20 text-amber-300 border border-amber-400/30">
                    <?= e($timeState['label'] ?? 'Tidak Aktif') ?>
                </span>
            <?php endif; ?>
        </div>
        <h3 class="text-xl sm:text-2xl font-extrabold"><?= e($election['title'] ?? 'Belum Ada Periode Pemilihan') ?></h3>
        <?php if ($election): ?>
            <p class="text-xs text-slate-300 mt-1">
                Jadwal: <strong><?= format_indo_datetime($election['start_time']) ?></strong> s.d. <strong><?= format_indo_datetime($election['end_time']) ?></strong>
            </p>
        <?php endif; ?>
    </div>

    <div class="flex flex-wrap items-center gap-2.5 shrink-0">
        <a href="<?= url('/admin/voters') ?>" class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold border border-white/15 transition">
            Import Dataset CSV
        </a>
        <a href="<?= url('/admin/results') ?>" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-md transition">
            Lihat Laporan Lengkap
        </a>
    </div>
</div>

<!-- KPI Summary Cards (FR-13, Bab 12) -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-7">
    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Mahasiswa Berhak Pilih</span>
            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-slate-900"><?= number_format((int) $stats['total_eligible']) ?></div>
        <p class="text-xs text-slate-500 mt-1">
            Dari total <?= number_format((int) $stats['total_dataset']) ?> dataset (<?= (int) $stats['total_non_eligible'] ?> Cuti/Non-Aktif)
        </p>
    </div>

    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Sudah Memilih (Suara Masuk)</span>
            <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-emerald-600"><?= number_format((int) $stats['total_voted']) ?></div>
        <p class="text-xs text-slate-500 mt-1">
            Tingkat Partisipasi: <strong class="text-emerald-700"><?= e($stats['turnout_percent']) ?>%</strong>
        </p>
    </div>

    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Belum Memilih</span>
            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-amber-600"><?= number_format((int) $stats['total_not_voted']) ?></div>
        <p class="text-xs text-slate-500 mt-1">Mahasiswa aktif yang belum memberikan suara</p>
    </div>

    <div class="bg-white rounded-3xl p-5 sm:p-6 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-3">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Jumlah Calon Ketua</span>
            <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
        </div>
        <div class="text-3xl font-extrabold text-slate-900"><?= count($candidates) ?></div>
        <p class="text-xs text-slate-500 mt-1">Kandidat terdaftar pada periode ini</p>
    </div>
</div>

<!-- Charts & Candidate Perolehan Suara -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-7">
    <!-- Perolehan Suara Per Kandidat -->
    <div class="lg:col-span-7 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Grafik Perolehan Suara Kandidat</h3>
                <p class="text-xs text-slate-500">Rekapitulasi real-time dari kotak suara anonim (`votes`)</p>
            </div>
            <span class="px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 font-bold text-xs">
                Total: <?= $totalVotes ?> Suara
            </span>
        </div>

        <div class="h-64 mb-6">
            <canvas id="voteBarChart"></canvas>
        </div>

        <div class="space-y-3 pt-4 border-t border-slate-100">
            <?php foreach ($candidates as $cand): ?>
                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/70 flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="w-9 h-9 rounded-xl bg-slate-900 text-white font-extrabold text-xs flex items-center justify-center shrink-0">
                            0<?= (int) $cand['candidate_number'] ?>
                        </span>
                        <div>
                            <p class="text-sm font-extrabold text-slate-900"><?= e($cand['name']) ?></p>
                            <p class="text-xs text-slate-500"><?= e($cand['jenjang']) ?> - <?= e($cand['program_studi']) ?> (NIM: <?= e($cand['nim']) ?>)</p>
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <span class="text-base font-extrabold text-indigo-600"><?= (int) $cand['vote_count'] ?> Suara</span>
                        <span class="block text-xs font-bold text-slate-500"><?= e($cand['percentage']) ?>%</span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Partisipasi Per Program Studi -->
    <div class="lg:col-span-5 bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm flex flex-col justify-between">
        <div>
            <h3 class="text-base font-extrabold text-slate-900 mb-1">Partisipasi Pemilih Tetap</h3>
            <p class="text-xs text-slate-500 mb-5">Distribusi Kehadiran Pemilih Tetap </p>

            <div class="space-y-4">
                <?php if (empty($prodiTurnout)): ?>
                    <p class="text-xs text-slate-400">Belum ada data program studi.</p>
                <?php else: ?>
                    <?php foreach ($prodiTurnout as $pt): ?>
                        <div>
                            <div class="flex items-center justify-between text-xs mb-1.5">
                                <span class="font-bold text-slate-800"><?= e($pt['jenjang']) ?> - <?= e($pt['program_studi']) ?></span>
                                <span class="font-semibold text-slate-600">
                                    <?= (int) $pt['total_voted'] ?> / <?= (int) $pt['total_eligible'] ?> (<?= e($pt['percentage']) ?>%)
                                </span>
                            </div>
                            <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-indigo-600 to-violet-600 rounded-full" style="width: <?= min(100, (float) $pt['percentage']) ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
            <span class="text-slate-500">Ingin mengunduh laporan lengkap?</span>
            <a href="<?= url('/admin/results') ?>" class="font-bold text-indigo-600 hover:text-indigo-700">Buka Rekapitulasi &rarr;</a>
        </div>
    </div>
</div>

<!-- Recent Voter Attendance & Recent Audit Logs -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Daftar Hadir Pemilih Terbaru -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Daftar Hadir Pemilih Terbaru</h3>
                <p class="text-xs text-slate-500">Mencatat waktu memilih tanpa menampilkan kandidat pilihan</p>
            </div>
            <a href="<?= url('/admin/voters?voting_status=voted') ?>" class="text-xs font-bold text-indigo-600 hover:underline">Lihat Semua</a>
        </div>

        <?php if (empty($recentVoters)): ?>
            <div class="py-8 text-center text-xs text-slate-400">Belum ada suara yang masuk pada periode ini.</div>
        <?php else: ?>
            <div class="divide-y divide-slate-100 text-xs">
                <?php foreach ($recentVoters as $rv): ?>
                    <div class="py-3 flex items-center justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-800"><?= e($rv['nama']) ?> <span class="text-slate-400 font-normal">(<?= e($rv['nim']) ?>)</span></p>
                            <p class="text-slate-500"><?= e($rv['jenjang']) ?> - <?= e($rv['program_studi']) ?></p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="inline-block font-mono text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                <?= e($rv['receipt_code']) ?>
                            </span>
                            <p class="text-[11px] text-slate-400 mt-0.5"><?= format_indo_datetime($rv['voted_at']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Log Aktivitas Administrasi Terbaru -->
    <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900">Audit Aktivitas Terbaru</h3>
                <p class="text-xs text-slate-500">Jejak aktivitas keamanan & administrasi sistem</p>
            </div>
            <a href="<?= url('/admin/audit-logs') ?>" class="text-xs font-bold text-indigo-600 hover:underline">Semua Log</a>
        </div>

        <div class="divide-y divide-slate-100 text-xs">
            <?php foreach ($recentAudits as $log): ?>
                <div class="py-3 flex items-start justify-between gap-3">
                    <div>
                        <span class="inline-block px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-mono font-bold text-[10px] mb-1">
                            <?= e($log['action']) ?>
                        </span>
                        <p class="text-slate-700 font-medium"><?= e($log['description']) ?></p>
                    </div>
                    <span class="text-[11px] text-slate-400 shrink-0"><?= format_indo_datetime($log['created_at']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
    (function() {
        const ctx = document.getElementById('voteBarChart');
        if (!ctx) return;
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'Jumlah Suara Sah',
                    data: <?= json_encode($chartData) ?>,
                    backgroundColor: ['#4f46e5', '#7c3aed', '#0ea5e9', '#10b981'],
                    borderRadius: 12,
                    maxBarThickness: 56
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0 }
                    }
                }
            }
        });
    })();

    (function() {
        const initialHash = <?= json_encode($dashInitialHash) ?>;
        const liveUrl = <?= json_encode(url('/admin/elections/live-status')) ?>;
        let isReloading = false;

        async function pollDashboardLive() {
            if (isReloading || !liveUrl) return;
            try {
                const res = await fetch(liveUrl + '?_t=' + Date.now(), {
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });
                if (!res.ok) return;
                const data = await res.json();
                if (data && data.state_hash && data.state_hash !== initialHash) {
                    isReloading = true;
                    window.location.reload();
                }
            } catch (e) {
                // Ignore transient errors
            }
        }

        setInterval(pollDashboardLive, 3000);
    })();
</script>

