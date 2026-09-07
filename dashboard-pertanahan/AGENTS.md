# AGENTS.md

## Konteks dan scope aktif
Workspace: `C:/xampp/htdocs/dashboard-pertanahan`. Root Laravel: `api/`; document root Laravel: `api/public/`. UI lama adalah PHP native di root. Jangan menyamakan kedua autentikasi/database tersebut.

Sebelum bekerja, baca SELURUH `PROJECT_CONTEXT_DASHBOARD_PERTANAHAN.md`, kemudian `docs/progress.md` dan dokumen Tahap 0 yang relevan. Urutan prioritas: arahan pengguna terbaru > kamus final > Excel Jawa Barat > kode/dummy lama. Konteks tetap wajib digunakan; konflik dicatat dalam `docs/data-reconciliation.md`, bukan diperbaiki diam-diam. Catatan implementasi sebelum reset bukan bukti kondisi sekarang.

Scope saat ini Fase 1A dokumentasi saja. Hanya `PROJECT_CONTEXT_DASHBOARD_PERTANAHAN.md`, `AGENTS.md` dan dokumentasi/bukti di `docs/` boleh ditulis. Jangan mengubah controller, model, route, middleware, migration, seeder, Blade/view, CSS, JavaScript, API login, `.env`, konfigurasi, database, atau workbook. Jangan instal dependency, memakai SQLite (termasuk test in-memory), membuat fallback, menghapus file, commit/push atau membuat PR. Berhenti setelah Fase 1A; jangan memulai Fase 1B sebelum scope berikutnya diberikan. Jangan menjalankan initializer, migration, seeder, importer atau HTTP apa pun pada scope ini.

## Laravel/PHP setelah scope implementasi disetujui
Pertahankan Laravel yang ada, PHP sesuai `api/composer.json` (>=8.2 dalam constraint ^8.2), Eloquent/query builder atau PDO prepared statements. Hindari rewrite umum/framework baru. Baca file sasaran sebelum edit; perubahan sekecil yang diperlukan. Pisahkan validasi request, authorization wilayah/indikator, perhitungan, penyimpanan, dan rendering secara bertahap. Pertahankan dasar visual UI lama; tujuan navigasi mengikuti kamus/konteks.

## Keamanan
Jangan menyalin password, hash password, token, isi `.env`, cookie/session, atau rahasia ke Git, docs, komentar, keluaran tool, atau log. Dokumentasikan lokasi temuan tanpa nilai rahasianya. Jangan seed akun demo di production atau menampilkan kredensial pada UI. Validasi tipe, kalender, wilayah, peran, satuan, dan payload eksternal; escape HTML dengan Blade escaped output atau `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. CSRF untuk aksi session, pembatasan percobaan login, rotasi sesi, cookie aman, pencabutan/expiry token dan authorization wajib diuji. Role saja tidak cukup untuk membatasi wilayah Pemda. Jangan gunakan eval/exec/shell_exec untuk logika bisnis atau formula; formula harus terdaftar/terversi. Jangan mengirim pesan ke pihak lain tanpa instruksi pengguna.

## Database dan sumber
Target tunggal MySQL/MariaDB; tidak ada fallback SQLite. Konfigurasi environment tanpa rahasia di source; konfigurasi produksi baru memerlukan persetujuan. Jangan menjalankan `composer run setup`/post-create tanpa meninjau efeknya (migrate/install/SQLite). Migration versi terpisah dari seed referensi dan dummy. Transaksi, FK, unique key, audit trail, idempotensi dan decimal tepat wajib dijaga. Jangan menimpa nilai approved dengan import draft.

Baca dua workbook asli di `data/source/`, termasuk kedua sheet kamus, header bertingkat, merge, formula shared, cached value dan styles. Kamus aktual memiliki 58 entri fisik, 57 kode unik karena XIX.1 ganda; jangan memakai No sebagai PK. Keputusan Fase 1A: XIX.1 layanan dipertahankan, peta berkode kanonis MAP.1 dengan source_code XIX.1 dan source row 62; workbook tidak diubah. Excel Jawa Barat A:BB, 25 baris wilayah 6–30; baris 31–987 kosong nilai. NO bukan identitas wilayah. Jangan menciptakan data wilayah yang absen, mengubah null menjadi nol, menyamakan sertipikat biasa dengan elektronik, atau menebak periode/formula ambigu.

## Run dan testing
Manifest Laravel ^12.0; lock v12.69.1 dan Sanctum v4.3.3. PHP audit 7.4.33 tidak kompatibel; vendor, Composer/Node/npm tidak tersedia pada pemeriksaan. Jangan mengklaim Laravel berjalan. Setelah prasyarat dan scope disetujui: root `api/`, `php artisan serve`, frontend sesuai `npm run dev/build`, database MySQL terisolasi untuk test. UI native mempunyai petunjuk `php -S localhost:8000 router.php`, tetapi request memulai session dan `db()` membuka/menulis SQLite; tidak dijalankan pada Tahap 0.

Verifikasi harus sesuai scope: lint baca-saja, inspeksi/hash sumber, coverage mapping. Pengujian runtime Laravel perlu PHP yang cocok/dependency; test database harus MySQL khusus, bukan data kerja. Jangan jalankan suite yang menulis data pada audit baca-saja. Catat alat yang tidak tersedia, exit code, kegagalan dan yang belum diuji; lint sukses bukan bukti login/UI bekerja. Tinjau diff; karena file awal untracked, bandingkan SHA256 baseline, bukan mengandalkan git diff kosong.

## Dokumentasi wajib setiap fase
Perbarui `docs/progress.md` setelah setiap fase: `# Progress`, `## Tanggal`, `## Tugas` (checkbox nyata), `## File yang diperiksa`, `## Perubahan kecil yang dibuat`, `## Verifikasi yang dijalankan`, `## Hasil verifikasi`, `## Risiko / catatan`, `## Selanjutnya`. Sertakan blocker, keputusan, prompt berikutnya. Bedakan fakta inspeksi, inferensi, rekomendasi. Cantumkan path dan class/function untuk temuan. Jangan tandai selesai sebelum seluruh output tersimpan dan diverifikasi; laporkan file berubah serta hasil verifikasi.

## Kontrak keputusan Fase 1A — target, bukan verifikasi

- Aplikasi kanonis Laravel 12 di api/, identitas tunggal Laravel/Sanctum. UI same-origin memakai prioritas session/cookie stateful + CSRF. API tim diperkuat pada 2A: logout/revoke, rate limit, validation, cookie/session, role/scope wilayah; auth native dipensiunkan setelah UI bermigrasi. Halaman native tidak dihapus sebelum feature parity pengganti terbukti.
- MySQL/MariaDB untuk development/production, PDO MySQL; gagal koneksi harus eksplisit, tanpa fallback SQLite atau dummy production. data/dashboard.sqlite dipertahankan sementara sebagai artefak legacy sampai audit migrasi selesai.
- Reporting snapshot memakai as_of_date DATE; tahun/bulan turunan filter, bukan pengganti tanggal sumber. Valid date tanpa nilai -> no-data, tidak redirect latest. Dukung negara hingga desa/kelurahan; awal Jawa Barat, nilai hanya 25 wilayah sumber, NO bukan PK.
- GAP = NIB-NOP; tanda menentukan NIB > NOP / NIB = NOP / NIB < NOP. P6/P7 mentah dan QC tetap, tampilan hitung kanonis. Status perbandingan bukan klaim koneksi teknis dari M.
- RDTR memakai total/terbit AO dan terintegrasi AP. Persen AP/AO*100 dan komplemen hanya jika denominator valid; total nol/kosong -> NULL/no-data keduanya. AQ/AR hanya evidence/QC sampai definisi tervalidasi.
- Satu-satunya nilai operasional awal: Excel Jawa Barat; kamus metadata. PBB/NOP Excel awal lalu manual Pemda; PKKPR dan indikator Pemda tanpa kolom valid manual/no-data awal. Aset lahan Pemda manual, nilai awal Excel hanya jika mapping valid. BPHTB Excel awal dan API tertunda kontrak. NIB/kredit/RDTR/TORA/data umum tersedia lewat impor dengan lineage.
- Sebaran aset hanya detail Excel/KKP valid; kec/desa dan layanan Kantah/E-Sertipikat/ZNT detail/dominan tanpa sumber tetap no-data. MAP.1 BHUMI placeholder/link terkonfigurasi, source_code XIX.1; tanpa API/token/embed rekaan.
- Raw value/formula/result/row/column/checksum disimpan; 10 selisih luas ±1 ha dan 2 teks GAP terbalik diberi quality flag tanpa mengubah Excel. Dash/kosong bukan nol. Domain/service menghitung turunan, tidak menerima input manual bebas. Tetap pertahankan seluruh 58 entri/54 kolom dalam dokumentasi.
- Hierarki menu final mengikuti PROJECT_CONTEXT bagian 5/11 dan blueprint: Umum tiga prioritas + kebutuhan lain; Tematik lima submenu; MAP.1. Jangan menjalankan ulang generator dokumen Tahap 0 untuk menimpa keputusan Fase 1A.
- PHP target 8.2+ yang kompatibel composer.lock, Composer dan Node/npm sesuai lock bila tersedia; tidak instal/ubah runtime sekarang. Fakta PHP 7.4.33/dependency belum tersedia dari Tahap 0 tetap blocker yang belum diuji ulang.
- Setelah fase, update docs/progress.md. Baseline Tahap 0 mencakup PROJECT_CONTEXT sebagai salah satu dari 115 file: perubahan dokumen ini kini diizinkan dan dilaporkan terpisah, bukan baseline baru untuk menyembunyikan perubahan aplikasi. Bandingkan 114 entri lainnya dan laporkan hash/diff dokumen; jangan memperbarui bukti Tahap 0.
