<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 Halaman Tidak Ditemukan — PEMIRA Kampus</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-3xl p-8 text-center shadow-xl border border-slate-100">
        <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-600 font-extrabold text-2xl flex items-center justify-center mx-auto mb-4">
            404
        </div>
        <h1 class="text-xl font-extrabold text-slate-900 mb-2">Halaman Tidak Ditemukan</h1>
        <p class="text-sm text-slate-500 mb-6">Tautan yang Anda tuju tidak tersedia atau telah dipindahkan.</p>
        <a href="<?= url('/') ?>" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700 transition">
            Kembali ke Beranda
        </a>
    </div>
</body>
</html>

