# Audit proyek: Dashboard Pertanahan

Tanggal audit: 2026-09-05

## 1. Ringkasan eksekutif

Proyek ini adalah aplikasi PHP procedural yang sudah memiliki UI dashboard, login berbasis peran, kanal input data Pemda, serta adaptor data KKP dalam mode mock. Aplikasi saat ini berfungsi sebagai prototipe/POC, bukan sebagai sistem data produksi. Arsitektur inti masih sederhana dan aman untuk dipelihara selama tetap dipisahkan antara layer konfigurasi, data akses, layanan, dan tampilan.

Ada satu file Excel sumber utama: `data/source/Input Data Jawa Barat.xlsx` (dan salinan ganda di `DashboardEksekutif/Input Data Jawa Barat.xlsx`). Workbook tersebut sudah berisi struktur indikator pertanahan lengkap, tetapi tidak bersih untuk diimport langsung ke database karena format multi-header, banyak row kosong, cell bertipe teks/angka campur, serta penggunaan formula Excel. Struktur data perlu dinormalisasi sebelum dipindahkan ke MySQL/MariaDB.

## 2. Struktur folder dan arsitektur PHP

### 2.1 Struktur folder

- `index.php` — entry point redirect ke dashboard
- `login.php` — form login + autentikasi demo
- `dashboard.php` — dashboard utama untuk dashboard eksekutif
- `input-data.php` — form input data Pemda
- `sync-kkp.php` — form sinkronisasi KKP mock/live
- `api/kkp.php` — endpoint JSON snapshot KKP
- `db.php` — inisialisasi SQLite + tabel + data demo
- `config.php` — konfigurasi global, session, environment
- `services/KkpClient.php` — adaptor KKP dan normalisasi payload
- `assets/` — CSS/JS UI
- `data/` — SQLite, sessions, mapping example, workbook source
- `config/kkp.env.example` — contoh env KKP
- `DashboardEksekutif/` — duplikasi proyek yang mengandung copy dashboard dan workbook

### 2.2 Framework / arsitektur

- Tidak ada framework resmi terdeteksi.
- Struktur bersifat procedural PHP, dengan fungsi utilitas dan halaman `*.php` langsung mengeksekusi HTML.
- Tidak ada Composer, tidak ada `composer.json`, tidak ada package manager, tidak ada build step.
- Ada pola sederhana: `require_once` file konfigurasi dan data layer, lalu render HTML langsung.
- CRUD dibuat secara langsung dengan `PDO` dan `prepare()`, tanpa ORM atau query builder.
- Ini berfungsi sebagai aplikasi monolitik ringan, bukan arsitektur service-oriented.

### 2.3 Entry point dan routing

- `login.php` adalah pintu masuk aplikasi.
- `index.php` memeriksa login, lalu redirect ke `dashboard.php`.
- `dashboard.php` menampilkan dashboard utama yang berisi ringkasan, tabel kabupaten/kota, serta status integrasi.
- `input-data.php` adalah channel input untuk Pemda dengan validasi numerik dan batasan logika.
- `sync-kkp.php` menjalankan sinkronisasi KKP.
- `api/kkp.php` mengembalikan JSON snapshot KKP sesuai provinsi dan tanggal.
- `logout.php` menghapus sesi.

### 2.4 Dependensi dan versi PHP

- Dependensi yang paling jelas: built-in PHP + PDO SQLite + session + cURL (opsional untuk KKP live).
- Terdapat eksternal font Google di CSS (`Poppins` via `@import`).
- Tidak ada paket NPM/PHP Composer.
- PHP yang paling aman untuk dijadikan target: PHP 8.x; minimum praktis untuk struktur sekarang adalah PHP 7.4+, karena kode menggunakan `declare(strict_types=1)`, `password_hash()`, `random_bytes()`, `hash_equals()`, dan `PDO`.
- Untuk hosting gratis, sangat perlu dicek apakah `pdo_mysql`, `curl`, `openssl`, dan kemampuan menulis ke folder `data/` tersedia.

### 2.5 Cara menjalankan aplikasi

Petunjuk yang ada di README:

- `php -S localhost:8000` (jalankan dari folder aplikasi, biasanya di XAMPP/WAMP)
- buka `http://localhost:8000/login.php`
- akun demo aktif:
  - `joshua@demo.local` / `Demo123!`
  - `admin@demo.local` / `Demo123!`
  - `pemda@demo.local` / `Demo123!`

Aplikasi saat ini mengandalkan sesi file di `data/sessions` dan database SQLite di `data/dashboard.sqlite`.

## 3. Data dummy, query sederhana, konfigurasi, dan komponen UI

### 3.1 Data dummy dan seeding

`db.php` melakukan hal berikut:

- membuat tabel: `users`, `regions`, `metric_snapshots`
- mengisi data demo users dan region
- mengisi snapshot data numerik contoh untuk beberapa kabupaten/kota
- menyimpan password menggunakan `password_hash()`

Data dummy yang ada mencakup:

- user demo: 3 akun peran eksekutif/admin/pemda
- wilayah: provinsi dan kabupaten/kota Jawa Barat, Jawa Timur, DKI Jakarta
- snapshot numerik: `total_records`, `validated_stage_1`, `validated_stage_2`, `area_hectare`, sumber `kkp`/`pemda`

### 3.2 Query/database sederhana

- `db.php` menggunakan `new PDO('sqlite:' . DB_PATH)`
- semua query dibuat dengan `prepare()` dan `execute()`
- `metric_snapshots` punya unique key `UNIQUE(region_code, report_date, source)`
- apabila `input-data.php` menerima data Pemda, maka `INSERT ... ON CONFLICT ... DO UPDATE` digunakan
- `dashboard.php` dan `index.php` melakukan query agregasi per provinsi dan tanggal

### 3.3 Konfigurasi dan kredensial tertanam

- `config.php` mengatur `DB_PATH`, `SESSION_PATH`, KKP mode, base URL, endpoint, token.
- `config/kkp.env.example` berisi placeholder:
  - `KKP_MODE=mock`
  - `KKP_API_BASE_URL=https://contoh-domain-pusdatin.go.id`
  - `KKP_API_BEARER_TOKEN=ganti_dengan_token_produksi`
- `login.php` menampilkan password demo di form HTML sebagai `value="Demo123!"`.
- `README.md` menampilkan akun demo secara eksplisit.
- `db.php` menghasilkan password hash untuk user demo; ini bukan credential produksi, tetapi tetap berisiko jika aplikasi dipindahkan ke hosting publik sebelum dibersihkan.

### 3.4 Komponen grafik/tabel

Komponen visual utama:

- progress ring SVG untuk validasi tahap 1 dan tahap 2
- kartu metrik (total objek, validasi, luas terdata)
- tabel kabupaten/kota dengan status `KKP`/`PEMDA`
- sidebar navigation
- mode gelap/terang via JS

Tidak ada library charting profesional seperti Chart.js, Highcharts, atau Plotly. Grafik yang ada adalah CSS/SVG custom dan tabel HTML.

## 4. Audit workbook Excel: ukuran, sheet, header, kolom, tipe, formula, data, kosong, duplikat, inkonsistensi

### 4.1 Lokasi file

- `data/source/Input Data Jawa Barat.xlsx`
- duplikasi: `DashboardEksekutif/Input Data Jawa Barat.xlsx`

### 4.2 Sheet dan dimensi

- jumlah sheet: 1
- nama sheet: `JAWA BARAT (4 Agst)`
- ukuran `UsedRange`: 987 baris x 54 kolom
- 292 sel formula ditemukan
- banyak baris kosong di `UsedRange` karena spreadsheet memakai banyak header, notes, merging, dan rapi formatting

### 4.3 Header dan struktur kolom

Header sebenarnya bukan satu baris tunggal; spreadsheet menggunakan struktur multi-level:

- Baris 1: catatan umum / pertanyaan pembuat template
- Baris 2: group indikator besar
- Baris 3: nama kolom utama
- Baris 4: subheader untuk subkategori tertentu
- Baris 5: metadata tambahan (contoh: `data 13/7/2026`)

Kolom utama yang jelas:

- `NO`, `DAERAH`
- `APL` dan sertifikasi: `Luas APL (ha)`, `Luas lahan bersertipikat (ha)`, `blm bersertipikat (ha)`, `%`
- `PBB/BPHTB`: `PBB`, `% PBB`, `BPHTB`, `% BPHTB`, `JUMLAH PBB & BPHTB`
- `NIB/NOP`: `STATUS Terkoneksi`, `NIB`, `NOP`, `KETERANGAN`
- `Hak tanggungan/kredit`: `HT`, `TOTAL SERTIPIKAT YANG DI HT TAHUN 2025`, `TOTAL SERTIPIKAT TAHUN 2000 - 2026`, `SERTIFIKAT (%)`
- `Aset Pemda`: `Aset Pemda (Bidang)`, `Sudah Sertipikat`, `Belum Sertipikat`, `% Belum Sertipikat`
- `ZNT`: `Cakupan ZNT`, `Luas belum (ha)`, `Tahun`, `Nilai Rata-Rata ZNT`, `PKS`
- `Kawasan kumuh & pertanian`: `KT`, `Luas wilayah kumuh`, `Luas Usulan LP2B`, `Ada Lokasi KT 5 Tahun Terakhir`
- `GTRA`: `Pembentukan GTRA (2018-2025)`, `GTRA Aktif (APBN 2026)`
- `TORA`: `LUAS TORA BELUM SERTIPIKAT (Ha)`
- `RTRW`: `87% KP2B/LP2B`
- `RDTR/OSS`: `RDTR OSS`, `Jumlah Perkada RDTR`, `Jumlah Terintegrasi OSS`
- `Kepemilikan lahan bersertifikat`: `NIK`, `%`, `NON NIK`, `%`, `JUMLAH PENDUDUK (UMUR >30)`, `JUMLAH SERTIPIKAT (NIK + TANPA NIK)`, `% BELUM KEPEMILIKAN TANAH`
- `MPP`: status kolom ini tidak jelas dalam header dan perlu konfirmasi sumber
- `KKN tematik / KW456`: `JUMLAH KW 456`, `LUAS KW456 (Ha)`

### 4.4 Tipe data dan nilai

- Banyak angka berbentuk teks dengan format ribuan: `125,758`, `148,615,465,215`, `1,073,275`.
- Persentase disimpan sebagai `51%`, `41.5%`, `97.68`, `0%` dan campur antara string dan numerik.
- Status dan kategori sering ditulis sebagai teks: `Terkoneksi`, `Belum Terkoneksi`, `TIDAK ADA`, `Ada`, `Belum`, `Sudah`, `SUDAH TERLAKSANA`, `BELUM TERLAKSANA`, `-`
- Beberapa kolom berisi angka desimal, beberapa menggunakan `Rp` dan m2, beberapa memakai satuan ha.
- Beberapa baris memiliki `-` atau string kosong untuk nilai tak ada; ini harus diperlakukan sebagai null atau 0 sesuai aturan bisnis.

### 4.5 Formula, nilai kosong, duplikat, inkonsistensi

Dari audit workbook:

- 292 sel menggunakan formula Excel
- ada banyak row kosong dalam `UsedRange`
- 0 duplikasi nama daerah dalam data yang terisi
- data aktual yang berisi wilayah tampak hanya berkisar 20-30 baris, bukan 987 baris
- standardisasi penamaan status masih campur: `Ada / Tidak Ada / TIDAK ADA / Sudah / Belum / SUDAH TERLAKSANA / BELUM TERLAKSANA`
- beberapa kolom bersifat multi-level dan tumpang tindih, bukan layout database yang siap pakai
- ada pertanyaan/keterangan di header yang menunjukkan template belum final (misal: “RDTR klasifikasi apa?”, “Apakah jumlah RDTR target dalam 1 daerah?”)

Kesimpulan: file Excel saat ini cocok untuk sumber input manusia/analisis, tetapi belum siap dijadikan schema relational tanpa proses ETL yang membersihkan, mengubah format, dan memetakan kolom ke tabel target.

## 5. Pemetaan indikator Excel ke modul dashboard

| Indikator | Sumber kolom Excel | Modul dashboard yang relevan | Catatan |
| --- | --- | --- | --- |
| APL dan sertipikasi | C3-C7 | Modul `apl_sertipikasi` | Luas APL, bersertipikat, belum bersertipikat, persentase |
| PBB/BPHTB | C8-C12 | Modul `pbb_bphtb` | Sumber pendapatan sektor pertanahan |
| Status koneksi NIB–NOP | C13-C16 | Modul `nib_nop` | Status terkoneksi + NIB/NOP + keterangan |
| Hak tanggungan/kredit | C17-C20 | Modul `hak_tanggungan_kredit` | HT, total sertifikat, tingkat sertifikat |
| Aset Pemda | C21-C27 | Modul `aset_pemda` | Terdiri dari aset bidang, sudah/belum sertipikat |
| ZNT | C28-C32 | Modul `znt` | Cakupan, luas belum, tahun, nilai rata-rata, PKS |
| Kawasan kumuh/pertanian | C33-C36 | Modul `kawasan_kumuh_pertanian` | KT, luas kumuh, usulan LP2B, lokasi KT |
| GTRA | C37-C38 | Modul `gtra` | Pembentukan dan GTRA aktif |
| TORA | C39 | Modul `tora` | Luas TORA belum sertipikat |
| RTRW | C40 | Modul `rtrw` | Status / skema angkutan / akuntabilitas |
| RDTR/OSS | C41-C44 | Modul `rdtr_oss` | Perkada dan integrasi OSS |
| Kepemilikan bersertipikat | C45-C51 | Modul `kepemilikan_sertipikat` | NIK, non-NIK, sertifikat, status kepemilikan |
| MPP | perlu konfirmasi header | Modul `mpp` | Kolom `STATUS` di C52 tidak jelas; perlu validasi dengan pemilik data |
| KKN tematik / KW456 | C53-C54 | Modul `kkn_kw456` | Jumlah dan luas KW456 |

Catatan: aplikasi sekarang hanya memiliki satu tabel generik `metric_snapshots` yang mencakup 6 field numerik umum. Model ini tidak cukup untuk menampung 15+ indikator berbasis workbook tanpa perlu normalisasi lanjutan.

## 6. Rancangan arsitektur target yang aman dan dapat dirawat

### 6.1 Prinsip target

- Pertahankan stack PHP dan gaya code saat ini (procedural, file-based PHP, HTML + CSS + sedikit JS) selama tetap aman.
- Gunakan MySQL/MariaDB sebagai database production, bukan SQLite.
- Pertahankan `PDO`, `prepare()`, `execute()`, `beginTransaction()`, serta validasi input sebelum insert/update.
- Hindari framework baru karena proyek belum memerlukan framework; perubahan besar akan meningkatkan risiko saat ini.

### 6.2 Layer target

1. `config.php`
   - ambil konfigurasi dari environment variable / `.env`
   - definisikan DB host/user/password/name
   - definisikan session path dan security flags

2. `db.php`
   - gunakan DSN MySQL/MariaDB
   - `setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION)`
   - `setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC)`
   - `SET NAMES utf8mb4` bila diperlukan
   - semua query pada file akan menggunakan prepared statement saja

3. `services/`
   - `ImportWorkbookService.php` untuk membaca data Excel dan mapping ke tabel target
   - `IndicatorNormalizer.php` untuk membersihkan angka, string, `%`, qty, `-` dan `NULL`
   - `RegionService.php` untuk validasi daerah dan kode BPS
   - `KkpClient.php` tetap dipakai untuk adapotor live/mock

4. `api/`
   - tetap menyediakan endpoint JSON untuk konsumsi internal atau frontend
   - semua endpoint harus memvalidasi parameter, otorisasi, dan output sesuai role

5. `dashboard.php` / `index.php`
   - tetap menampilkan dashboard seperti sekarang
   - data tidak lagi diambil dari SQLite demo, tapi dari MySQL query yang dijamin bersih

### 6.3 Skema target yang disarankan

Skema target perlu dibagi menjadi beberapa tabel, bukan satu tabel flat. Contoh:

- `users`
- `regions`
- `report_periods`
- `data_sources`
- `indicator_groups`
- `indicator_values`
- `indicator_import_logs`

Atau versi lebih sederhana namun tetap rapi:

- `regions`
- `region_indicators`
- `indicator_snapshots`
- `import_batches`
- `users`

Dengan field seperti:

- `region_code`
- `region_name`
- `province_code`
- `indicator_code`
- `year`
- `period_label`
- `value_numeric`
- `value_text`
- `unit`
- `source_type` (`pemda`, `kkp`, `manual`, `excel`)
- `is_verified`
- `import_batch_id`
- `created_at`

Ini menjamin data indikator bisa mengakomodasi APL, PBB/BPHTB, NIB-NOP, kredit, lintas topik, dan kolom tambahan di masa depan.

## 7. Risiko keamanan

- Password demo dan email demo tertulis di README dan form login: potensi salah pakai jika aplikasi dipublikasikan tanpa redaction.
- `db.php` membuat user demo setiap kali DB kosong; ini bagus untuk demo, tapi berbahaya untuk lingkungan publik jika tidak dibatasi.
- `config.php` membaca env dari server, tetapi `config/kkp.env.example` masih menampilkan placeholder token jelas dan tidak aman jika ditaruh di repo publik.
- Session file disimpan di `data/sessions`, yang belum memiliki kontrol keamanan/rotasi pada hosting shared.
- Tidak ada `CSP`, `HSTS`, atau security headers. Tidak ada penanganan sanitasi eksplisit untuk semua input selain validasi dasar numerik.
- `api/kkp.php` menerima `province` dan `date` tanpa sanitasi yang lebih dalam dari regex yang sudah ada; tetap perlu validasi business rule dan otorisasi yang jelas.
- Potensi data leakage jika `dashboard.php` menampilkan nama dan role user di browser tanpa pembatasan tambahan.
- Produk final harus menghapus akun demo dari produksi dan menggantinya dengan autentikasi yang terintegrasi ke IAM institutional.

## 8. Risiko kualitas data

- Workbook multi-header, row kosong, 292 formula, serta campuran tipe data raw.
- `-` dan string status harus di-mapping dengan definisi bisnis yang jelas.
- Kode wilayah di workbook belum terlihat terhubung ke master BPS; perlu master wilayah yang benar.
- Nilai `%` dan `Rp` perlu diproses sebelum analitik agar tidak menggabungkan string dan numerik.
- Nama daerah kemungkinan perlu normalisasi: `Kabupaten Bandung`, `Kota Bandung`, `Kabupaten Bandung Barat` dan seterusnya, dengan kode BPS yang jelas.
- Tidak ada bukti data membentuk satu sumber of truth yang final; workbook mungkin hanya snapshot atau template perencanaan.

## 9. Kompatibilitas hosting PHP gratis / shared hosting

Risiko utama untuk hosting gratis atau shared hosting:

- SQLite file di `data/dashboard.sqlite` mungkin tidak memiliki izin tulis yang cukup.
- `PDO_SQLITE` mungkin tersedia, tetapi `PDO_MYSQL` mungkin belum ada di hosting yang sama.
- `curl` untuk KKP live mungkin tidak aktif.
- `openssl` untuk token/HTTPS bisa diabaikan atau dibatasi.
- session file dan permission folder bisa gagal jika `data/sessions` tidak writable.
- `exec()` / `shell_exec()` tidak tersedia pada shared hosting, jadi ETL harus dibuat dalam PHP murni.
- free hosting biasanya tidak cocok untuk aman, multi-role, dan publish-level production jika kebutuhan data sensitif.

Rancangan target harus memvalidasi dengan provider hosting yang benar-benar menyediakan `PDO_MySQL`, `curl`, file write permission, SSL, dan domain khusus.

## 10. Bagian yang perlu konfirmasi

- Apakah sumber data final benar-benar workbook Excel ini, atau masih ada file lain yang menjadi sumber resmi?
- Apakah target database produk adalah MySQL/MariaDB di hosting pemerintah, atau hanya PostgreSQL/SQLite di lingkungan internal?
- Apakah semua indikator dalam workbook itu wajib masuk ke dashboard, atau hanya subset tertentu yang harus di-publish pada fase MVP?
- Siapa pemilik data untuk setiap indikator: Pemda, KKP, atau Pusat/Unit teknis tertentu?
- Apakah kode wilayah dan master daerah sudah tersedia dalam bentuk kode BPS resmi yang bisa digunakan untuk join data?
- Apakah dashboard perlu menampilkan semua 15+ indikator di satu halaman atau dibagi per modul/halaman?
- Apakah ada aturan validasi khusus untuk `-`, `TIDAK ADA`, `Ada`, dan saat status belum jelas?
- Apakah file workbook akan diupdate rutin atau hanya menjadi data awal import?

## 11. Kesimpulan

Proyek ini sudah memiliki fondasi UI dan logika aplikasi yang cukup baik untuk demo dan validasi kebutuhan; namun masih sangat jauh dari sistem dashboard produksi yang diharapkan untuk data pertanahan. Kebutuhan utamanya sekarang adalah normalisasi data dari Excel, pemisahan schema indikator, dan migrasi dari SQLite ke MySQL/MariaDB dengan `PDO` prepared statements, tanpa mengubah stack utama yang sudah ada.
