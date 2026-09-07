# Prompt 01B.1 — Audit Kesiapan Runtime melalui Chat VS Code

Gunakan di Chat OpenAI VS Code yang memiliki akses ke workspace proyek.

```text
Lanjutkan proyek Dashboard Eksekutif Pertanahan pada Fase 1B.1 — audit kesiapan runtime dan dependency.

KONTEKS WAJIB — baca hanya file berikut terlebih dahulu:
- @PROJECT_CONTEXT_DASHBOARD_PERTANAHAN.md
- @AGENTS.md
- @docs/progress.md
- @docs/HANDOFF.md
- @docs/implementation-plan.md
- @api/composer.json
- @api/composer.lock
- @api/package.json

Jika ada lockfile frontend, baca hanya salah satu yang benar-benar ada: api/package-lock.json, api/yarn.lock, atau api/pnpm-lock.yaml. Jangan membaca ulang seluruh repository dan jangan membuka vendor/node_modules.

TUJUAN
Menentukan runtime/dependency yang tepat untuk Laravel 12 di api/, memeriksa kondisi aktual Windows/XAMPP secara read-only, dan membuat langkah instalasi manual yang aman. Fase ini belum melakukan instalasi atau perubahan aplikasi.

PEMERIKSAAN READ-ONLY
1. Pastikan root workspace dan folder api/ benar.
2. Dari manifest/lockfile, catat requirement minimum dan versi terkunci: PHP, Laravel, Sanctum, Composer packages, Node/npm/Vite packages.
3. Jalankan perintah PowerShell berikut satu per satu bila tersedia dan ringkas hasilnya; jangan mencetak environment atau rahasia:
   - Get-Command php, composer, node, npm, mysql -ErrorAction SilentlyContinue
   - where.exe php
   - where.exe composer
   - where.exe node
   - where.exe npm
   - where.exe mysql
   - php -v
   - php --ini
   - php -m
   - composer --version
   - node --version
   - npm --version
   - mysql --version
4. Periksa keberadaan tanpa mengubah:
   - api/vendor/autoload.php
   - api/node_modules/
   - api/.env.example
   - api/.env (hanya laporkan ada/tidak; jangan baca atau tampilkan isinya)
5. Dari php -m, cek extension yang dibutuhkan Laravel/aplikasi: openssl, pdo, pdo_mysql, mbstring, tokenizer, xml, ctype, json, bcmath, fileinfo, curl, zip. Bedakan wajib menurut dependency dan disarankan menurut proyek.
6. Identifikasi konflik PATH atau lebih dari satu php.exe. Kondisi yang sudah diketahui: PHP lokal sebelumnya 7.4.33, sedangkan Laravel membutuhkan PHP ^8.2.
7. Periksa apakah MySQL/MariaDB client tersedia dan service dapat dikenali tanpa login database, membuat database, atau mencoba password.
8. Tentukan apakah Python benar-benar dibutuhkan dari kode/importer. Jangan mengharuskan Python hanya karena audit lama pernah mencarinya.

OUTPUT DI DISK
- Buat/perbarui docs/05-runtime-readiness.md berisi:
  - requirement dari manifest/lockfile;
  - runtime aktual dan path executable;
  - tabel PASS/FAIL/BLOCKED;
  - extension PHP tersedia/hilang;
  - dependency tersedia/hilang;
  - konflik PATH;
  - kebutuhan Python: ya/tidak/belum pasti beserta bukti file;
  - rencana instalasi manual berurutan dengan lokasi yang aman;
  - perintah verifikasi setelah instalasi;
  - kriteria selesai Fase 1B.
- Perbarui docs/progress.md dan docs/HANDOFF.md hanya dengan hasil faktual audit Fase 1B.1.

BATASAN KETAT
- Jangan menginstal atau meng-upgrade PHP, XAMPP, Composer, Node/npm, MySQL, atau package.
- Jangan menjalankan composer install/update, npm install/update, Artisan, HTTP server, migration, seed, import, atau query database.
- Jangan membaca atau mengubah isi .env.
- Jangan mengubah file aplikasi, database, UI, test, workbook, config runtime, PATH, registry, atau service Windows.
- Jangan menghapus PHP/XAMPP lama.
- Jangan commit/push/PR.
- Jangan mengulang Tahap 0 atau Fase 1A.
- Jangan menampilkan output mentah yang panjang; ringkas maksimal 12 temuan.

SELESAI JIKA
- docs/05-runtime-readiness.md tersimpan.
- progress dan HANDOFF mencatat status Fase 1B.1 secara faktual.
- Tidak ada file di luar docs/ yang berubah.
- Jawaban akhir berisi: versi yang dibutuhkan, versi yang tersedia, blocker, tiga langkah manual berikutnya, dan file dokumentasi yang berubah.

Berhenti setelah audit. Tunggu pengguna menjalankan instalasi manual sebelum Fase 1B.2 verifikasi.
```

## Prompt sambungan singkat jika Chat terputus

```text
Lanjutkan Fase 1B.1 saja. Baca @docs/progress.md, @docs/HANDOFF.md, dan @docs/05-runtime-readiness.md. Selesaikan hanya audit runtime yang belum tercatat. Jangan membaca ulang repository, jangan menginstal dependency, jangan mengubah aplikasi, dan jangan push.
```

