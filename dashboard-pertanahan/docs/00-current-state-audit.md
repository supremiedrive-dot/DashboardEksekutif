# Tahap 0 — Audit kondisi saat ini

Tanggal: 2026-09-06. Status: inspeksi statis dan verifikasi terbatas; bukan sertifikasi fungsi aplikasi. Dokumen ini menggantikan klaim kondisi saat ini pada audit/progres historis.

## Workspace, stack, dan entry point

| Aspek | Fakta inspeksi | Implikasi |
|---|---|---|
| Workspace | `C:/xampp/htdocs/dashboard-pertanahan`; root Laravel `api/` memiliki artisan/composer/bootstrap | Folder benar; dua aplikasi belum terpadu |
| UI utama | `login.php`, `dashboard.php`, `input-data.php`: PHP procedural + PDO + HTML | Bukan Blade Laravel |
| Laravel | `api/composer.json`: framework ^12.0, PHP ^8.2, Sanctum ^4.3, Tinker; `composer.lock`: v12.69.1/v4.3.3 | Versi terkunci, belum terpasang; bukan versi runtime yang berhasil dijalankan |
| Build | `api/package.json`, `vite.config.js`: Vite ^7.0.7, Tailwind ^4, axios ^1.11, laravel-vite-plugin ^2, concurrently | UI native memakai CSS/JS langsung; pipeline Vite belum digunakan olehnya |
| Laravel web | `api/routes/web.php` closure `/` → `welcome.blade.php`; health `/up` di bootstrap | Hanya welcome, bukan dashboard pertanahan; tidak ada komponen Blade bisnis |
| Auth API | `api/routes/api.php` closure login/user; `User` memakai HasApiTokens | Audit lengkap di 01; controller dasar kosong |
| PHP tersedia | `php -v`: 7.4.33; PDO/pdo_mysql/mysqli/curl/zip/SimpleXML ada | Tidak memenuhi Laravel ^8.2 |
| Alat | Get-Command hanya menemukan php/mysql; Composer, Node, npm, Python tidak ditemukan di PATH; api/vendor dan node_modules tidak ada | Tidak instal; Laravel/build/test terblokir |
| MySQL | Client MariaDB 10.4.27 tersedia; probe TCP SQL localhost ditolak 1045 untuk akun probe tanpa password | Server merespons autentikasi; versi SERVER, schema dan data aplikasi belum diverifikasi |
| Git | Semua file proyek pada status awal untracked | Diff biasa tidak cukup; baseline `00-baseline-hashes.json` |

Cara run yang ada: README root menyebut `php -S localhost:8000`; router proteksi memerlukan `router.php` secara eksplisit pada built-in server. Jangan jalankan request root pada Tahap 0: `config.php` memulai/menulis sesi dan `db.php::db()` membuka SQLite serta `initialize_database()` mengeksekusi DDL/seed. Laravel kelak dijalankan dari `api/` dengan PHP kompatibel dan `php artisan serve`; web server harus menunjuk `api/public/`. Tidak ada perintah install/setup/migrate dijalankan. `composer run setup` mencakup install, salin .env, key, migration dan npm; post-create juga membuat SQLite. Ini bukan langkah yang aman untuk scope sekarang.

## Inventaris UI lengkap berdasarkan source (belum diuji di browser)

| Halaman/komponen | Isi aktual | Pertahankan / perbaiki kelak |
|---|---|---|
| `login.php` | Card email/password, hidden CSRF, error, tombol; form POST ke dirinya, kredensial demo terisi dan petunjuk demo | Pertahankan layout; hapus demo setelah scope disetujui; sambungkan auth Laravel |
| `index.php` | require_login lalu redirect dashboard, exit; query dan markup lama setelah exit tidak terjangkau | Redirect tidak meneruskan filter; kode mati jangan dipakai sebagai bukti tampilan aktif |
| `dashboard.php` sidebar | Dashboard; Input Pemda; Sinkronisasi KKP (admin/pemda melihat link); Penjelasan Teknis; API KKP; Keluar | Sync guard hanya admin sehingga link Pemda tidak konsisten; belum ada menu/subtab kamus |
| Filter | Provinsi dan tanggal; default provinsi pertama ORDER BY name; default tanggal MAX snapshot provinsi | Dengan seed lama, inferensi default DKI Jakarta; belum verifikasi database. Tidak ada filter kab/kota/kecamatan/desa |
| Kartu | Total Objek (ring tetap 100%), Validasi Tahap 1, Validasi Tahap 2 (SVG ring), Luas Terdata ha | Pertahankan wadah; empat metrik bukan indikator kamus; nol/no-data belum dibedakan |
| Tabel | Wilayah, Total, Tahap 1, Tahap 2, Luas ha, Sumber | Label tetap “Data terverifikasi” tidak didukung status validasi; tabel kosong tidak diberi pesan no-data |
| Panel kanan | Nama/role, logo profil, status SQLite contoh, mode KKP; tombol tema | Pertahankan gaya; status sumber kelak dari metadata aktual |
| `assets/app.js` | Buka/tutup sidebar dan toggle class dark | Tidak ada fetch/axios login/API/data dummy numerik atau persistensi tema |
| CSS/assets | `style.css`, `dashboard.css`, logo.png; Google Fonts/Poppins/Material Icons; breakpoint 900/640 dan 1100/760 | Responsif dirancang di CSS, perilaku/tampilan/kontras/fokus belum teruji; tidak ada chart library atau peta |
| `input-data.php` | Region seluruh kab/kota, tanggal default tetap, total/stage1/stage2/luas; POST upsert | Bukan form indikator Pemda; belum authorization wilayah pengguna |
| `sync-kkp.php` | Admin: provinsi/tanggal, mode, tombol sync, pesan | Mock baca snapshot lama lalu upsert; live kontrak belum terkonfirmasi |
| `api/kkp.php` | JSON snapshot source kkp lewat session PHP native | Bukan route Sanctum dan bukan API BHUMI |
| `import-excel.php` | Admin: upload XLSX, tanggal, preview/import, tabel ringkasan | Tidak ditautkan sidebar dashboard; memanggil `valid_report_date()` yang tidak didefinisikan di kode yang diinventarisasi; menerima db SQLite untuk importer MySQL |
| `docs.php` | Empat section sumber, validasi, agregasi, hak akses | Pernyataan fitur adalah copy UI, bukan bukti verifikasi |
| Laravel welcome | `api/resources/views/welcome.blade.php`: halaman starter, link login/register conditional Route::has, dokumentasi/laracasts/deploy, SVG dekorasi | Tidak ada login Blade, dashboard, subtab, peta, komponen bisnis; resources JS hanya bootstrap axios |

## Jalur data dan seluruh lokasi dummy yang ditemukan

`db.php::initialize_database()` → SQLite `metric_snapshots` → query langsung `dashboard.php` → sum dan `dashboard_percent()` → kartu/tabel. Snapshot Pemda mengalahkan snapshot KKP untuk seluruh record wilayah/tanggal; kebijakan generik ini tidak sesuai owner per indikator. `services/DashboardDataService.php::getDashboard()` memakai schema MySQL, tetapi tidak di-require dashboard aktif. `DatabaseConnection::create()` juga tidak dihubungkan `db()`.

| Lokasi | Dummy/konflik teridentifikasi |
|---|---|
| `db.php::initialize_database()` | 3 akun demo, 13 region (3 provinsi + 10 kab/kota), 10 snapshot tanggal 2026-09-01; empat Jawa Barat, tiga Jawa Timur, tiga DKI. Ini hitungan array source, bukan isi database saat ini |
| `login.php`, `README.md` | Identitas/kredensial demo terbuka; nilainya sengaja tidak disalin di audit |
| `data/dashboard.sqlite` | File artefak database ada, hanya hash; isi tidak dibuka sesuai larangan SQLite |
| `services/KkpClient.php::fetchMockSnapshots()` | Memakai kembali baris source kkp, bukan data live; label source kkp sendiri tidak membuktikan keaslian |
| `database/import-demo-sqlite.php` | CLI jalur salin dummy SQLite ke MySQL, dibatasi development/testing dan --demo; tidak dijalankan |
| `database/seeders/reference_seed.sql` | Referensi tiga provinsi; kode indikator legacy; salah calon e-sertipikat dari persen luas sertipikat, layanan owner Pemda, ZNT di tata ruang, unit pelepasan hutan count |
| `database/seeders/regions_jawa_barat.sql` | 27 master kab/kota referensi, bukan 27 baris nilai Excel; Kabupaten Bogor/Pangandaran tidak ada di workbook |
| `api/database/seeders/DatabaseSeeder.php::run()` dan `UserFactory::definition()` | User tes dan password factory konstan; tanpa guard production eksplisit; tidak dieksekusi |
| `tests/DashboardDataServiceTest.php` | Fixture SQLite in-memory, angka Bandung; dilarang dijalankan sekarang |
| `tests/ExcelWorkbookImporterTest.php`, `MySqlIntegrationTest.php`, `HttpSmokeTest.php` | Fixture periode 2097–2099, user/snapshot/impor MySQL dan upload; menulis sehingga tidak dijalankan |
| `data/mappings/kkp-field-mapping.example.json`, `config/kkp.env.example` | Contoh mapping/konfigurasi; bukan respons otoritatif atau bukti koneksi live |
| `assets/*.js`, Laravel resources/public | Tidak ditemukan dataset dashboard numerik; starter visual, bootstrap axios, ikon/logo saja |

## Kesiapan MySQL dan risiko utama

Tiga kontrak users/regions berbeda: SQLite `password_hash/role`, SQL MySQL `password_hash/role_id`, Laravel `password/role`. Native menggunakan `regions.type/province_code`; SQL MySQL `region_type/province_id`; Laravel belum punya migration wilayah/indikator. Mengganti driver saja akan merusak query/auth. `input-data.php` dan `KkpClient::syncProvince()` memakai ON CONFLICT SQLite; root loader environment bukan jalur request utama. Tidak ditemukan pemakaian mysqli dalam source aplikasi yang diinventarisasi walau extension terpasang.

`database/schema.sql` memiliki roles/users/provinces/regions/report_periods/data_sources/indicator_groups/indicators/import_batches/import_batch_rows/import_audit_logs/report_snapshots/indicator_values/metric_snapshots, InnoDB, FK, unique dan indeks. Kekurangan: nilai tiga tipe dapat bersamaan, duplikasi region/period/source tidak dijaga sesuai snapshot, tidak ada desa/kelurahan eksplisit, scope user wilayah, versioning definisi, hash batch unik, revisi immutable atau audit perubahan manual. CREATE IF NOT EXISTS tidak merekonsiliasi schema yang sudah berbeda; DDL migration PHP tidak otomatis atomik di MySQL. Desain target di 04, bukan instruksi menerapkan schema lama.

Importer menyimpan 52 kolom C:BB dengan kode legacy, bukan seluruh kode kamus. Preview mencari `regions.is_active`, yang tidak ada pada schema SQLite aktif; import mensyaratkan driver MySQL. Shared formula followers memiliki teks formula kosong sehingga `is_derived` bisa salah. Cache formula dibaca, tidak dihitung ulang. Setiap reimport membuat batch baru dan menurunkan snapshot menjadi draft; checksum hanya metadata/log, tidak unique idempotency key. Jangan menjalankannya untuk memperbaiki data pada audit.

## Verifikasi aktual dan batasnya

- SHA256 sumber dan kode disimpan sebelum edit dokumen; detail akhir di progress.
- `php -l` atas 56 file PHP non-Blade/non-storage: 54 lolos, 2 gagal (`api/bootstrap/app.php`, `api/database/factories/UserFactory.php`) karena sintaks PHP 8 pada PHP 7.4. Lint bukan tes fungsi.
- `php api/artisan --version` gagal: autoload vendor tidak ada; route:list/test/runtime belum dapat dijalankan.
- Probe MySQL menerima access denied; tidak membuka `.env`, tidak memakai kredensial kerja, tidak query schema/data.
- Pembacaan ZIP/XML workbook baca-saja, termasuk relationship, strings, merges, styles, formula/cache/shared index. 58 entri kamus, 54 kolom/25 wilayah, 292 formula; bukti lengkap 02 dan JSON.
- Tidak menjalankan HTTP UI native karena akan menulis sesi dan berpotensi SQLite/seed; larangan user mengatasi kewajiban HTTP umum sebelumnya. Tidak ada klaim login, dashboard, API, integrasi KKP/BHUMI atau responsivitas berfungsi.
- Test tersedia dibaca; tidak ada test auth Laravel selain ExampleTest GET welcome dan assertTrue. Suite native bukan bukti autentikasi Laravel.

Dokumen lama `project-audit.md`, `database-design.md`, `integration-inventory.md`, `mysql-migration.md` tetap disimpan sebagai historis; klaim tidak ada Laravel atau MySQL sudah terpadu tidak cocok dengan source sekarang. Ikuti paket 00–04 dan reconciliation.

## Konfigurasi tambahan dan izin folder

`api/config/queue.php` juga memakai default DB_CONNECTION sqlite untuk batching/failed jobs; cache/session/queue default database perlu ikut kontrak MySQL. Failover cache/queue adalah mekanisme berbeda dari fallback database dan tidak membuktikan aplikasi mempunyai fallback MySQL aman. `config/app.php` default timezone UTC/locale en; format Indonesia dan timezone pelaporan perlu dipilih kemudian. `config/services.php` hanya layanan mail/Slack generic; tidak ada konfigurasi client KKP/BHUMI. `config/filesystems.php` private/local dan public disk terpisah, tetapi upload native masih data/source/. `config/logging.php` default single log/debug level; jangan log payload auth.

Get-Acl baca-saja pada data/ dan data/source/ dilakukan. Pada data/source/, grup Authenticated Users mempunyai Modify dan Users ReadAndExecute; ini ACL workstation development, bukan bukti izin produksi aman atau hak akun web server yang terverifikasi. Tidak ada ACL diubah, sesi dibaca, maupun `.env` ditampilkan. Keterjangkauan lewat HTTP belum diuji. Pemeriksaan Get-NetTCPConnection tidak menghasilkan bukti listener yang dapat digunakan; respons probe MySQL 1045 adalah bukti layanan menjawab, bukan versi/schema server.
