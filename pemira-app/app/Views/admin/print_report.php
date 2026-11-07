<?php
declare(strict_types=1);
$candidates = $results['candidates'] ?? [];
$totalVotes = (int) ($results['total_votes'] ?? 0);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Rekapitulasi Hasil Pemilihan — <?= e($election['title'] ?? 'PEMIRA') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 p-6 sm:p-10">

    <div class="max-w-4xl mx-auto mb-5 flex items-center justify-between no-print">
        <a href="<?= url('/admin/results') ?>" class="text-xs font-bold text-slate-600 hover:text-indigo-600">&larr; Kembali ke Panel Admin</a>
        <button type="button" onclick="window.print()" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-extrabold shadow-md">
            Cetak Dokumen / Simpan PDF
        </button>
    </div>

    <div class="max-w-4xl mx-auto bg-white p-8 sm:p-12 rounded-2xl shadow-sm border border-slate-200">
        <!-- Kop Berita Acara -->
        <div class="text-center border-b-2 border-slate-900 pb-5 mb-6">
            <h1 class="text-lg font-extrabold uppercase tracking-wide">KOMISI PEMILIHAN RAYA MAHASISWA (KPRM)</h1>
            <h2 class="text-xl font-extrabold uppercase mt-1"><?= e($election['title'] ?? 'PEMILIHAN KETUA ORGANISASI KAMPUS') ?></h2>
            <p class="text-xs text-slate-600 mt-1">
                Organisasi: <strong><?= e($election['organization_name'] ?? '-') ?></strong> &bull;
                Periode: <strong><?= e($election['period_label'] ?? '-') ?></strong>
            </p>
        </div>

        <div class="mb-6 text-xs leading-relaxed space-y-2">
            <p>
                Pada hari ini, tanggal <strong><?= format_indo_datetime(date('Y-m-d H:i:s')) ?></strong>, telah dilaksanakan rekapitulasi hasil perhitungan suara secara elektronik melalui <strong>Sistem Pemilihan Ketua Organisasi Kampus Berbasis Web</strong> dengan rincian waktu pelaksanaan:
            </p>
            <div class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                <div>
                    <span class="text-slate-500 block">Waktu Mulai Pemilihan:</span>
                    <strong><?= format_indo_datetime($election['start_time'] ?? null) ?></strong>
                </div>
                <div>
                    <span class="text-slate-500 block">Waktu Berakhir Pemilihan:</span>
                    <strong><?= format_indo_datetime($election['end_time'] ?? null) ?></strong>
                </div>
            </div>
        </div>

        <!-- I. Data Penggunaan Hak Pilih -->
        <h3 class="text-sm font-extrabold uppercase tracking-wider mb-3">I. Ringkasan Data Pemilih & Penggunaan Hak Pilih</h3>
        <table class="w-full text-xs border border-slate-300 mb-6">
            <tbody>
                <tr class="border-b border-slate-300">
                    <td class="p-2.5 font-semibold bg-slate-50 w-2/3">1. Total Mahasiswa Memiliki Hak Pilih (Mahasiswa Aktif)</td>
                    <td class="p-2.5 font-bold text-right"><?= number_format((int) $stats['total_eligible']) ?> Mahasiswa</td>
                </tr>
                <tr class="border-b border-slate-300">
                    <td class="p-2.5 font-semibold bg-slate-50">2. Total Mahasiswa yang Menggunakan Hak Pilih (Sudah Memilih)</td>
                    <td class="p-2.5 font-bold text-right"><?= number_format((int) $stats['total_voted']) ?> Mahasiswa</td>
                </tr>
                <tr class="border-b border-slate-300">
                    <td class="p-2.5 font-semibold bg-slate-50">3. Total Mahasiswa yang Belum Menggunakan Hak Pilih</td>
                    <td class="p-2.5 font-bold text-right"><?= number_format((int) $stats['total_not_voted']) ?> Mahasiswa</td>
                </tr>
                <tr class="border-b border-slate-300">
                    <td class="p-2.5 font-semibold bg-slate-50">4. Total Suara Sah Masuk di Kotak Suara Digital</td>
                    <td class="p-2.5 font-extrabold text-right"><?= number_format($totalVotes) ?> Suara</td>
                </tr>
                <tr>
                    <td class="p-2.5 font-semibold bg-slate-50">5. Persentase Tingkat Partisipasi Pemilih</td>
                    <td class="p-2.5 font-extrabold text-right"><?= e($stats['turnout_percent']) ?>%</td>
                </tr>
            </tbody>
        </table>

        <!-- II. Hasil Perolehan Suara Calon Ketua -->
        <h3 class="text-sm font-extrabold uppercase tracking-wider mb-3">II. Perolehan Suara Masing-Masing Calon Ketua</h3>
        <table class="w-full text-xs border border-slate-300 mb-8">
            <thead>
                <tr class="bg-slate-100 border-b border-slate-300 font-bold">
                    <th class="p-2.5 border-r border-slate-300 text-center w-20">No. Urut</th>
                    <th class="p-2.5 border-r border-slate-300 text-left">Nama Lengkap Calon Ketua</th>
                    <th class="p-2.5 border-r border-slate-300 text-left">NIM</th>
                    <th class="p-2.5 border-r border-slate-300 text-left">Program Studi</th>
                    <th class="p-2.5 border-r border-slate-300 text-right">Jumlah Suara</th>
                    <th class="p-2.5 text-right">Persentase</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($candidates as $cand): ?>
                    <tr class="border-b border-slate-300">
                        <td class="p-2.5 border-r border-slate-300 text-center font-extrabold">0<?= (int) $cand['candidate_number'] ?></td>
                        <td class="p-2.5 border-r border-slate-300 font-bold"><?= e($cand['name']) ?></td>
                        <td class="p-2.5 border-r border-slate-300 font-mono"><?= e($cand['nim']) ?></td>
                        <td class="p-2.5 border-r border-slate-300"><?= e($cand['jenjang']) ?> - <?= e($cand['program_studi']) ?></td>
                        <td class="p-2.5 border-r border-slate-300 text-right font-bold"><?= number_format((int) $cand['vote_count']) ?> Suara</td>
                        <td class="p-2.5 text-right font-bold"><?= e($cand['percentage']) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Signature Blocks -->
        <div class="grid grid-cols-2 gap-12 pt-8 text-center text-xs">
            <div>
                <p class="mb-16">Mengetahui,<br><strong> Pembina Kemahasiswaan / Perwakilan Kampus</strong></p>
                <p class="font-bold underline">( ........................................................ )</p>
                <p class="text-slate-500 mt-0.5">NIP / NIDN.</p>
            </div>
            <div>
                <p class="mb-16">Ditetapkan oleh,<br><strong>Ketua Komisi Pemilihan Raya Mahasiswa (KPRM)</strong></p>
                <p class="font-bold underline">( ........................................................ )</p>
                <p class="text-slate-500 mt-0.5">NIM.</p>
            </div>
        </div>
    </div>

</body>
</html>

