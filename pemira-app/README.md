# Sistem Pemilihan Ketua Organisasi Kampus Berbasis Web (`pemira-app`)

Aplikasi web E-Voting Pemilihan Raya Mahasiswa (PEMIRA) yang dirancang dengan prinsip **One Student One Vote** (1 Mahasiswa 1 Suara) dan **Secret Ballot** (Kerahasiaan Suara Penuh).

## 1. Spesifikasi Teknis
- **Bahasa Pemrograman**: PHP 8.4 / 8.5 (`strict_types=1`, Arsitektur MVC Modern)
- **Database**: MySQL 8.0+ / MariaDB (`pemira_db`) dengan *Auto-Migration & Seeding* (serta dukungan *auto-fallback* SQLite)
- **Antarmuka (UI/UX)**: Tailwind CSS & Chart.js (Modern, Elegan, Responsif, & Ramah Pengguna)

## 2. Fitur Utama (Sesuai PRD)
- **Import & Validasi Dataset Mahasiswa (FR-01, FR-02, AC-07)**:
  Mendukung unggah file `.csv` dengan struktur kolom:
  `NIM, Nama, Jenjang, Program Studi, Periode masuk, Status Mahasiswa, Status Semester`
- **Validasi Hak Pilih Real-time (AC-01)**:
  Hanya mahasiswa dengan `Status Mahasiswa = Aktif` dan `Status Semester = Aktif` yang diizinkan masuk dan memilih.
- **Kustomisasi Password Mandiri Mahasiswa**:
  Password awal mahasiswa otomatis disetel sama dengan `NIM` dan mahasiswa disarankan mengganti password saat pertama kali login.
- **Secret Ballot & Pencegahan Voting Ganda (FR-09, FR-10, AC-02, AC-04)**:
  Daftar hadir pemilih (`voter_attendances`) dipisahkan dari kotak suara anonim (`votes`) dalam satu transaksi ACID database.
- **Rekapitulasi, Export Excel/CSV, & Cetak Berita Acara PDF (FR-12, AC-05)**:
  Visualisasi grafik perolehan suara, rekapitulasi partisipasi per program studi, export CSV/Excel, dan cetak Berita Acara.

## 3. Cara Menjalankan di Laragon
1. Pastikan **Apache / Nginx** dan **MySQL** di Laragon telah dijalankan (*Start All*).
2. Buka di browser:
   - `http://pemira-app.test` atau `http://localhost/pemira-app`
3. Pada saat pertama kali diakses, sistem akan **otomatis membuat database `pemira_db`, membuat seluruh tabel, dan mengisi data sampel awal**.
   *(File SQL manual juga tersedia di `database/schema.sql` dan `database/seed.sql`)*

## 4. Akun Demo Pengujian
| Peran | NIM / Username | Password Awal | Keterangan |
| :--- | :--- | :--- | :--- |
| **Administrator KPRM** | `admin` | `admin123` | Akses penuh ke Panel Admin |
| **Mahasiswa Aktif 1** | `230101001` | `230101001` | S1 Teknik Informatika (Berhak Memilih) |
| **Mahasiswa Aktif 2** | `230101002` | `230101002` | S1 Sistem Informasi (Berhak Memilih) |
| **Mahasiswa Cuti** | `220101007` | `220101007` | S1 Teknik Informatika — Cuti (Ditolak Login / AC-01) |
| **Mahasiswa Non-Aktif** | `210102008` | `210102008` | S1 Manajemen — Non-Aktif (Ditolak Login / AC-01) |

