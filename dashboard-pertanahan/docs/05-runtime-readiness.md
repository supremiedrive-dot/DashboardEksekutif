# Audit kesiapan runtime dan dependency — Fase 1B.1

## 1) Requirement dari manifest dan lockfile

### PHP / Laravel
- `api/composer.json`: `php` = `^8.2`
- `api/composer.json`: `laravel/framework` = `^12.0`
- `api/composer.json`: `laravel/sanctum` = `^4.3`
- `api/composer.lock`: `laravel/framework` = `v12.69.1`
- `api/composer.lock`: `laravel/sanctum` = `v4.3.3`
- `api/composer.json`: `phpunit/phpunit` = `^11.5.50`

### Node / frontend
- `api/package.json` menyiapkan `vite` / `laravel-vite-plugin` / `tailwindcss` / `axios` / `concurrently`.
- Versi pada manifest:
  - `@tailwindcss/vite`: `^4.0.0`
  - `axios`: `^1.11.0`
  - `concurrently`: `^9.0.1`
  - `laravel-vite-plugin`: `^2.0.0`
  - `tailwindcss`: `^4.0.0`
  - `vite`: `^7.0.7`
- Tidak ada `api/package-lock.json`, `api/yarn.lock`, atau `api/pnpm-lock.yaml` pada workspace yang dibaca; lockfile frontend belum tersedia di `api/`.

## 2) Runtime aktual dan path executable

### PHP CLI
- Path aktif: `C:\xampp\php\php.exe`
- Versi: `PHP 7.4.33 (cli)`
- `php --ini`: `Loaded Configuration File: C:\xampp\php\php.ini`
- `where.exe php`: hanya satu hasil, `C:\xampp\php\php.exe`

### Composer / Node / npm
- `composer --version`: tidak tersedia di PATH
- `node --version`: tidak tersedia di PATH
- `npm --version`: tidak tersedia di PATH
- `where.exe composer`: tidak ditemukan
- `where.exe node`: tidak ditemukan
- `where.exe npm`: tidak ditemukan

### MySQL/MariaDB client
- Path: `C:\xampp\mysql\bin\mysql.exe`
- Versi: `Ver 15.1 Distrib 10.4.27-MariaDB, for Win64 (AMD64)`
- `where.exe mysql`: satu hasil, `C:\xampp\mysql\bin\mysql.exe`

### File/folder yang diperiksa secara read-only
- `api/vendor/autoload.php`: tidak ada
- `api/node_modules`: tidak ada
- `api/.env.example`: ada
- `api/.env`: tidak ada

## 3) Ringkasan status PASS / FAIL / BLOCKED

| Item | Status | Detail |
|---|---|---|
| PHP 8.2+ sesuai manifest | FAIL | PHP aktif adalah 7.4.33, tidak memenuhi `^8.2` |
| Laravel 12 lockfile | PASS | `api/composer.lock` berisi `laravel/framework` `v12.69.1` |
| Sanctum 4.3.x lockfile | PASS | `api/composer.lock` berisi `laravel/sanctum` `v4.3.3` |
| Composer tersedia | BLOCKED | `composer` tidak ada di PATH |
| Node/npm tersedia | BLOCKED | `node` dan `npm` tidak ada di PATH |
| `api/vendor/autoload.php` | FAIL | Vendor belum dibuat |
| `api/node_modules` | FAIL | Dependency frontend belum diinstall |
| MySQL client tersedia | PASS | `mysql.exe` aktif dari XAMPP |
| `api/.env.example` | PASS | File ada |
| `api/.env` | FAIL | File belum dibuat/tersedia |
| Laravel app boot belum diuji | BLOCKED | Dependency tidak ada dan PHP versi salah |

## 4) Extension PHP yang tersedia dan yang hilang

### Extension wajib menurut Laravel/dependency
`php -m` menunjukkan extension berikut tersedia:
- `bcmath` — tersedia
- `ctype` — tersedia
- `curl` — tersedia
- `fileinfo` — tersedia
- `json` — tersedia
- `mbstring` — tersedia
- `openssl` — tersedia
- `PDO` — tersedia
- `pdo_mysql` — tersedia
- `tokenizer` — tersedia
- `xml` — tersedia
- `zip` — tersedia

Catatan penting:
- `pdo` terlihat sebagai `PDO` dalam `php -m`, dan itu memenuhi kebutuhan Laravel/DB.
- `pdo_mysql` juga tersedia, sesuai target MySQL/MariaDB.
- Extension yang ada mendukung Laravel 12 di PHP 8.2+ ketika runtime yang benar dipasang.
- Tidak ada extension wajib yang terlihat hilang pada PHP 7.4.33 saat ini.

### Extension yang disarankan/proyek
PHP 7.4.33 juga menampilkan extension lain yang relevan untuk aplikasi web umum, seperti `dom`, `session`, `mysqli`, `SimpleXML`, `xmlreader`, `xmlwriter`, `exif`, dan `gd`. Tidak ada tanda missing untuk kebutuhan dasar proyek pada runtime yang aktif.

## 5) Dependency tersedia/hilang

### PHP dependency / runtime
- PHP CLI aktif: tersedia, tetapi versi tidak sesuai `^8.2`
- Composer: tidak tersedia
- Node/npm: tidak tersedia
- Laravel vendor: tidak tersedia
- Frontend `node_modules`: tidak tersedia

### MySQL / db client
- MySQL/MariaDB client: tersedia
- Database server access, schema, user/password validation: belum diuji (read-only, tidak ada login database atau query yang dijalankan)

## 6) Konflik PATH dan kondisi PHP aktif

- `where.exe php` hanya mengembalikan satu path: `C:\xampp\php\php.exe`.
- Tidak ada lebih dari satu `php.exe` dalam PATH yang terdeteksi.
- Ditemukan kondisi blocker nyata: XAMPP bundle yang aktif adalah PHP 7.4.33, sementara `api/composer.json` mensyaratkan PHP `^8.2` dan `composer.lock` mengunci Laravel 12.
- Composer, Node, dan npm tidak berada di PATH; karena itu, tidak ada instalasi dependency yang bisa dilakukan dari shell saat ini.
- Ini bukan kondisi `PATH conflict` multi-PHP yang biasa, melainkan satu runtime XAMPP aktif yang usang untuk Laravel 12.

## 7) Kebutuhan Python

- Status: `Tidak` untuk fase ini.
- Bukti yang tersedia dari manifest yang dibaca:
  - `api/composer.json` adalah PHP-only; tidak ada dependency Python.
  - `api/package.json` adalah Node/Vite frontend; tidak ada dependency Python.
  - Tidak ada `requirements.txt`, `pyproject.toml`, atau script Python yang dibaca dari file yang diizinkan pada audit ini.
- Kesimpulan: tidak ada bukti bahwa Python diperlukan oleh aplikasi Laravel atau importer pada skenario saat ini. Jika nanti importer atau alat tertentu membutuhkan Python, itu harus dibuktikan dengan file/fungsi spesifik di fase berikutnya, bukan diasumsikan karena audit lama.

## 8) Rencana instalasi manual yang aman (belum dijalankan)

1. Siapkan runtime baru yang kompatibel Laravel 12
   - Instal PHP 8.2.x (versi yang sesuai dengan Laravel 12) di lokasi terpisah seperti `C:\tools\php82` atau `C:\php82`.
   - Jangan mengganti atau menghapus PHP 7.4.33 yang ada di `C:\xampp\php`.
   - Pastikan `C:\tools\php82` atau path instalasi baru ditambahkan ke `PATH` secara terpisah, bukan mengganti PATH XAMPP yang sudah ada.
   - Verifikasi dengan `where.exe php` dan `php -v` sebelum melanjutkan.

2. Instal Composer dan Node/npm pada PATH yang terpisah
   - Instal Composer untuk Windows dari installer resmi atau PHAR yang masuk ke direktori non-XAMPP.
   - Instal Node.js LTS dan npm dari situs resmi Node.js, bukan mengubah runtime XAMPP.
   - Pastikan yang aktif di PATH adalah versi baru, bukan `C:\xampp\php` saja.
   - Verifikasi `composer --version` dan `npm --version`.

3. Instal dependency Laravel dan frontend di `api/`
   - `cd C:\xampp\htdocs\dashboard-pertanahan\api`
   - `composer install --no-interaction --prefer-dist`
   - `npm install --no-fund --no-audit`
   - Jika `.env` belum ada, salin dari `.env.example` dengan cara manual dan isi konfigurasi MySQL yang aman; jangan membuka/menampilkan nilai rahasia saat dokumentasi.
   - Pastikan tidak ada fallback SQLite yang diaktifkan secara diam-diam.

4. Validasi sebelum implementasi
   - Jalankan `php artisan --version`
   - Jalankan `php artisan config:clear`
   - Jalankan `php artisan test` hanya di lingkungan MySQL test yang terpisah dan bukan database kerja (sesuai scope berikutnya).
   - Pastikan `api/vendor/autoload.php` dan `api/node_modules` ada di folder target.

## 9) Perintah verifikasi setelah instalasi manual

Perintah yang disarankan setelah instalasi runtime baru:

```powershell
where.exe php
where.exe composer
where.exe node
where.exe npm
php -v
composer --version
node --version
npm --version
php --ini
php -m | findstr /I "openssl pdo pdo_mysql mbstring tokenizer xml ctype json bcmath fileinfo curl zip"
cd C:\xampp\htdocs\dashboard-pertanahan\api
composer validate --no-check-publish
composer check-platform-reqs
php artisan --version
npm install --no-fund --no-audit
npm run build
```

Catatan: perintah di atas tidak dijalankan pada audit ini; mereka adalah verifikasi yang diperlukan setelah instalasi manual selesai.

## 10) Kriteria selesai Fase 1B

### Kriteria Fase 1B.1 (audit selesai)
- Manifest dan lockfile Laravel 12/Sanctum 4.3.x tercatat dan didokumentasikan.
- Runtime XAMPP aktif, path, versi, dan blocker tercatat.
- Extension PHP yang wajib dan dependency yang diperlukan dinilai secara read-only.
- Pekerjaan dipisahkan dari instalasi/pengubahan aplikasi.
- Dokumentasi final tersimpan di `docs/05-runtime-readiness.md` dan `docs/progress.md`/`docs/HANDOFF.md` diperbarui.

### Kriteria Fase 1B penuh (setelah instalasi manual dan verifikasi berikutnya)
- PHP 8.2+ aktif di PATH.
- Composer dan Node/npm tersedia di PATH.
- `api/vendor/autoload.php` dan `api/node_modules` ada.
- `api/.env` dibuat dari `.env.example` dengan konfigurasi MySQL yang aman.
- `composer install` dan `npm install` berhasil.
- `php artisan --version` dan `npm run build` berhasil.
- MySQL client tersedia dan koneksi DB test eksplisit, tanpa fallback SQLite atau dummy database.

## 11) Keputusan audit saat ini

- Fase 1B.1 sudah selesai sebagai audit kesiapan runtime dan dependency, tetapi tidak menandakan Laravel sudah berjalan.
- `PHP 7.4.33` pada XAMPP saat ini adalah blocker utama untuk Laravel 12 yang ditargetkan.
- Composer, Node, dan npm belum tersedia di PATH sehingga instalasi dependency belum bisa dilakukan.
- Langkah berikutnya yang aman adalah instalasi runtime baru dan dependency di lokasi terpisah, lalu verifikasi sebelum pekerjaan implementasi berikutnya dimulai.

## 12) Fase 1B.2 — verifikasi readiness Laravel dan MySQL/MariaDB

### Ringkasan eksekusi
- Working directory: `C:\xampp\htdocs\dashboard-pertanahan`
- Laravel app root: `C:\xampp\htdocs\dashboard-pertanahan\api`
- PHP aktif: `C:\tools\php84\php.exe` lewat `php -v` dan `php --ini`
- `composer validate --no-check-publish`: `./composer.json is valid`
- `composer check-platform-reqs`: semua platform requirement Laravel 12 pada PHP 8.4.25 sukses
- `php artisan --version`: `Laravel Framework 12.69.1`
- `php artisan about`: berhasil menampilkan environment lokal, Laravel 12.69.1, PHP 8.4.25, dan database driver `mysql`
- `npm run build` pada `api/`: berhasil (`vite build` selesai; output di `public/build/...`), dengan `package-lock.json` dan `node_modules` ada
- `.env`: ada dan di-normalisasi untuk local MySQL-safe mode tanpa SQLite; `DB_DATABASE` dan `DB_USERNAME` masih kosong, sehingga database runtime belum bisa dibuka

### Tabel hasil Fase 1B.2
| Pemeriksaan | Hasil | Bukti ringkas |
|---|---|---|
| PHP 8.4 | PASS | `where.exe php` mengarah ke `C:\tools\php84\php.exe`; `php -v` = `PHP 8.4.25` |
| Extension PHP | PASS | `bcmath`, `ctype`, `curl`, `fileinfo`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`, `tokenizer`, `xml`, `zip` semua `PASS` |
| Composer dependency | PASS | `composer validate --no-check-publish` valid; `composer check-platform-reqs` semua sukses |
| Laravel bootstrap | PASS | `php artisan --version` dan `php artisan about` berhasil; Laravel 12.69.1 aktif |
| Node/npm | PASS | `node -v` = `v24.20.0`; `npm -v` = `11.19.0`; build frontend berhasil |
| Frontend build | PASS | `vite build` selesai dan output build dibuat |
| .env | PASS | `.env` ada; konfigurasi local diatur ke `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://127.0.0.1:8000`, `DB_CONNECTION=mysql`, `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync` |
| MySQL connection | PASS | `php artisan tinker --execute="echo 'CONNECTED | Database: '.DB::connection()->getDatabaseName().' | Version: '.DB::selectOne('SELECT VERSION() AS version')->version;"` berhasil; driver `mysql` dan database `dashboard_pertanahan_dev` terbaca |

### Status koneksi Laravel–MariaDB
- Koneksi Laravel–MariaDB: `PASS`
- Driver: `mysql`
- Database: `dashboard_pertanahan_dev`
- Versi server: `10.4.27-MariaDB`
- Tidak ada fallback SQLite.
- `db:show` gagal hanya saat membaca `performance_schema.session_status` karena akun `dashboard_app` tidak memiliki hak akses statistik server; ini adalah pembatasan privilege yang disengaja dan bukan kegagalan koneksi.
- Tidak menambahkan privilege ke `performance_schema`.

### File yang berubah pada Fase 1B.2
- `api/.env` dibuat/di-normalisasi untuk local MySQL-safe config tanpa SQLite
- `docs/05-runtime-readiness.md` diperbarui
- `docs/progress.md` diperbarui
- `docs/HANDOFF.md` diperbarui

### Blocker yang tersisa
- Tidak ada blocker teknis yang menghambat koneksi Laravel–MariaDB yang telah terbukti berhasil.
- `db:show` gagal hanya pada query metadata `performance_schema` karena keterbatasan privilege akun, bukan karena koneksi, driver, atau SQLite fallback.
- Tidak ada migrasi, seed, import, atau perubahan kode aplikasi yang dijalankan.

### Kesimpulan Fase 1B
- Fase 1B selesai dengan status lulus untuk runtime, Laravel bootstrap, dependency, frontend build, dan koneksi read-only MariaDB.
- Fase berikutnya adalah **Fase 2A**: fondasi Laravel dan hardening autentikasi, dengan fokus pada auth/session/CSRF dan kontrak login yang aman.
