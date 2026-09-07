# Blueprint menu dan dashboard — keputusan Fase 1A

2026-09-06. Hierarki dan kode adalah keputusan target Fase 1A, bukan UI terimplementasi/terverifikasi. Bentuk visual tetap rekomendasi. Seluruh 58 entri fisik kamus dipertahankan; kode kanonis peta MAP.1 menyimpan source_code XIX.1, baris 62 dan label PETA GTRA. Layanan tetap XIX.1.

## Hierarki final yang dikunci

1. Informasi Umum: Total APL → Lahan Terpetakan → Kepemilikan E-Sertipikat → Manajemen Isu Pertanahan & Ruang. E-Sertipikat tetap ada dengan no-data.
2. Informasi Tematik: Keuangan Daerah → Iklim Investasi Daerah → Optimalisasi Aset Pemda → Layanan Pertanahan → Aspek Tata Ruang. Kelompok Pengguna tetap di Layanan sesuai sheet utama/konteks.
3. Penampil Peta BHUMI (MAP.1): menu utama untuk kebutuhan PETA GTRA. Placeholder/link terkonfigurasi; tanpa URL/API/token/embed/geometri rekaan.
4. Utilitas sesuai role/scope: Input Pemda, validasi dan impor admin, profil/logout. Sinkronisasi KKP memerlukan kontrak/izin sebelum diaktifkan.

## Kategori Level 1 → Subtab → Kartu/Blok → Nama Data → visual

| Baris/kode | Kategori Level 1 | Subtab | Kartu/Blok | Nama Data | Visual disarankan | Sumber awal |
|---|---|---|---|---|---|---|
| r5 / I.1 | Informasi Umum | — | Total APL | Total Luasan APL | KPI dan tabel/perbandingan wilayah | Exact match |
| r6 / I.2 | Informasi Umum | — | Total APL | % Luasan APL Bersertifikat | KPI % dan bar wilayah; tampilkan denominator | Transformasi/formula |
| r7 / I.3 | Informasi Umum | — | Total APL | % Luasan APL Belum Bersertifikat | KPI % dan bar wilayah; tampilkan denominator | Transformasi/formula |
| r8 / II.1 | Informasi Umum | — | Lahan Terpetakan | Total Luasan APL Terpetakan | KPI dan tabel/perbandingan wilayah | Transformasi/formula |
| r9 / II.2 | Informasi Umum | — | Lahan Terpetakan | Total Luasan APL Belum Terpetakan | KPI dan tabel/perbandingan wilayah | Exact match |
| r10 / III.1 | Informasi Umum | — | Manajemen Isu Pertanahan & Ruang | GTRA - Status Pembentukan | Badge dan tabel status; unknown terpisah | Exact match |
| r11 / III.2 | Informasi Umum | — | Manajemen Isu Pertanahan & Ruang | GTRA - Dukungan APBN (2026) | Badge dan tabel status; unknown terpisah | Exact match |
| r12 / III.3 | Informasi Umum | — | Manajemen Isu Pertanahan & Ruang | FPR - Status Pembentukan | Badge dan tabel status; unknown terpisah | Input manual Pemda |
| r13 / III.4 | Informasi Umum | — | Manajemen Isu Pertanahan & Ruang | FPR - Tahun Pembentukan | Tahun dan tabel wilayah | Input manual Pemda |
| r14 / IV.1 | Informasi Umum | — | Kepemilikan E-Sertifikat | % E-Sertifikat | KPI % dan bar wilayah; tampilkan denominator | KKP/Kantah belum tersedia |
| r15 / XIX.1 | Informasi Tematik | Layanan Pertanahan | Kelompok Pengguna Layanan | Kuasa( PPAT/PPATS) | KPI % dan bar wilayah; tampilkan denominator | KKP/Kantah belum tersedia |
| r16 / XIX.2 | Informasi Tematik | Layanan Pertanahan | Kelompok Pengguna Layanan | Kuasa PPAT/PPATS (jumlah berkas) | KPI dan tabel/perbandingan wilayah | KKP/Kantah belum tersedia |
| r17 / XIX.3 | Informasi Tematik | Layanan Pertanahan | Kelompok Pengguna Layanan | % Non Kuasa (Pemohon Langsung) | KPI % dan bar wilayah; tampilkan denominator | KKP/Kantah belum tersedia |
| r18 / XIX.4 | Informasi Tematik | Layanan Pertanahan | Kelompok Pengguna Layanan | Non Kuasa - Pemohon Langsung (jumlah berkas) | KPI dan tabel/perbandingan wilayah | KKP/Kantah belum tersedia |
| r19 / V.1 | Informasi Tematik | Keuangan Daerah | Pendapatan Asli Daerah | Total PAD (PBB+BPHTB) | KPI dan tabel/perbandingan wilayah | Transformasi/formula |
| r20 / V.2 | Informasi Tematik | Keuangan Daerah | Pendapatan Asli Daerah | Total PBB | KPI dan tabel/perbandingan wilayah | Transformasi/formula |
| r21 / V.3 | Informasi Tematik | Keuangan Daerah | Pendapatan Asli Daerah | Total BPHTB | KPI dan tabel/perbandingan wilayah | Transformasi/formula |
| r22 / VI.1 | Informasi Tematik | Keuangan Daerah | Integrasi NIB-NOP | Total NIB (bidang) | KPI dan tabel/perbandingan wilayah | Exact match |
| r23 / VI.2 | Informasi Tematik | Keuangan Daerah | Integrasi NIB-NOP | Total NOP (bidang) | KPI dan tabel/perbandingan wilayah | Exact match |
| r24 / VI.3 | Informasi Tematik | Keuangan Daerah | Integrasi NIB-NOP | GAP NIB-NOP (bidang) | GAP bertanda dan bar; status NIB > NOP / NIB = NOP / NIB < NOP dari tanda | Transformasi/formula |
| r25 / VII.1 | Informasi Tematik | Keuangan Daerah | Infrastruktur ZNT | % Cakupan Luas ZNT | KPI % dan bar wilayah; tampilkan denominator | Exact match |
| r26 / VII.2 | Informasi Tematik | Keuangan Daerah | Infrastruktur ZNT | % Luas ZNT Detail | KPI % dan bar wilayah; tampilkan denominator | KKP/Kantah belum tersedia |
| r27 / VII.3 | Informasi Tematik | Keuangan Daerah | Infrastruktur ZNT | Nilai Bidang Tanah (paling dominan) | Kelas/rentang min–max, bukan rata-rata | Tidak tersedia/ambigu |
| r28 / VIII.1 | Informasi Tematik | Iklim Investasi Daerah | Kredit Lembaga Keuangan | Total Nilai Kredit | KPI dan tabel/perbandingan wilayah | Transformasi/formula |
| r29 / VIII.2 | Informasi Tematik | Iklim Investasi Daerah | Kredit Lembaga Keuangan | % Sertipikat di HT-kan | KPI % dan bar wilayah; tampilkan denominator | Transformasi/formula |
| r30 / IX.1 | Informasi Tematik | Iklim Investasi Daerah | RDTR dan Integrasi OSS | Jumlah RDTR Terbit Perkada | KPI dan tabel/perbandingan wilayah | Excel AO input total/terbit; metadata/QC |
| r31 / IX.2 | Informasi Tematik | Iklim Investasi Daerah | RDTR dan Integrasi OSS | % RDTR Terintegrasi OSS | KPI % dan bar wilayah; tampilkan denominator | Service AP/AO; AO nol/kosong -> NULL/no-data; AQ/AR evidence |
| r32 / IX.3 | Informasi Tematik | Iklim Investasi Daerah | RDTR dan Integrasi OSS | % RDTR Belum Terintegrasi OSS | KPI % dan bar wilayah; tampilkan denominator | Service AP/AO; AO nol/kosong -> NULL/no-data; AQ/AR evidence |
| r33 / X.1 | Informasi Tematik | Iklim Investasi Daerah | Penerbitan PKKPR | Total Persetujuan KKPR | KPI dan tabel/perbandingan wilayah | Input manual Pemda |
| r34 / X.2 | Informasi Tematik | Iklim Investasi Daerah | Penerbitan PKKPR | Nilai Potensi Investasi | KPI dan tabel/perbandingan wilayah | Input manual Pemda |
| r35 / XI.1 | Informasi Tematik | Optimalisasi Aset Pemda | Sertipikasi Aset Lahan Pemda | Aset Belum Sertipikat (%) | KPI % dan bar wilayah; tampilkan denominator | Transformasi/formula |
| r36 / XI.2 | Informasi Tematik | Optimalisasi Aset Pemda | Sertipikasi Aset Lahan Pemda | Aset Belum Sertipikat (Luas m²) | KPI dan tabel/perbandingan wilayah | Exact match |
| r37 / XI.3 | Informasi Tematik | Optimalisasi Aset Pemda | Sertipikasi Aset Lahan Pemda | Aset Belum Sertipikat (Nilai Rp) | KPI dan tabel/perbandingan wilayah | Transformasi/formula |
| r38 / XII.1 | Informasi Tematik | Optimalisasi Aset Pemda | Sebaran Aset Pemda | Aset Tersertipikat (jumlah) | Tabel sebaran; peta hanya dengan geometri valid | Tidak tersedia/ambigu |
| r39 / XII.2 | Informasi Tematik | Optimalisasi Aset Pemda | Sebaran Aset Pemda | Sebaran Aset - Kab/Kota (%) | Tabel sebaran; peta hanya dengan geometri valid | Tidak tersedia/ambigu |
| r40 / XII.3 | Informasi Tematik | Optimalisasi Aset Pemda | Sebaran Aset Pemda | Sebaran Aset - Kecamatan (%) | Tabel sebaran; peta hanya dengan geometri valid | KKP/Kantah belum tersedia |
| r41 / XII.4 | Informasi Tematik | Optimalisasi Aset Pemda | Sebaran Aset Pemda | Sebaran Aset - Desa/Kelurahan (%) | Tabel sebaran; peta hanya dengan geometri valid | KKP/Kantah belum tersedia |
| r42 / XIII | Informasi Tematik | Layanan Pertanahan | Intensitas Layanan Prioritas | Jumlah Berkas | KPI dan tabel/perbandingan wilayah | KKP/Kantah belum tersedia |
| r43 / XIII.1 | Informasi Tematik | Layanan Pertanahan | Intensitas Layanan Prioritas | Pengecekan  % | KPI % dan bar wilayah; tampilkan denominator | KKP/Kantah belum tersedia |
| r44 / XIII.2 | Informasi Tematik | Layanan Pertanahan | Intensitas Layanan Prioritas | SKPT % | KPI % dan bar wilayah; tampilkan denominator | KKP/Kantah belum tersedia |
| r45 / XIII.3 | Informasi Tematik | Layanan Pertanahan | Intensitas Layanan Prioritas | HT-EL (%) | KPI % dan bar wilayah; tampilkan denominator | KKP/Kantah belum tersedia |
| r46 / XIII.4 | Informasi Tematik | Layanan Pertanahan | Intensitas Layanan Prioritas | Roya (%) | KPI % dan bar wilayah; tampilkan denominator | Tidak tersedia/ambigu |
| r47 / XIII.5 | Informasi Tematik | Layanan Pertanahan | Intensitas Layanan Prioritas | Peralihan (%) | KPI % dan bar wilayah; tampilkan denominator | KKP/Kantah belum tersedia |
| r48 / XIII.6 | Informasi Tematik | Layanan Pertanahan | Intensitas Layanan Prioritas | Pendaftaran SK (%) | KPI % dan bar wilayah; tampilkan denominator | KKP/Kantah belum tersedia |
| r49 / XIII.7 | Informasi Tematik | Layanan Pertanahan | Intensitas Layanan Prioritas | Perubahan Hak (%) | KPI % dan bar wilayah; tampilkan denominator | KKP/Kantah belum tersedia |
| r50 / XV.1 | Informasi Tematik | Layanan Pertanahan | Durasi Layanan Prioritas | Pengecekan (durasi) | KPI durasi dan bar layanan; definisi statistik | KKP/Kantah belum tersedia |
| r51 / XV.2 | Informasi Tematik | Layanan Pertanahan | Durasi Layanan Prioritas | SKPT (durasi) | KPI durasi dan bar layanan; definisi statistik | KKP/Kantah belum tersedia |
| r52 / XV.3 | Informasi Tematik | Layanan Pertanahan | Durasi Layanan Prioritas | HT-EL (durasi) | KPI durasi dan bar layanan; definisi statistik | KKP/Kantah belum tersedia |
| r53 / XV.4 | Informasi Tematik | Layanan Pertanahan | Durasi Layanan Prioritas | Roya (durasi) | KPI durasi dan bar layanan; definisi statistik | KKP/Kantah belum tersedia |
| r54 / XV.5 | Informasi Tematik | Layanan Pertanahan | Durasi Layanan Prioritas | Peralihan (durasi) | KPI durasi dan bar layanan; definisi statistik | KKP/Kantah belum tersedia |
| r55 / XV.6 | Informasi Tematik | Layanan Pertanahan | Durasi Layanan Prioritas | Pendaftaran SK (durasi) | KPI durasi dan bar layanan; definisi statistik | KKP/Kantah belum tersedia |
| r56 / XV.7 | Informasi Tematik | Layanan Pertanahan | Durasi Layanan Prioritas | Perubahan Hak (durasi) | KPI durasi dan bar layanan; definisi statistik | KKP/Kantah belum tersedia |
| r57 / XVI.1 | Informasi Tematik | Layanan Pertanahan | Layanan Pengukuran Lahan | Waktu Tunggu Pengukuran | KPI durasi dan bar layanan; definisi statistik | KKP/Kantah belum tersedia |
| r58 / XVI.2 | Informasi Tematik | Layanan Pertanahan | Layanan Pengukuran Lahan | Durasi Pengukuran | KPI durasi dan bar layanan; definisi statistik | KKP/Kantah belum tersedia |
| r59 / XVII.1 | Informasi Tematik | Aspek Tata Ruang | Integrasi KP2B/LP2B dalam RTRW | Status Penerbitan SK KP2B/LP2B | Badge dan tabel status; unknown terpisah | Input manual Pemda |
| r60 / XVII.2 | Informasi Tematik | Aspek Tata Ruang | Integrasi KP2B/LP2B dalam RTRW | Integrasi ke Dalam RTRW | Badge dan tabel status; unknown terpisah | Tidak tersedia/ambigu |
| r61 / XVIII.1 | Informasi Tematik | Aspek Tata Ruang | Pelepasan Kawasan Hutan | Status Belum Tersertipikat (Ha) | KPI dan tabel/perbandingan wilayah | Exact match |
| r62 / MAP.1 | Penampil Peta BHUMI | — | Penampil Peta | Penampil Peta BHUMI | Placeholder/link terkonfigurasi | Fitur nonnumerik; source_code XIX.1, label asli PETA GTRA |

## Filter, provenance dan state

- Filter global provinsi, kab/kota, kecamatan, desa/kelurahan (hanya bila referensi/data tersedia) dan snapshot as_of_date eksplisit; tahun/bulan turunan filter, bukan pengganti tanggal sumber. Tanggal valid tanpa nilai tetap no-data, tidak latest otomatis. Master mendukung negara sebagai parent provinsi sesuai kebutuhan. Awal Jawa Barat, coverage 25 wilayah; wilayah/provinsi lain no-data. Jangan agregasikan parent dan child sekaligus.
- Setiap KPI memiliki unit, periode laporan dan cakupan waktu sumber, owner, status validasi, coverage dan waktu pembaruan. Rasio provinsi dihitung dari jumlah numerator/denominator sebanding, bukan sum persen. Durasi tidak dijumlahkan.
- Keuangan: PAD dilabeli subtotal PBB+BPHTB. PBB/NOP manual; PAD/GAP/rasio formula terkunci. Status/keterangan perbandingan diturunkan dari tanda GAP (>, =, <); M koneksi hanya evidence, bukan klaim integrasi teknis. P6/P7 raw + quality flag tetap.
- Aset XI manual Pemda dengan Excel awal; XII membutuhkan objek Hak Pakai. Tabel sebelum peta; jangan membuat titik/poligon kecamatan/desa rekaan.
- ZNT hanya VII.1 cocok langsung. AC/AD/AE/AF metadata tambahan setelah persetujuan, bukan pengganti VII.2/VII.3. Kolom ekstra tidak otomatis menu baru.
- Layanan: tujuh prioritas, kelompok pengguna, durasi dan pengukuran; semua no-data sampai sumber Kantah tersedia, tidak memakai dummy validasi.
- BHUMI: label presentasi pengguna, source_label PETA GTRA/r62 tetap terlacak. Kode kanonis MAP.1 diputuskan; placeholder/link terkonfigurasi saja. Link menunggu konfigurasi sah; API/embed tidak dibuat.
- State: loading, empty filter, no-data, error/retry aman, draft/rejected, serta nol hanya jika sumber nol. Badge terverifikasi hanya dari approval.
- Pertahankan card/sidebar/tabel/tema/logo dan pola responsif lama. Kelak uji keyboard, fokus, kontras, 360/768/1366, overflow, unit dan provenance. Belum ada verifikasi browser.
- Konflik Legenda: Kelompok Pengguna di Umum dan MVP hanya dua layanan; keputusan final Fase 1A mengikuti konteks terbaru/sheet utama, lima submenu dan tujuh layanan, tanpa menghapus entri.

## Perilaku target lintas halaman

Kategori Level 1 = menu utama; Level 2/Subtab = submenu; Kartu/Blok = section visual; Nama Data = KPI/tabel/grafik/visual. Urutan visual mengikuti daftar final di atas, bukan urutan fisik baris sumber pada tabel lineage. Informasi Umum tidak menghapus Manajemen Isu meskipun tiga kelompok lain diprioritaskan.

UI akan bermigrasi ke Laravel 12 api/, session/cookie stateful + CSRF melalui identitas Laravel/Sanctum tunggal. Halaman native tidak dihapus sebelum feature parity pengganti dan verifikasi; auth native dipensiunkan setelah migrasi UI. MySQL/MariaDB menjadi satu database development/production; kegagalan tidak memicu SQLite/auto-seed atau dashboard dummy. Artefak data/dashboard.sqlite tetap dipertahankan.

RDTR: tampilkan input total/terbit AO dan terintegrasi AP; persen AP/AO*100 dan komplemen hanya denominator valid. AO nol/kosong menampilkan no-data pada kedua persen; SUDAH/BELUM AQ/AR hanya evidence/QC sampai definisinya tervalidasi. APL selisih ±1 ha diberi quality flag; nilai sumber tetap, turunan service konsisten. Dash/blank tampil belum ada data, nol hanya dari sumber. Semua turunan read-only.

PBB/NOP awal Excel lalu pembaruan manual Pemda. BPHTB Excel awal, API ditunda kontrak. PKKPR dan Pemda tanpa kolom valid awal no-data/manual. Sertipikasi aset lahan manual Pemda (dasar Excel valid boleh awal); sebaran aset hanya detail tersedia. Layanan Kantah, E-Sertipikat, ZNT detail/dominan, kecamatan/desa tanpa sumber tetap no-data. NIB/kredit/RDTR/TORA/data umum yang tersedia berasal Excel awal dengan lineage, bukan nilai kamus/dummy.
