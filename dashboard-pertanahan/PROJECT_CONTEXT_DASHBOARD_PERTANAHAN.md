# PROJECT CONTEXT — Dashboard Eksekutif Pertanahan untuk Pemda

Dokumen ini adalah sumber konteks tetap untuk OpenAI Codex di VS Code. Arahan pengguna terbaru dalam percakapan tetap lebih tinggi prioritasnya.

## 1. Tujuan proyek

Mengembangkan aplikasi Dashboard Eksekutif Pertanahan berbasis Laravel/PHP untuk menyajikan informasi pertanahan kepada pemerintah daerah. Proyek sudah mempunyai UI/UX dasar dari Login sampai Dashboard, tetapi menu, tampilan, dan data dashboard belum mengikuti kamus data; dashboard masih menampilkan data dummy provinsi lain.

Fokus pertama adalah Provinsi Jawa Barat. Arsitektur wajib siap dikembangkan ke provinsi lain, kabupaten/kota lain, dan banyak periode pelaporan tanpa mengubah struktur inti.

## 2. Teknologi dan batas kerja

- Keputusan Fase 1A: Laravel 12 di `api/` adalah aplikasi kanonis target; PHP 8.2+ harus kompatibel dengan composer.lock.
- Bahasa: PHP.
- Database target: MySQL/MariaDB, bukan SQLite sebagai fallback tersembunyi.
- Login: sudah ada API/autentikasi Laravel dari anggota tim dan wajib diaudit sebelum diubah.
- UI PHP native root adalah referensi visual dan sumber migrasi bertahap; jangan hapus halaman native sebelum pengganti Laravel mencapai feature parity dan telah diverifikasi. Target akhir satu Laravel, bukan dua auth/database paralel.
- Progres untuk saat ini hanya lokal di VS Code dan belum di-push ke GitHub.
- Jangan menyimpan password, token, data rahasia, atau `.env` ke Git.
- Jangan mengganti framework atau membangun ulang aplikasi sebelum audit membuktikan hal itu perlu dan pengguna menyetujuinya.

## 3. Dokumen dan data otoritatif

1. `data/source/Kamus_Data_Dashboard_Pertanahan_FINAL.xlsx`
   - Spesifikasi struktur menu, subtab, kartu/blok, nama indikator, tipe, satuan, formula, sumber, asal data, dan level input.
2. `data/source/Input Data Jawa Barat.xlsx`
   - Satu-satunya sumber data operasional awal untuk Provinsi Jawa Barat.

Jangan membaca baris/kolom Excel berdasarkan posisi yang ditebak. Header Excel Jawa Barat bertingkat pada baris 2–5; data aktual terdeteksi pada baris 6–30. Baris sampai 987 sebagian besar hanya terformat/kosong. Importer wajib mendeteksi dan mengabaikan baris kosong.

## 4. Aturan hierarki tampilan

- `Kategori Level 1` pada kamus = menu utama aplikasi.
- `Kategori Level 2 (Subtab)` = submenu/tab di dalam menu utama.
- `Kartu/Blok` = kelompok visual/section pada halaman submenu.
- `Nama Data` = indikator yang ditampilkan pada kartu, KPI, tabel, grafik, atau visual utama dashboard.
- `Tipe Data`, `Satuan`, dan `Formula/Cara Hitung` menentukan validasi, penyimpanan, format angka, dan perhitungan.
- `Asal Data` menentukan mekanisme input: sistem/Excel, manual Pemda, Kantor Pertanahan/KKP, atau turunan/campuran.

## 5. Struktur menu yang diminta pengguna

### Menu utama: Informasi Umum

Tampilan awal diprioritaskan untuk:

1. Total APL
   - Total Luasan APL.
   - Persentase APL bersertipikat.
   - Persentase APL belum bersertipikat.
2. Lahan Terpetakan
   - Total luasan APL terpetakan.
   - Total luasan APL belum terpetakan/KW456.
3. Kepemilikan E-Sertipikat
   - Persentase E-Sertipikat.

Data awal berasal dari Excel Jawa Barat sesuai mapping yang tervalidasi. Kamus juga memuat `Manajemen Isu Pertanahan & Ruang`; jangan menghilangkannya. Keputusan Fase 1A: kebutuhan ini tetap dipertahankan setelah tiga kelompok prioritas.

### Menu utama: Informasi Tematik

#### Submenu: Keuangan Daerah

1. Pendapatan Asli Daerah Sektor Pertanahan
   - PAD/Total PBB + BPHTB sebagai nilai turunan.
   - PBB: input manual Pemda menurut arahan pengguna, dengan data awal dari Excel Jawa Barat bila tersedia.
   - BPHTB: nilai awal Excel; integrasi API eksternal ditunda sampai kontrak akses tersedia. Metadata owner kamus tetap dicatat.
2. Integrasi Jumlah NIB–NOP
   - NIB: data awal Excel/sistem ATR-BPN.
   - NOP: input manual Pemda, dengan data awal dari Excel Jawa Barat bila tersedia.
   - GAP = total NIB - total NOP. Status/keterangan perbandingan diturunkan dari tanda GAP: NIB > NOP, NIB = NOP, atau NIB < NOP; bukan input bebas. Kolom koneksi sumber tetap evidence, bukan bukti teknis integrasi.
3. Infrastruktur ZNT
   - Cakupan ZNT, luas belum tercakup, tahun, nilai ZNT, atau indikator kamus yang mempunyai mapping valid.

#### Submenu: Iklim Investasi Daerah

1. Kredit Lembaga Keuangan: data Excel.
2. RDTR dan Integrasi OSS: input dasar jumlah total/terbit dan jumlah terintegrasi dari Excel; persen dihitung service, denominator nol/kosong menghasilkan NULL/no-data. SUDAH/BELUM sumber hanya evidence/QC sampai definisinya tervalidasi.
3. Penerbitan PKKPR: input manual Pemda.

#### Submenu: Optimalisasi Aset Pemda

1. Sertipikasi Aset Lahan Pemda: input manual Pemda.
2. Sebaran Aset Pemda: data KKP/Excel; desain harus mendukung kabupaten/kota, kecamatan, dan desa/kelurahan ketika data tersedia.

#### Submenu: Layanan Pertanahan

1. Intensitas Layanan Prioritas:
   - Pengecekan.
   - SKPT.
   - HT-EL.
   - Roya.
   - Peralihan.
   - Pendaftaran SK.
   - Perubahan Hak.
   - Sumber/input: Kantor Pertanahan.
2. Kelompok Pengguna Layanan.
3. Durasi Layanan Prioritas.
4. Durasi/Layanan Pengukuran.

#### Submenu: Aspek Tata Ruang

1. Integrasi KP2B/LP2B dalam RTRW: status sudah/belum; input Pemda sesuai kamus/arahan.
2. Pelepasan Kawasan Hutan: data Excel/sistem ATR-BPN sesuai mapping.

### Menu/submenu: Penampil Peta BHUMI

Keputusan Fase 1A: menu Penampil Peta BHUMI berkode kanonis `MAP.1`, placeholder/link terkonfigurasi. Simpan `source_code=XIX.1` dan baris peta asli sebagai lineage; XIX.1 tetap untuk Kelompok Pengguna Layanan. Jangan mengarang URL, endpoint, token, embed, atau akses API.

## 6. Input manual Pemda

- Wajib tersedia modul/form input manual khusus indikator milik Pemda.
- Hak akses harus membatasi pengguna pada wilayah yang diizinkan.
- Setiap nilai terikat pada provinsi, kabupaten/kota, periode, indikator, satuan, sumber, status data, pencatat, dan waktu perubahan.
- Perlu validasi tipe/rentang, CSRF, authorization, audit trail, dan pencegahan duplikasi.
- Nilai turunan seperti PAD total, GAP NIB–NOP, dan persentase tidak boleh diedit bebas apabila kamus menetapkannya sebagai formula.

## 7. Prinsip database

- Master wilayah hierarkis negara/provinsi/kabupaten-kota/kecamatan/desa-kelurahan sesuai kebutuhan. Reporting snapshot memiliki `as_of_date DATE` sebagai identitas tanggal data, tahun/bulan sebagai atribut/turunan filter; mendukung banyak snapshot wilayah/indikator.
- Gunakan kode indikator stabil dari kamus, bukan label UI sebagai primary key.
- Simpan nilai dengan tipe yang tepat; rupiah, luas, jumlah, persentase, status, tahun, dan rentang nilai tidak boleh dipaksa menjadi satu string tanpa strategi validasi.
- Simpan asal/sumber, metode input, versi file, checksum impor, status validasi, dan audit perubahan.
- Impor harus idempotent: file/periode/wilayah/indikator yang sama tidak menghasilkan duplikasi.
- Gunakan transaksi, foreign key, unique constraint, indeks filter dashboard, migration, seeder referensi, dan `.env.example` tanpa rahasia.
- Excel Jawa Barat adalah satu-satunya sumber nilai operasional awal, bukan seed dummy atau hardcode pada controller/Blade/JavaScript. Kamus hanya spesifikasi/metadata.

## 8. Temuan awal dua workbook

- Hasil Tahap 0: kamus mempunyai 58 entri fisik dan 57 kode sumber unik karena XIX.1 ganda. Keputusan target memisahkan MAP.1 (peta, source_code XIX.1) dan XIX.1 (layanan), tanpa mengubah workbook. Lima subtab tematik tetap.
- Excel input Jawa Barat mempunyai 54 kolom dan 25 kabupaten/kota terisi pada baris 6–30.
- Excel terformat hingga baris 987, tetapi baris kosong tidak boleh dianggap data.
- Terdapat formula Excel untuk persentase, total PBB+BPHTB, persentase sertipikat HT, dan indikator turunan lain.
- Nomor wilayah pada data tidak berurutan; jangan memakai kolom `NO` sebagai identitas wilayah.
- Header sumber bertuliskan `DAREAH`; model aplikasi menggunakan nama baku `daerah`/`wilayah` tanpa mengubah bukti sumber.
- Data Jawa Barat belum mencakup seluruh kabupaten/kota Jawa Barat. Jangan menciptakan nilai untuk wilayah yang tidak tersedia; sistem hanya menyiapkan master wilayah dan menampilkan status belum ada data.
- Sejumlah kolom Excel input tidak muncul persis dalam kamus, dan beberapa sumber dalam kamus berbeda dengan arahan pengguna. Semua perbedaan harus dicatat dalam matriks rekonsiliasi.

## 9. Aturan keputusan ketika ada perbedaan

Urutan prioritas:

1. Arahan terbaru pengguna.
2. Kamus data final sebagai spesifikasi indikator.
3. Excel Input Jawa Barat sebagai sumber nilai awal.
4. Implementasi/dummy lama sebagai referensi UI saja.

Jangan menebak mapping ambigu. Masukkan ke `docs/data-reconciliation.md` dengan kolom: kode, indikator, sumber menurut kamus, arahan pengguna, kolom Excel kandidat, keputusan sementara, dan kebutuhan konfirmasi.

## 10. Definition of done umum

Sebuah fase dinyatakan selesai hanya jika:

- Perubahan nyata tersimpan di disk.
- Daftar file yang berubah dilaporkan.
- Pemeriksaan/test yang relevan dijalankan dan hasilnya dicatat.
- Tidak ada data dummy lama yang masih memengaruhi komponen yang sudah dimigrasikan.
- Perhitungan sampel direkonsiliasi dengan Excel.
- UI responsif dan memiliki loading, empty, error, serta no-data state.
- `docs/progress.md` diperbarui agar thread Codex baru dapat melanjutkan tanpa mengulang audit.


## 11. Keputusan permanen Fase 1A (2026-09-06)

Bagian ini adalah **keputusan target pengguna, bukan hasil implementasi/verifikasi runtime**. Fakta audit tetap di docs/00-current-state-audit.md dan docs/01-auth-api-audit.md. Scope aktif hanya dokumentasi PROJECT_CONTEXT, AGENTS.md dan docs/; berhenti setelah Fase 1A. Jangan mengubah aplikasi, manifest/dependency, .env, migration, data/SQLite/Excel, route/controller/model/service/test/UI; jangan menjalankan migration/import/seed/HTTP, instalasi, commit/push/PR.

| Keputusan | Kontrak permanen |
|---|---|
| Aplikasi | Laravel 12/PHP 8.2+ kompatibel lock di api/ kanonis; docroot api/public/. Native dipertahankan sampai feature parity dan verifikasi pengganti, lalu auth native dipensiunkan. Bukan dua sistem permanen. |
| Database | MySQL/MariaDB + PDO MySQL untuk development dan production. Tidak ada silent fallback SQLite saat MySQL gagal atau dummy/auto-seed runtime production. data/dashboard.sqlite tetap artefak legacy sampai audit migrasi selesai; jangan dihapus. |
| Identitas | Laravel/Sanctum sumber identitas tunggal. UI same-origin memprioritaskan session/cookie stateful + CSRF aman; API tim diperkuat kemudian (logout/revoke, rate limit, validation, cookie/session, role dan scope wilayah). |
| Tanggal snapshot | as_of_date identitas tanggal data, bukan tahun/bulan saja. Tanggal/cakupan sumber mentah tetap lineage; tanggal awal belum diberikan, jangan menebak dari sheet atau hari ini. Pilihan tanggal valid tanpa nilai harus no-data, tidak dialihkan diam-diam ke latest. Snapshot stok tidak dijumlahkan antar tanggal hanya karena bulan sama. |
| Wilayah | Awal aktif Jawa Barat; impor nilai hanya 25 wilayah Excel. Master tanpa nilai berstatus belum ada data, bukan nol/dummy; NO bukan PK. Authority/versi master yang disahkan masih dibutuhkan. |
| Kode | XIX.1 tetap indikator Kelompok Pengguna Layanan; MAP.1 untuk fitur/menu BHUMI dengan source_code XIX.1, source row 62, label asli PETA GTRA. Peta bukan nilai numerik. |
| GAP | NIB-NOP bertanda; status NIB > NOP / NIB = NOP / NIB < NOP mengikuti hasil. Raw P6/P7 dipertahankan, quality flag untuk teks terbalik; tampilan memakai hitung kanonis. Jika input dasar missing, hasil/status no-data. |
| RDTR | Simpan total/terbit AO dan terintegrasi AP sebagai input sumber. Persen = AP/AO*100; belum = 100-persen hanya denominator valid. AO nol/kosong -> kedua persen NULL/no-data. AQ/AR dan pertanyaan header tetap evidence/QC, bukan input manual persen. |
| Sumber/owner | Nilai awal hanya Input Data Jawa Barat.xlsx. PBB/NOP Excel awal lalu manual Pemda; PKKPR/FPR/indikator Pemda tanpa kolom valid manual dan awal no-data. BPHTB Excel awal, API tertunda kontrak. NIB/kredit/RDTR/TORA/data umum tersedia impor dengan lineage. Sertipikasi aset lahan Pemda dikelola manual Pemda; nilai awal Excel hanya mapping valid, persen dihitung dari input dasar. |
| Kekosongan sumber | Sebaran aset Excel/KKP hanya bila detail/objek valid tersedia, kecamatan/desa no-data jika absen. Layanan Kantah, E-Sertipikat, ZNT detail/dominan dan indikator tanpa kolom valid no-data. BHUMI placeholder/link terkonfigurasi saja, tanpa API/token/embed rekaan. |
| QC | Jangan ubah kedua workbook. Simpan raw value, formula/shared master, result/cache, row/column, sheet dan file checksum. Sepuluh selisih luas ±1 ha diberi quality flag; nilai sumber dipertahankan, turunan service konsisten (I.2=100*D/C; I.3=100-I.2). Dash/kosong berbeda dari nol. Formula dihitung domain/service, bukan manual bebas. |
| Runtime | Laravel 12 dan PHP 8.2 atau versi yang kompatibel composer.lock; MySQL/MariaDB PDO MySQL; Composer/Node/npm sesuai lock/package-lock bila ada. Tidak ada instalasi atau perubahan runtime pada Fase 1A. |

Hierarki final: Kategori Level 1 → menu; Level 2/Subtab → submenu; Kartu/Blok → section; Nama Data → KPI/tabel/grafik/visual. Informasi Umum memprioritaskan Total APL, Lahan Terpetakan, E-Sertipikat dan tetap menyertakan kebutuhan lainnya. Informasi Tematik mempunyai lima submenu sesuai bagian 5; Kelompok Pengguna Layanan tetap pada Layanan Pertanahan. MAP.1 menu Penampil Peta BHUMI.

Tahapan berikut: 1B runtime/dependency; 2A fondasi Laravel + auth hardening; 2B migration/master MySQL; 3A katalog dan manual Pemda; 3B importer Excel + QC; 4A shell/navigation; 4B Informasi Umum; 4C tematik per submenu; 5 responsivitas/keamanan/QA/deployment. Rincian goal, sasaran, test, risiko dan done criteria di docs/implementation-plan.md. Fase 1B belum dimulai dan memerlukan scope pelaksanaan berikutnya. Untuk fase dokumentasi, done criteria hanya dokumen/konsistensi/hash; tidak mengklaim feature parity atau keberhasilan runtime.
