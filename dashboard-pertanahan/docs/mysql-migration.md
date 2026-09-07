# MySQL/MariaDB: konfigurasi, migrasi, dan pengujian

## Status 2026-09-05

Semua jalur aplikasi memakai PDO MySQL melalui `db()` dan `DatabaseConnection`.
Database development lokal sudah dibuat, schema diterapkan, dan 10 snapshot serta
3 identitas demo disalin secara eksplisit dari SQLite. Tidak ada perubahan schema
atau seeding otomatis saat halaman dibuka. SQLite asli tetap disimpan sebagai arsip.
Ini bukan deployment production.

## Konfigurasi

Salin `.env.example` ke `.env`, lalu isi `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`,
dan `DB_PASS` sesuai database yang disediakan hosting. Semua variabel tersebut wajib
ada; password kosong hanya diterima bila ditulis secara eksplisit. Charset koneksi
selalu utf8mb4. `DB_DRIVER` hanya menerima mysql. Tidak ada fallback SQLite.

`config/environment.php` membaca pasangan KEY=value, komentar pada baris tersendiri,
dan nilai literal berkutip. Tidak ada interpolasi variabel atau eksekusi perintah.
Environment server didahulukan, termasuk nilai kosong. Gunakan `APP_ENV=production`
di hosting; development lokal memakai `APP_ENV=development`. Jangan commit `.env`.

Apache harus mengaktifkan AllowOverride dan mod_rewrite untuk `.htaccess` proyek.
Aturan tersebut memblokir dotfiles, SQLite, SQL, dan direktori internal. PHP server
lokal wajib memakai router yang disediakan:

```powershell
php -S localhost:8000 router.php
```

## Schema dan akun

Database dibuat terlebih dahulu melalui panel hosting atau administrator database.
Jalankan dari root proyek:

```powershell
php database/migrate.php
```

Perintah ini menerapkan `database/schema.sql`, `database/seeders/reference_seed.sql`,
dan `database/seeders/regions_jawa_barat.sql`, berurutan. Tanpa CLI di hosting,
ketiga file SQL dapat diimpor melalui phpMyAdmin dalam urutan yang sama.
Baseline dapat dijalankan ulang tanpa menggandakan tabel/referensi; perubahan
struktur berikutnya memerlukan migration ALTER yang berversi, bukan mengandalkan
CREATE TABLE IF NOT EXISTS untuk mengubah tabel lama.

Tidak ada akun dibuat oleh migration. Untuk membuat administrator baru, set
`ADMIN_NAME`, `ADMIN_EMAIL`, dan `ADMIN_PASSWORD` melalui environment sementara lalu
jalankan `php database/create-admin.php`; hapus variabel tersebut setelah selesai.
Password minimal 12 karakter, disimpan dengan password_hash, dan tidak dicetak.
Hosting tanpa CLI memerlukan pembuatan akun lewat administrasi database dengan hash
yang dihasilkan PHP; jangan menyimpan password plaintext pada tabel users.

Data demo hanya untuk development/testing:

```powershell
php database/import-demo-sqlite.php --demo
```

Perintah menolak production dan target yang sudah berisi users/metric_snapshots.
Referensi wilayah yang sudah dibuat migration boleh ada. Password hash demo disalin
dari arsip; tidak ada password demo baru tertanam di source atau ditampilkan di UI.
Seeder ini bukan alat migrasi data bisnis umum. Jangan menjalankannya di production.

## Model data dan workbook

- `metric_snapshots` menyimpan empat metrik dashboard yang ada dengan FK ke
  `regions.code`, unik per wilayah/tanggal/sumber. Snapshot Pemda menggantikan KKP
  dalam agregasi untuk wilayah dan tanggal sama.
- `regions.province_id` terhubung ke `provinces`; `users.role_id` ke `roles`.
- Excel masuk ke `report_snapshots` dan `indicator_values`, sebagai **draft**;
  bukan pengganti otomatis total objek/validasi pada dashboard. Tampilan indikator
  baru memerlukan pekerjaan terpisah.
- 27 kode wilayah Jawa Barat pada seeder dicocokkan dengan
  [tabel wilayah BPS](https://sensus.bps.go.id/topik/tabular/sp2020/1/13/0).
  Workbook yang tersedia memiliki 25 baris wilayah; dua wilayah tanpa baris tidak
  diberi angka buatan.
- Mapping eksplisit 52 kolom indikator C-BB ada di
  `data/mappings/excel-jawa-barat.php`. A adalah nomor, B adalah wilayah.
  Header workbook `DAREAH` diterima. F dan T adalah persentase berbeda;
  U adalah aset sudah sertipikat, bukan total aset; AQ/AR merupakan persentase.
- Periode laporan wajib dipilih pengguna dan disimpan sebagai tanggal harian
  (`report_periods.period_type=custom`). Tahun indikator tidak dianggap periode.
  Tahun ZNT/kawasan kumuh disimpan sebagai teks tahun, bukan tanggal yang direka.
- XML numeric memakai titik desimal; angka teks menerima pemisah ribuan Indonesia
  atau Inggris yang konsisten. Pada angka teks ambigu seperti 1.234 atau 1,234,
  kelompok tiga digit dianggap ribuan. Nilai mentah selalu disimpan untuk audit.
  Persentase Excel berformat persen dikalikan 100; angka persen yang sudah berada
  pada skala 0-100 dipertahankan. Tidak ada pemaksaan persen >100 menjadi nilai lain.
- Formula memakai nilai cache workbook, bukan dihitung ulang. Formula tanpa cache
  atau error Excel ditolak. Baris total/catatan tanpa nomor wilayah tidak diimpor.
- Impor atomik; kegagalan membatalkan perubahan. Impor ulang memperbarui nilai
  termasuk null, tanpa menggandakan snapshot. Raw cell, formula, checksum, periode,
  batch, dan pengguna pengimpor disimpan.

## Pengujian

Runtime teruji: PHP 7.4.33 XAMPP, PDO/pdo_mysql, zip, SimpleXML, MariaDB 10.4.27.
Importer tidak membutuhkan Python atau Composer. cURL diperlukan untuk KKP live
dan pengujian HTTP. Endpoint KKP live belum dikonfigurasi atau diuji.

```powershell
php tests/EnvironmentTest.php
php tests/DashboardDataServiceTest.php
$env:APP_ENV='testing'
$env:DB_NAME='dashboard_pertanahan_test'
php database/migrate.php
php tests/ExcelWorkbookImporterTest.php
php tests/MySqlIntegrationTest.php
php -S 127.0.0.1:8094 router.php
```

Pada terminal kedua, set APP_ENV dan DB_NAME testing yang sama, lalu jalankan
`php tests/HttpSmokeTest.php`. Hentikan server setelah selesai. Untuk Apache lokal
dengan konfigurasi testing yang sama, set TEST_BASE_URL ke alamat localhost Apache.
Test MySQL/HTTP menolak APP_ENV lain dan nama database tanpa akhiran `_test`.
Tanggal 2097-2099 dan akun example.invalid adalah fixture khusus database testing.
Tes importer memerlukan workbook lokal yang tidak dilacak Git; file hasil tes
berada di `tmp/`, yang diabaikan Git dan diblokir dari HTTP.

`DashboardDataServiceTest.php` memakai SQLite in-memory secara eksplisit sebagai
fixture unit test; `MySqlIntegrationTest.php` menguji service yang sama pada MariaDB.
Keduanya tidak memakai SQLite sebagai koneksi aplikasi atau fallback.
