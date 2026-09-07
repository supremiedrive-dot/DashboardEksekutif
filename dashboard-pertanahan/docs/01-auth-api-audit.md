# Tahap 0 — Audit autentikasi dan API

2026-09-06. Fakta di bawah berasal dari source, bukan keberhasilan request HTTP. Runtime Laravel terblokir PHP 7.4.33 versus ^8.2 dan autoload vendor tidak ada. Tidak ada controller login terpisah: implementasi berada dalam closure route. `api/app/Http/Controllers/Controller.php` hanya abstract class kosong; `api/app/Providers/AppServiceProvider.php` register/boot kosong; tidak ada middleware aplikasi khusus/policy ditemukan dalam inventaris `api/app/`.

## Route dan alur aktual

| Endpoint (relatif server masing-masing) | Handler/bukti | Auth dan hasil yang tertulis | Status |
|---|---|---|---|
| Laravel POST `/api/login` | `api/routes/api.php` closure Route::post('/login'); prefix API dari `api/bootstrap/app.php::withRouting(api:...)` | validate email required/email dan password required → Auth::attempt → 401 pesan umum atau createToken('api-token')->plainTextToken dan user/token | Deklarasi terbaca; prefix efektif dan middleware framework belum diverifikasi route:list |
| Laravel GET `/api/user` | `api/routes/api.php` closure GET /user | auth:sanctum → Request::user() | Profil tersedia sebagai /user, bukan /profile; runtime belum diuji |
| Laravel logout/refresh/profile literal/register/reset | Tidak ada deklarasi di `api/routes/api.php`, `web.php` | Tidak ada implementasi logout/revoke/refresh atau route profil literal | Absensi source aplikasi; route vendor tidak diperiksa karena belum terpasang |
| Laravel GET `/` | `api/routes/web.php` closure view('welcome') | Starter, bukan form login | ExampleTest hanya mengharapkan 200 di sini; belum dijalankan |
| Laravel `/up` | `api/bootstrap/app.php` health | Endpoint health konfigurasi | Runtime belum diuji |
| Native GET/POST `/login.php` | `login.php` blok POST | verify_csrf → PDO SELECT users → password_verify(password_hash) → session_regenerate_id(true) → session user → dashboard redirect | Alur source terpisah dari API |
| Native `/logout.php` | file langsung; tidak membatasi method | kosongkan session, session_destroy, redirect login | Tidak ada CSRF/POST guard atau penghapusan cookie eksplisit |
| Native `/dashboard.php`, `/index.php`, `/docs.php` | `config.php::require_login()` | session user diperlukan | Tidak memakai Sanctum |
| Native `/input-data.php` | `require_role(['admin','pemda'])` | CSRF, validasi angka, tanggal regex, upsert SQLite | Tidak ada batas wilayah pengguna |
| Native `/sync-kkp.php`, `/import-excel.php` | `require_role(['admin'])` | CSRF dan penulisan data | Tidak dijalankan |
| Native GET `/api/kkp.php` | file native require ../db.php dan require_login | Snapshot lokal source kkp, JSON; parameter regex | Bukan route Laravel dan bukan integrasi live terbukti |

Alur UI native: browser → POST login.php → `db.php::db()` → SQLite users → `$_SESSION['user']` → dashboard.php. `assets/app.js` tidak mengirim login/fetch/token; form tidak memiliki action API. Alur Laravel yang direncanakan kode: API login → default web guard/provider User → personal access token → Bearer pada /api/user. Tidak ada jembatan identitas, session, token atau skema di antara keduanya.

## Guard, middleware dan model

| Topik | Fakta inspeksi | Risiko/inferensi dan tindakan yang disarankan setelah review |
|---|---|---|
| Guard/provider | `api/config/auth.php` default env AUTH_GUARD dengan default web/session; users Eloquent App\Models\User | Password broker throttle 60 hanya reset password, bukan limit percobaan login |
| Token | `User` use HasApiTokens; personal_access_tokens migration menyimpan token unik 64, abilities, expires_at nullable | Route tidak menentukan abilities/expiry; config sanctum expiration null; perlu kebijakan masa berlaku dan pencabutan |
| Password/serialization | User casts password hashed; hidden password/remember_token; fillable name/email/password/role | Hashing dan penyembunyian tertulis; mass-assignment role berisiko jika kelak request all dipakai (belum ada exploit route demikian) |
| Authorization | Role string default user di migration 2026_09_03_133822; tidak ada policy/middleware role/wilayah di app | /user hanya authentication; sistem belum membatasi input per wilayah/indikator |
| Stateful API/session | bootstrap withMiddleware kosong; tidak ada statefulApi() atau penambahan StartSession eksplisit | Auth::attempt memakai session guard dalam API; kompatibilitas lifecycle session harus diuji, jangan mengklaim pasti gagal/sukses tanpa runtime |
| CSRF | sanctum config menunjuk ValidateCsrfToken/EncryptCookies/AuthenticateSession; bootstrap tidak mengaktifkan stateful API eksplisit | Keberadaan config bukan bukti CSRF API aktif. Pilih session untuk first-party atau Bearer untuk client API, lalu tes alur sesuai pilihan |
| Rate limit | Tidak ada throttle/login limiter eksplisit di route/bootstrap/provider aplikasi | Perlindungan brute force belum terbukti; middleware efektif vendor belum diverifikasi |
| Session/cookie Laravel | session config default database, 120 menit, http_only true, same_site lax, secure dari env; encrypt false | Nilai runtime tidak diketahui; `.env` tidak dibaca; DB session membutuhkan migration dan runtime MySQL yang benar |
| Validation | email required/email, password hanya required | Tambahkan type string/max length; uji array/oversized input, malformed JSON, Accept JSON dan status 422 |
| Errors | Login salah memakai pesan generik 401; withExceptions kosong | Penanganan DB error/redaksi runtime belum teruji; `.env.example` debug true bukan bukti debug production |
| Token handling | Token dikembalikan respons; tidak ada penyimpanan frontend atau logging token eksplisit dalam source route | Hindari mencatat token; pilih penyimpanan aman dan revoke saat logout; jangan menambah refresh semu |
| Auth mock? | Auth::attempt dan createToken nyata tertulis; tidak ada branch login selalu sukses | Bukan sekadar respons mock di source, tetapi dependensi/DB belum siap. Dummy factory tidak membuktikan API bekerja |

## Temuan native yang memengaruhi integrasi

| Prioritas | Bukti | Temuan |
|---|---|---|
| Tinggi | `login.php` markup, `db.php::initialize_database()`, README | Kredensial demo tertanam/ditampilkan dan auto-seed tanpa guard production; nilai tidak disalin ke audit |
| Tinggi | `db.php::db()` | Request membuka SQLite dan menjalankan DDL/seed; melanggar target MySQL; audit tidak memanggilnya |
| Tinggi | `input-data.php` query cities dan validRegion | Setiap user Pemda boleh memilih seluruh wilayah terdaftar; role bukan scope wilayah |
| Tinggi | root vs `api/database/migrations/*users*`, `database/schema.sql` | Native password_hash/role, SQL password_hash/role_id, Laravel password/role; tidak kompatibel langsung |
| Sedang | `config.php` session_start/SESSION_PATH | Tidak ada session_set_cookie_params, strict mode, TTL eksplisit; cookie runtime/izin ACL belum diverifikasi. require_login hanya memeriksa data sesi, tidak recheck akun aktif |
| Sedang | `config.php::verify_csrf()` | hash_equals baik untuk string normal; payload array belum dijaga, berpotensi warning/type error; token session native bukan Laravel |
| Sedang | `login.php` blok POST | Prepared email query, password_verify, regenerasi ID dan escaping h positif; tidak ada rate limit server, email filter/string type explicit |
| Sedang | `logout.php` | Dapat dipanggil GET, tidak CSRF, tidak menghapus cookie eksplisit |
| Sedang | `input-data.php`, `sync-kkp.php`, `api/kkp.php` | Tanggal regex saja menerima tanggal kalender mustahil; input array belum dijaga |
| Sedang | `api/kkp.php::require_login()` | Redirect relatif login.php dari /api/ bisa menuju /api/login.php; respons unauth bukan JSON 401 |
| Sedang | `KkpClient::normalizePayload()` | Tidak memeriksa region sesuai province request, tanggal kalender, atau luas negatif; fallback tanggal dan skip non-array perlu kontrak. Live belum diuji |
| Sedang | `sync-kkp.php` catch | Pesan exception ditampilkan dengan escaping, tetapi escaping tidak menyembunyikan detail koneksi/SQL |
| Sedang | `.htaccess`, `router.php` | Proteksi file/direktori ada, tetapi belum memblok seluruh struktur Laravel di bawah /api bila workspace root disajikan. Laravel harus memakai public/; HTTP perlindungan belum diuji |

## Bukti test dan gap

| Test/command | Yang diperiksa | Status aktual |
|---|---|---|
| `php -l` 56 file | Sintaks lokal; route/controller/model juga dilint | 54 lolos, dua sintaks PHP 8 gagal pada PHP 7.4; JSON bukti di 00-lint-results.json |
| `php api/artisan --version` | Boot/autoload | Gagal vendor/autoload.php tidak ada; bukan keberhasilan route/list |
| `api/tests/Feature/ExampleTest::test_the_application_returns_a_successful_response()` | GET / → 200 | Hanya source dibaca; bukan auth test |
| `api/tests/Unit/ExampleTest::test_that_true_is_true()` | assertTrue | Bukan bukti fungsional |
| `api/tests/TestCase.php`, `api/phpunit.xml` | Base framework, DB SQLite :memory:, session array | Tidak dijalankan; SQLite dilarang dan vendor/PHP tidak siap |
| `tests/HttpSmokeTest.php` | Native login/role/CSRF/HTTP + create users/snapshot/import | Tidak dijalankan karena mutasi; tidak menguji Laravel |
| `tests/MySqlIntegrationTest.php`, `ExcelWorkbookImporterTest.php` | Query/insert/update/import target test | Tidak dijalankan, menulis DB/fixture |
| `tests/DashboardDataServiceTest.php`, `EnvironmentTest.php` | SQLite fixture; file sementara lalu unlink | Tidak dijalankan: SQLite dan penghapusan file di luar batas Tahap 0 |

Matriks test kelak: login valid/salah/array/invalid JSON; 422/401/429; /user tanpa token, token invalid/expired/revoked; logout invalidate; CSRF untuk session; rotasi session; hak role dan lintas wilayah/indikator; user nonaktif; sanitasi respons; DB unavailable; cookie HTTPS. Jalankan pada PHP kompatibel dan MySQL test terisolasi sesudah izin fase implementasi. Tidak ada pengujian auth runtime yang dinyatakan lulus sekarang.

## Keputusan target Fase 1A — identitas tunggal (2026-09-06)

Bagian audit di atas tetap fakta Tahap 0 dan tidak diubah menjadi klaim implementasi. Keputusan ini menggantikan pilihan arsitektur yang masih terbuka pada rekomendasi audit; belum ada patch route/controller/config atau pengujian baru.

| Keputusan | Target yang dikunci | Fase verifikasi mendatang |
|---|---|---|
| Satu aplikasi/identity | Laravel 12 di api/ kanonis; Laravel/Sanctum satu sumber identitas pada MySQL/MariaDB development/production, tanpa SQLite fallback | 1B cek prasyarat; 2A fondasi/auth; 2B schema/master |
| UI same-origin | Prioritaskan web session/cookie stateful + CSRF aman. Cookie Secure pada HTTPS, HttpOnly, SameSite sesuai topology; regenerasi ID saat login, logout invalidate session + regenerate CSRF | 2A feature tests di runtime kompatibel; browser shell pada 4A |
| API tim | Login Sanctum yang ada tetap sumber audit lalu hardening logout/revoke, rate limit, validasi tipe/panjang, error aman, cookie/session dan role/scope wilayah | 2A menguji route/middleware efektif; kontrak client token dipertahankan hanya jika perlu, bukan identity kedua |
| Bearer bila diperlukan | Token client eksternal tetap terkait user Laravel yang sama, dengan abilities/expiry/revoke teruji. UI same-origin tidak memerlukan token di localStorage | Kontrak/expiry rinci ditunda ke 2A; tidak mengarang refresh endpoint |
| Authorization | Role + wilayah + indikator + akun aktif diperiksa server-side pada read/write; CSRF tidak menggantikan policy scope | 2A deny-by-default; integrasi scope persisten setelah 2B dan input 3A |
| Transisi native | UI native adalah referensi visual/sumber migrasi. Jangan hapus halaman native sebelum pengganti Laravel feature parity dan terverifikasi; pensiunkan auth native setelah migrasi UI | 4A–4C parity per halaman; penutupan auth lama pada 5 setelah bukti lengkap |
| Gagal backend | MySQL gagal menghasilkan error aman, tidak pindah SQLite/auto-seed atau menjalankan login native sebagai fallback | 2A/2B pengujian kegagalan; bukan diuji sekarang |

Fase 2A tidak boleh mengklaim scope wilayah persisten selesai sebelum schema 2B dan integration test tersedia. Test session di lingkungan terisolasi dapat memakai driver array sementara dengan user MySQL test, bukan SQLite; sesi database final dan policy scope diuji kembali setelah 2B. Infrastruktur user/session/token standar yang dibutuhkan 2A hanya pada DB test sesuai scope fase berikut, bukan migration domain/data operasional.

Feature parity berarti alur pengganti yang relevan (login/logout, akses role/wilayah, error/no-data, navigasi) dibuktikan dengan test dan review, bukan mempertahankan cacat keamanan/dummy lama. Tidak ada auth kedua yang dipelihara sebagai target akhir. Nilai as_of_date yang valid tanpa data tidak memicu fallback sumber/database/periode maupun bypass auth.

Status Fase 1A: keputusan terdokumentasi saja; PHP 7.4.33, dependency belum tersedia dan akses/schema MySQL belum terverifikasi tetap blocker Tahap 0. Tidak ada HTTP, migration, instalasi atau test aplikasi dijalankan pada fase ini.

## Fase 2A — implementasi 2026-09-06

Bagian historis di atas tetap bukti Tahap 0/keputusan 1A. Instruksi pengguna Fase 2A mengizinkan perubahan auth/test dan mengungguli scope dokumentasi lama di AGENTS/PROJECT_CONTEXT. Tidak membaca ulang workbook atau mengaudit dashboard.

### Sebelum dan sesudah
- Sebelum: `routes/api.php` closure login memakai `Auth::attempt`, validasi minimum, kontrak 200 `{message,user,token}`, kegagalan 401 generik; GET /api/user sudah auth:sanctum. Logout/limiter tidak ada. Native menggunakan autentikasi terpisah, tidak diintegrasikan.
- Sesudah: `App\Http\Requests\LoginRequest::rules` memvalidasi required/string/email/max 255 dan password required/string/max 4096. `AuthController::login` menggunakan guard web `once`, provider hash Laravel, timebox dan rehash bawaan tanpa membuat sesi web. Implementasi vendor SessionGuard::once/validate diperiksa lokal.
- Respons login mempertahankan message/user/token dan pesan lama. `AuthController::profile` allowlist id/name/email/email_verified_at/role/created_at/updated_at; password/remember_token/relasi token tidak diserialisasi. Respons sukses no-store. Tidak menambahkan pencatatan password/token.
- Token baru bernama dashboard-api, expires_at 24 jam, abilities ['*'] mempertahankan kontrak lama; abilities bukan pemberian role/otorisasi wilayah. Token lama tidak diubah, bisa tetap tanpa expiry; kebijakan pencabutan token lama perlu keputusan operasional terpisah.
- `AuthController::logout` menghapus currentAccessToken saja. Cabang defensif sesi melakukan logout/invalidate/regenerateToken, tetapi statefulApi belum diaktifkan dan cabang ini belum diuji. Tidak ada logout semua perangkat.
- `AppServiceProvider::boot`: Laravel RateLimiter login 5 request/menit per SHA256(lowercase(trim(email)) + IP), termasuk request invalid/sukses. Payload email array ditangani aman; 429 dan Retry-After bawaan. Cache efektif file lokal, testing array; deployment multi-instance kelak membutuhkan cache bersama.
- `bootstrap/app.php`: API exception dirender JSON meskipun Accept tidak dikirim; guest tidak diarahkan ke route login yang tidak tersedia.
- `config/database.php`: default source sqlite ditemukan berbeda dari driver efektif mysql Fase 1B; diubah menjadi mysql. Definisi koneksi bawaan lain tidak dipakai sebagai fallback. `.env` tidak diubah.

### Endpoint akhir
| Method/path | Handler | Middleware/kontrak |
|---|---|---|
| POST /api/login | AuthController::login | throttle:login; 200 login, 401 kredensial, 422 validasi, 429 limit |
| GET /api/user | AuthController::user | auth:sanctum; 200 user langsung, 401 unauthenticated |
| POST /api/logout | AuthController::logout | auth:sanctum; 200 message, 401 unauthenticated |

### Verifikasi dan batas bukti
| Pemeriksaan | Status | Bukti |
|---|---|---|
| php -l semua 8 file PHP berubah/baru | PASS | Exit 0 |
| php artisan route:list --path=api --json | PASS | 3 endpoint; throttle:login dan auth:sanctum efektif |
| php artisan about --only=environment,drivers | PASS | Exit 0; PHP 8.4.25, Laravel 12.69.1, mysql, file session/cache; debug lokal enabled |
| php artisan config:clear | PASS | Exit 0 |
| composer check-platform-reqs | PASS | Exit 0, semua requirement sukses |
| AuthValidationTest (PHPUnit langsung) | PASS | 3 test, 35 assertion; validasi tipe/panjang, JSON tanpa Accept, guest profile/logout, limit + variasi case/IP. beforeExecuting melarang query DB |
| AuthTokenTest | SKIPPED | 4 test tersedia tetapi tidak dijalankan: kredensial salah/unknown; login-profile-logout-revoked + perangkat lain; invalid/expired; brute force kredensial salah. Memeriksa sanitasi dan expiry |
| Session/cookie, CSRF, rotasi sesi browser | SKIPPED | Belum diimplementasikan sebagai alur UI stateful; tidak mengklaim target session Fase 2A selesai |
| Frontend build | SKIPPED | Tidak ada perubahan frontend |

### Pengaman database test
`api/.env.testing` tidak tersedia saat inspeksi. Tidak ada bukti database testing terpisah siap; tidak mencoba koneksi/migration/seed/test database. `phpunit.xml` sebelumnya SQLite :memory:, kini memaksa testing/mysql/dashboard_pertanahan_test dan DB_URL kosong. Nama konfigurasi bukan bukti database telah tersedia.

`AuthTokenTest::setUp` mensyaratkan AUTH_TEST_DB_READY=1 serta .env.testing, menolak config cache/non-testing/non-mysql/nama DB lain/URL/read-write override, memeriksa SELECT DATABASE() aktual dan keberadaan users/personal_access_tokens sebelum transaksi. Tidak memakai RefreshDatabase/migrate:fresh/seed; fixture dibungkus transaksi rollback. Kegagalan prasyarat harus diselesaikan pada lingkungan test, bukan dev.

Satu langkah manual provisioning: administrator menyediakan database `dashboard_pertanahan_test` dan akun khusus dengan privilege hanya pada database tersebut, lalu menyimpan koneksi secara privat di `api/.env.testing` (tanpa menyalin rahasia ke docs). Setelah provisioning, verifikasi koneksi/nama aktual dan siapkan hanya migration auth yang telah dibaca pada DB test dalam scope yang disetujui; baru set AUTH_TEST_DB_READY=1 untuk menjalankan AuthTokenTest. Jangan menjalankan migration di dashboard_pertanahan_dev atau menambah privilege performance_schema.

### Otorisasi dan status
Fakta source: User memiliki role string dan migration penambah role default user; tidak ada relasi/master wilayah, policy atau endpoint domain yang membutuhkan role tertentu. Fase 1B mencatat DB development masih tanpa tabel; tidak diperiksa ulang atau dimigrasikan di fase ini. Tidak membuat role/schema palsu atau gate arbitrer untuk profil pribadi. Role tidak memberi akses wilayah. Policy role + wilayah + indikator dan akun aktif harus dibangun bersama schema 2B, deny-by-default sebelum endpoint domain dibuka.

Status: **Fase 2A selesai penuh**. Hardening Bearer terbukti: 7 test PASS (107 assertions) pada database MySQL testing yang terisolasi. AuthTokenTest memverifikasi token issuance, profile accessible, revoke active, invalid/expired rejected, credentials throttled. AuthValidationTest memverifikasi JSON validation tanpa Accept, auth requirement, rate limit bypass prevention. Database testing (dashboard_pertanahan_test) dengan user dashboard_test terisolasi dari development. Integrasi token dengan Sanctum, limiter 5/menit per email+IP, logout revoke token aktif terbukti berfungsi. Session/cookie + CSRF untuk UI same-origin belum dimulai (menunggu Fase 3A); authorization role + wilayah + indikator belum selesai (menunggu schema Fase 2B). Tidak ada commit/push; perbaikan bersifat local verification. Fase 2B memulai dengan schema role/wilayah dan policy authorization; disetujui menunggu prompt lanjutan.
