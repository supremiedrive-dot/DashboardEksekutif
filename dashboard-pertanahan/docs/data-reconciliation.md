# Rekonsiliasi — keputusan Fase 1A

2026-09-06. **Keputusan target pengguna, bukan hasil implementasi/runtime.** Prioritas arahan terbaru > kamus final > Excel Jawa Barat > kode/dummy. Dokumen ini mengunci keputusan 1A; bagian bukti Tahap 0 di bawah dipertahankan untuk lineage audit. Pertanyaan historis yang sudah dijawab tidak diminta ulang. Workbook/kode tidak diubah.

## Tujuh kelompok keputusan: status aktif

| Kelompok | Status keputusan utama | Keputusan dan alasan | Sisa pekerjaan berstatus terpisah |
|---|---|---|---|
| 1. Aplikasi, database, identitas | **Diputuskan** | Laravel 12/PHP kompatibel 8.2+ di api/ kanonis; MySQL/MariaDB development/production; Laravel/Sanctum identitas tunggal, UI same-origin session/cookie stateful + CSRF. Native referensi visual/migrasi, jangan hapus sebelum feature parity pengganti terverifikasi; auth native dipensiunkan sesudah migrasi. Tidak ada dua auth/DB permanen atau silent fallback | Hardening logout/revoke/rate limit/validation/session/cookie/role/scope pada 2A; implementasi belum dimulai. SQLite tetap artefak legacy sampai audit migrasi selesai |
| 2. Snapshot/periode | **Diputuskan** | as_of_date DATE identitas tanggal data; tahun/bulan atribut/turunan filter; banyak snapshot per wilayah/indikator. Pilihan tanggal valid tanpa nilai -> no-data, tidak latest otomatis. Waktu sumber mentah tidak diganti tahun/bulan | **Masih membutuhkan sumber:** nilai as_of_date pertama yang disahkan; nama sheet/metadata campur belum memberikan satu tanggal pasti |
| 3. Kode/hierarki kamus | **Diputuskan** | XIX.1 tetap Kelompok Pengguna Layanan; MAP.1 BHUMI, source_code XIX.1/r62/PETA GTRA disimpan. Kelompok Pengguna pada Tematik/Layanan; tujuh layanan; Umum tiga prioritas + kebutuhan lain | **Ditunda:** koreksi formula Roya XIII.4 dan label sumber XIX.4 belum diputuskan spesifik pengguna; jangan koreksi workbook/kamus diam-diam |
| 4. GAP/RDTR/status | **Diputuskan** | GAP=NIB-NOP, tanda menentukan NIB > NOP / NIB = NOP / NIB < NOP. RDTR dasar total/terbit AO dan terintegrasi AP; AP/AO*100 dan komplemen hanya denominator valid. AO nol/kosong -> NULL/no-data keduanya; AQ/AR evidence/QC | **Masih membutuhkan sumber:** validasi definisi SUDAH/BELUM dan metadata header; AN belum membedakan SK vs integrasi RTRW. Ini tidak membatalkan keputusan formula GAP/RDTR |
| 5. Data awal dan owner | **Diputuskan** | Hanya Excel Jawa Barat sebagai nilai operasional awal, kamus metadata. PBB/NOP awal Excel lalu manual Pemda; PKKPR/Pemda tanpa kolom valid manual/no-data; aset lahan manual Pemda, dasar Excel valid boleh awal. BPHTB awal Excel | **Ditunda:** integrasi API BPHTB/eksternal sampai kontrak akses; **Masih membutuhkan sumber:** objek Hak Pakai dan denominator/detail sebaran aset. Approval teknis dan kontrak per owner dirinci pada 3A, bukan diganti menjadi semua input bebas |
| 6. Wilayah dan sumber belum ada | **Diputuskan** | Master negara/provinsi/kabupaten-kota/kecamatan/desa-kelurahan sesuai kebutuhan, awal Jawa Barat, impor nilai hanya 25 wilayah, NO bukan PK; master tanpa nilai no-data | **Masih membutuhkan sumber:** authority/versi master resmi; E-Sertipikat, ZNT detail/dominan, layanan Kantah, sebaran kec/desa/geometri. MAP.1 placeholder/link terkonfigurasi tanpa API/token/embed rekaan |
| 7. Runtime/pelaksanaan | **Diputuskan** | Target Laravel 12, PHP 8.2+ kompatibel composer.lock, PDO MySQL, Composer dan Node/npm sesuai lock/package-lock bila ada; Fase 1A dokumentasi saja | **Ditunda ke 1B:** penyiapan/verifikasi runtime/dependency, detail versi Node, akses/schema MySQL. Fakta PHP 7.4.33/dependency belum tersedia belum diuji ulang; tidak instal sekarang |

## Kontrak nilai awal, pembaruan, formula dan no-data

Semua baris berikut adalah keputusan target. Exact match/ambiguity dan bukti 58 entri/54 kolom di docs/02-data-dictionary-mapping.md tetap dibaca bersama bagian keputusan Fase 1A di sana. Channel Excel tidak mengubah owner kamus menjadi API aktif. Mapping tidak menciptakan nilai baru.

| Kode/kelompok | Data awal operasional | Pembaruan/owner | Formula/service atau keputusan QC | Status sumber/integrasi |
|---|---|---|---|---|
| I.1–3 APL | C/D/E serta F/G sebagai raw pembanding | Excel ber-lineage; ATR/BPN menurut metadata | I.2=100*D/C; I.3=100-I.2; 10 delta C-D-E ±1 ha diberi quality flag, C/D/E asli tidak ditimpa. Luas turunan C-D dibedakan dari E sumber | Diputuskan; rounding presentasi tidak mengubah raw |
| II.1–2 terpetakan | C/BB; BA hanya jumlah | Excel awal | C-BB ha; BB ha bukan BA bidang; validasi subset/unit; hasil invalid diberi QC | Diputuskan; provenance cakupan sumber dipertahankan |
| III.1–2 GTRA | AK/AL | Excel awal | Status normalisasi, tahun sumber tetap | Diputuskan |
| III.3–4 FPR dan Pemda tanpa kolom | Tidak tersedia | Manual Pemda | Status/tahun valid, bukan AD/AG | Masih membutuhkan sumber; awal no-data |
| IV.1 E-Sertipikat | Tidak tersedia | Sumber STEL/BT belum ada | Tidak memakai F/G/NIK atau dummy | Masih membutuhkan sumber; no-data |
| V.1 PAD subtotal | H/J input; L raw cache | Domain/service | H+J; rupiah disimpan, miliar presentasi; nilai turunan tidak manual | Diputuskan |
| V.2 PBB | H | Awal Excel, pembaruan manual Pemda | Validasi rupiah, lineage dan approval | Diputuskan |
| V.3 BPHTB | J | Excel awal; owner kamus tetap metadata | Jangan anggap seed Pemda/Excel atau URL kamus kontrak API | Diputuskan awal; API eksternal ditunda sampai kontrak akses |
| VI.1 NIB / VI.2 NOP | N/O | NIB Excel awal; NOP Excel awal lalu manual Pemda | Jumlah dasar bertipe integer | Diputuskan |
| VI.3 GAP/status | N/O; P/M evidence | Domain/service, tidak editable | N-O signed; >0: NIB > NOP, =0: NIB = NOP, <0: NIB < NOP. Missing input -> no-data. P6/P7 raw + quality flag, tampilan kanonis | Diputuskan; status ini perbandingan jumlah, bukan klaim koneksi teknis M |
| VII.1 cakupan ZNT | AB | Excel awal | Persen 0–100 sesuai style/data; tidak *100 seragam | Diputuskan |
| VII.2/3 detail/dominan | Tidak tersedia; AC/AE bukan substitusi | Menunggu data valid | Rata-rata tidak menjadi dominan/range; tambahan AC:AF staging/metadata | Masih membutuhkan sumber; no-data |
| VIII.1/2 kredit | Q/R/S, T cache | Excel awal | Q rupiah, tampilan triliun; 100*R/S dengan metadata 2025 vs 2000–2026 | Diputuskan impor/lineage; beda cakupan waktu diberi informasi/QC |
| IX.1 RDTR total/terbit | AO | Excel awal sebagai input sumber | Integer >=0; label/header mentah tetap | Diputuskan dasar total/terbit; pertanyaan AO1 tetap lineage |
| IX.2/3 OSS | AP terintegrasi dan AO total; AQ/AR evidence | Domain/service | AP/AO*100; 100-hasil hanya valid. AO=0/kosong -> NULL/no-data, bukan 0% atau 100%. AP missing/invalid -> no-data + QC sesuai error, jangan coercion/clamp | Diputuskan formula; definisi AQ/AR masih membutuhkan sumber/validasi |
| X.1/2 PKKPR | Tidak tersedia | Manual Pemda | Input dokumen/nilai investasi, bukan Q kredit | Masih membutuhkan sumber; awal no-data |
| XI.1–3 sertipikasi aset | U/X/Y/Z valid dapat data awal Excel; AA raw cache | **Manual Pemda** untuk pembaruan dasar | Persen 100*X/(U+X) oleh service, tidak input persen bebas; luas/nilai dasar manual dengan unit tepat; dash no-data | Diputuskan owner/formula; Excel tidak menghapus kewajiban kanal manual |
| XII sebaran aset | Excel/KKP hanya jika detail dan objek valid tersedia | Sumber detail belum lengkap | U bidang tidak otomatis sertipikat Hak Pakai; denominator provinsi tak boleh menganggap 25=27; kec/desa no-data | Masih membutuhkan sumber, bukan rekaan lokasi |
| XIII/XIX layanan/pengguna, XV/XVI durasi | Tidak tersedia | Sumber/input Kantah | Persen dari input berkas; durasi definisi hari/statistik belum ada; Roya dan label nonkuasa tidak dikoreksi tanpa keputusan | Masih membutuhkan sumber; koreksi kamus spesifik ditunda; no-data |
| XVII SK/integrasi RTRW | AN evidence belum cukup membedakan dua status | Manual Pemda | Jangan isi XVII.1 dan XVII.2 dari satu status tanpa validasi | Masih membutuhkan sumber/definisi AN, awal status tak terdukung no-data |
| XVIII.1 TORA | AM | Excel awal | Ha TORA belum sertipikat, bukan count/seluruh hutan | Diputuskan |
| MAP.1 BHUMI | Tidak ada nilai numerik; source_code XIX.1 r62 | Placeholder/link terkonfigurasi | Lineage peta dipertahankan; tidak buat observation numerik | Diputuskan kode/hierarki; link memerlukan konfigurasi valid, API/embed ditunda |
| Semua kolom ekstra | 54 kolom termasuk A/B tetap raw/staging | Belum otomatis indikator/manual/menu baru | Tidak membuang evidence; tidak memetakan AS:AY ke E-Sertipikat/kuasa | Ditunda perluasan katalog sampai kebutuhan/definisi disahkan |

## QC dan tanggal yang dikunci

- Jangan ubah kedua workbook atau data/dashboard.sqlite. Simpan raw value, formula/shared master, result/cache, row, column, sheet, checksum file, mapping version dan hasil hitung service terpisah.

## Rekonsiliasi implementasi importer Fase 3B MVP (2026-09-06)

Workbook kanonis `Input Data Jawa Barat.xlsx`, sheet `JAWA BARAT (4 Agst)`, diproses dengan mapping version `jabar-2026-08-04-v1` dan snapshot `as_of_date=2026-08-04`. Reader menyimpan seluruh 54 kolom × 25 row = 1.350 cell staging, termasuk A sebagai metadata NO dan B sebagai nama wilayah mentah. NO tetap bukan identitas wilayah. Seluruh 292 formula dicatat bersama formula type/shared index, cached value, number format, sheet, row, column, source header/period, dan checksum file; formula tidak dieksekusi.

Promosi dibatasi pada sembilan input dasar dengan owner kanonis `atr_bpn`: C→I.1, N→VI.1, Q→VIII.1, AB→VII.1, AK→III.1, AL→III.2, AM→XVIII.1, AO→IX.1, dan BB→II.2. Hasil development adalah 225 observation/revision draft untuk 25 wilayah. Nilai decimal cached yang mempunyai artefak presisi biner dinormalisasi ke scale enam digit sesuai schema; nilai mentah/cache tetap disimpan tanpa koreksi workbook.

Kolom Pemda seperti H/PBB, J/BPHTB, O/NOP, Y/Z aset serta kandidat AN hanya di-stage dan diberi ownership mismatch; tidak dipromosikan melalui kanal Excel. Formula turunan F/G/L/T/AA/AQ/AR tidak dipromosikan. Kolom pendukung tanpa kode kanonis, U/XII.1 yang objek/denominatornya ambigu, MAP.1, deferred, definition pending, Kantah/manual, dan data kosong juga tidak dipromosikan. Dash/kosong berstatus no-data dan tidak menjadi nol; AO bernilai nol tetap input dasar sah, sementara AQ/AR tidak dibuat dan mendapat QC denominator.

QC development berjumlah 446: 10 `APL_AREA_DELTA`, 2 `NIB_NOP_TEXT_SIGN_MISMATCH`, 8 `RDTR_ZERO_DENOMINATOR`, 48 `SOURCE_PERIOD_UNCLEAR`, 31 `INDICATOR_UNMAPPED`, 125 `DERIVED_NOT_PROMOTED`, 197 `OWNERSHIP_MISMATCH`, dan 25 `MAPPING_NOT_APPROVED`. Tidak ada formula tanpa cache, region tidak cocok, indicator master hilang, atau nilai promotable invalid pada workbook aktual. Temuan tidak memperbaiki nilai sumber secara diam-diam.
- Sepuluh selisih C-D-E ±1 ha pada r7/r8/r10/r16/r19/r20/r23/r28/r29/r30 diberi quality flag; bukan izin memperbaiki angka Excel atau menghapus selisih dengan toleransi diam-diam.
- P6/P7 terbalik: flag quality, raw tetap. Delapan denominator RDTR nol r7/r10/r12/r15/r23/r24/r26/r28 menghasilkan kedua persen NULL. AQ/AR sumber tetap evidence/QC.
- Delapan dash AG13/AH13/Y14/Z14/AG16/AH16/W22/Z22 adalah missing, berbeda dari blank dan numeric zero. State no-data tidak sama nilai nol.
- as_of_date DATE identitas snapshot data; tahun/bulan diturunkan darinya. Tanggal/cakupan asli per indikator (APL Juli, aset Maret, HT 2025 dan lainnya) disimpan, bukan diganti tanggal impor. Nilai as_of_date pertama belum diberikan; proses berikut meminta tanggal sah sebelum impor, tanpa menebak atau menggeser filter valid ke latest.
- Hasil formula dihitung domain/service dari dasar yang valid dan versi rule; cache Excel bukan input turunan otoritatif. Rasio agregat berasal dari dasar sebanding; bukan penjumlahan persen atau penjumlahan snapshot stok antar tanggal.

## Cara membaca arsip audit berikut

Tabel berikut adalah bukti konflik dan hipotesis **pada Tahap 0** yang dipertahankan agar keputusan baru tidak menyembunyikan perbedaan sumber. Kolom hipotesis/pertanyaan historis bukan status aktif: MAP.1, identitas tunggal, as_of_date, tanda GAP, denominator RDTR, kanal manual dan hierarki sudah diputuskan pada bagian atas. Formula Roya, sumber nonkuasa, AN dan detail sebaran yang belum diputuskan tetap tercatat terpisah. Tidak ada pertanyaan persetujuan ulang untuk keputusan yang sudah diberikan.
## Bukti dan hipotesis historis Tahap 0 (bukan keputusan aktif)

| Kode / bukti | Indikator | Sumber menurut kamus | Arahan pengguna | Kolom Excel kandidat | Hipotesis Tahap 0 (digantikan keputusan 1A di atas) | Pertanyaan historis |
|---|---|---|---|---|---|---|
| Seluruh kamus r5–62 | Jumlah entri | No sampai 57, No 38 ganda | Konteks menyebut 57 baris | — | Fakta 58 entri fisik, 57 kode unik; petakan semua | Benahi penomoran pada versi kamus berikutnya, bukan sekarang |
| XIX.1 r15 vs r62 | Kuasa % dan PETA GTRA | Kantah vs BHUMI | Kode stabil + placeholder BHUMI | — | Identitas audit baris+kode; peta usulan navigation key, bukan angka | Setujui key BHUMI terpisah tanpa menghilangkan original_code |
| XIX.1–4, Legenda A17 | Kelompok pengguna | Sheet utama Tematik/Layanan; Legenda Umum | Konteks menaruh di Layanan | — | Ikuti konteks dan sheet utama | Setujui penempatan dan perbaikan Legenda kelak |
| XIII.1–7, Legenda A19/A36 | Scope layanan | Legenda MVP dua layanan | Pengguna meminta tujuh | — | Desain tujuh, status no-data; fase implementasi bertahap | Tidak memakai batas MVP lama sebagai pembatalan scope terbaru |
| III.1–4 | Manajemen Isu | Umum/GTRA dan FPR | Prioritas awal tiga kelompok, isu tetap ada | AK/AL; FPR tidak ada | Isu tetap section setelah tiga prioritas | Urutan visual sementara bisa ditinjau |
| PETA GTRA r62 | Peta | Label GTRA, sumber Bhumi | Penampil Peta BHUMI | Tidak ada geometri | Menu placeholder BHUMI; label asal dipertahankan metadata | Link/embed/layer, izin dan kontrak, bukan endpoint rekaan |
| I.2/I.3 | Persen APL | D/C dan 100-I.2 | Awal Excel tervalidasi | C:G | Recompute vs cache; F6 51% sementara D/C 51.4376819% | Presisi/rounding serta authoritative numerator; jangan silently overwrite cache |
| II.1/II.2 | Terpetakan | APL-KW456, luas KW456 | Ha dan belum/KW456 | C,BA,BB | C-BB, bukan C-BA; subset APL harus benar | KW456 seluruhnya dalam cakupan APL dan tahun sebanding? |
| IV.1 | E-Sertipikat | STEL/BT | Prioritas kartu E-Sertipikat | Tidak ada; F dan AS:AX ditolak | No-data; seed `kepemilikan_sertifikat_e` dari persen lahan sertipikat salah makna | Sumber STEL/BT dan denominator resmi |
| V.1 | Total PAD | PBB+BPHTB, miliar Rp | Nilai turunan | H,J,L rupiah | Subtotal PBB+BPHTB, bukan PAD seluruh sektor | Label akhir dan presisi rupiah |
| V.2/VI.2 | PBB/NOP | Pemda | Manual dengan Excel awal | H/O | Tidak ada konflik owner; bedakan channel Excel dan manual | Kebijakan review/override setelah impor awal |
| V.3 | BPHTB | Sistem ATR/BPN | Petakan dan catat beda sumber | J | ATR/BPN owner sementara, Excel awal; seed lama Pemda/Excel tidak authority | Siapa mengoreksi BPHTB dan sumber akhir yang disahkan? |
| VI.3 | GAP dan integrasi | NIB-NOP, campuran | GAP/status turunan bukan bebas | N,O,M,P | GAP bertanda; Bandung -91318 tetapi P6 NIB>NOP. M koneksi bukan hasil GAP | Aturan status integrasi/jumlah pasangan; jumlah sama tidak membuktikan terhubung |
| VII.1 | Cakupan ZNT | Persen langsung ATR/BPN | Cakupan awal Excel | AB,AC | AB teks 97.68 sudah persen; AC ha belum bukan denominator pasti | Definisi cakupan/denominator, toleransi terhadap luas APL |
| VII.2 | ZNT Detail % | Ditjen Pengadaan Tanah | Pakai indikator mapping valid | AC bukan match | No-data, jangan konversi AC menjadi persen detail | Sumber ZNT detail |
| VII.3 | Nilai dominan/rentang | NBT kelas nilai | Nilai ZNT bila valid | AE rata-rata | Kandidat ditolak; simpan tambahan staging | Distribusi kelas dominan/range diperlukan |
| AC:AF | Tambahan ZNT | Tidak seluruhnya punya kode | Luas belum/tahun/nilai bila valid | AC,AD,AE,AF | Metadata/extra pending, bukan pengganti VII.2/3; seed menaruh ZNT tata ruang keliru | Perlu indikator tambahan berversi atau cukup metadata? |
| VIII.1 | Total kredit | Triliun Rp, RekapHT | Excel | Q Rp | /1e12 tampilan; jangan label semua kredit jika hanya HT | Cakupan kredit lembaga/HT |
| VIII.2 | Sertipikat HT % | Jumlah HT/total sertipikat | Excel | R2025/S2000–2026/T | Formula cocok secara aritmetika, periode populasi berbeda | Boleh dipublikasikan dengan catatan cakupan atau sumber seperiode? |
| IX.1–3 | RDTR/OSS | Perkada dan persen | Excel | AO:AR | Header calon cocok, tetapi AO1/AQ1/AR1 berisi pertanyaan target/klasifikasi. Mapping ditahan | Definisi terbit/target dan denominator OSS; cache konstanta vs formula |
| X.1/X.2 | PKKPR/potensi investasi | Pemda | Manual | — | Tidak tersedia; Q bukan potensi investasi | Data/form/owner Pemda dan periode |
| XI.1 | Aset belum % | Input langsung Pemda | Manual aset; formula tidak bebas | U,X,AA formula | Usulan turunan dari jumlah bidang; berbeda cara input kamus | Setujui input komponen dan persen read-only atau attest persen langsung |
| XI.2/XI.3 | Luas/nilai aset belum | m2 / miliar Rp | Manual dengan data awal | Y m2, Z Rp | Y langsung; Z /1e9 tampilan, bukan konversi semua aset ke ha | Periode aset Maret 2026 harus dipertahankan |
| XII.1 | Aset tersertipikat | Sertipikat Hak Pakai KKP | KKP/Excel | U bidang | Bidang bukan otomatis jumlah sertipikat/Hak Pakai | Konfirmasi objek, jenis hak, multipel sertipikat |
| XII.2 | Sebaran kab/kota % | Input langsung KKP | Hierarki wilayah | B/U parsial | 25 wilayah, denominator belum lengkap; tidak buat persen total provinsi | Basis jumlah/luas/nilai dan universe aset |
| XII.3/XII.4 | Sebaran kec/desa | Nama kec/desa tetapi Level Input Kab/Kota | Dukung level bawah ketika tersedia | — | Schema siap; no-data tanpa lokasi rekaan | Granularitas input dan denominator |
| XIII/XIII.1–7 | Total/intensitas | Berkas KKP | Input Kantah | — | Jangan pakai HT Rp/sertipikat atau metrik validasi dummy | Total tujuh layanan, pembilang per layanan, periode |
| XIII.4 I46 | Roya % | Formula memakai berkas pengecekan | Roya terpisah | — | Formula konflik, usulan numerator Roya ditahan | Koreksi tertulis owner kamus |
| XIX.4 J18 | Nonkuasa jumlah | Teks sumber “jumlah berkas kuasa” | Kelompok pengguna | — | Gunakan kebutuhan nonkuasa, belum implementasi | Koreksi label sumber dan denominator keseluruhan |
| XV/XVI | Durasi/tunggu | Hari ATR/BPN | Kantah | — | Bukan count seperti seed lama; tidak SUM durasi | Hari kerja/kalender, mean/median, populasi, SLA dan bobot |
| XVII.1/XVII.2 | SK vs integrasi RTRW | Dua status Pemda terpisah | Integrasi sudah/belum | AN hanya satu | Tidak isi dua kode dari satu status tanpa bukti; XVII.1 manual, XVII.2 kandidat | Makna “87% KP2B/LP2B” dan apakah AN status SK/integrasi? |
| XVIII.1 | TORA belum sertipikat | Ha | Pelepasan kawasan hutan | AM | Cakupan TORA belum, bukan seluruh hutan dilepas; seed count tidak cocok | Label ringkas tetap mengungkap scope TORA |
| I/K/V/W/AG:AJ/AS:AZ/BA | Kolom ekstra | Tidak punya indikator persis | Semua 54 kolom diaudit | Lihat matriks 54 kolom | Simpan staging/provenance; tidak otomatis jadi menu atau input baru | Setujui katalog tambahan hanya jika diperlukan |
| AS:AY | Kepemilikan NIK | Bukan E-Sertipikat/pengguna layanan | Jangan mengarang mapping | AS:AY | AY mengurangi jumlah sertipikat dari orang >30: proksi ambigu | Jangan klaim persen warga tak bertanah tanpa definisi populasi |
| Semua nilai | Periode | Waktu berbeda antar indikator | Multi-periode, jangan menebak | Header C5/U3/R3/S3/AK4/AL4, AD/AG | Reporting period eksplisit + observed period sumber per nilai | Tanggal/periode pelaporan awal dan kebijakan data lintas waktu |
| Wilayah | Master vs data | Kab/Kota | Jangan ciptakan nilai absen | 25 nama B6:B30 vs master 27 | Kab. Bogor/Pangandaran no-data; NO bukan PK; alias+authority+versi | Master BPS/Kemendagri yang disahkan, tanpa meminta nilai fiktif |

## Konflik implementasi dan dokumentasi lama

| Bukti | Konflik | Keputusan Tahap 0 |
|---|---|---|
| `docs/progress.md` sebelum reset vs `db.php`, `login.php`, `dashboard.php` sekarang | Klaim MySQL terpadu/akun demo dihapus/empty state ada tidak cocok source aktif SQLite/demo | Status reset ditulis ulang; hasil tes historis tidak dibawa sebagai kelulusan saat ini |
| `docs/project-audit.md`, `implementation-plan.md` lama | Menyebut tidak ada Laravel/tetap native dan sumber tunggal | Laravel api/ dan dua workbook faktual; rencana baru menggunakan Laravel yang ada |
| Root PHP vs api/ Laravel | UI/session PDO tidak memanggil Sanctum | Audit dua alur; penyatuan hanya fase berikut setelah keputusan |
| `database/schema.sql` vs Laravel migrations/native DB | Tiga users/regions contracts berbeda | Tidak mengubah driver/migration sekarang; desain target terpisah |
| `api/.env.example`, config/database, phpunit.xml, composer scripts | Default/test/post-create SQLite, target MySQL | Tidak dieksekusi; butuh perubahan eksplisit setelah review |
| `services/DashboardDataService.php` vs `dashboard.php` | Service MySQL ada tetapi dashboard query SQLite langsung | Keberadaan service/test tidak membuktikan integrasi |
| `import-excel.php` vs `config.php` | valid_report_date dipanggil tetapi tidak ada definisi; db SQLite tidak cocok preview/import MySQL | Temuan statis, tidak menguji dengan menjalankan jalur tulis |
| `ExcelWorkbookImporter::readWorksheetRows/parseCell/importFile` | Shared follower formula teks kosong; batch baru tiap import; approved berpotensi diturunkan draft | Perlu provenance formula, idempotensi dan publication terpisah pada desain |
| `dashboard.php` | Badge “terverifikasi” tetap, ring total 100%, tidak ada empty state, default provinsi alfabetis | Tidak menganggap status/kartu bukti data valid; mempertahankan wadah visual saja |
| `dashboard.php` sidebar vs `sync-kkp.php` | Pemda melihat link sync, endpoint hanya admin | Konsistenkan setelah scope disetujui |
| `input-data.php` | Pemda bisa seluruh wilayah dan empat metrik generik | Scope wilayah+indikator dan form owner harus didesain; bukan mengubah pada audit |
| `db.php` seed vs Excel | Jawa Barat/Jatim/DKI demo, hanya empat wilayah Jabar | Dummy tidak dijadikan nilai Jawa Barat; nilai sebenarnya hanya Excel awal |
| `.htaccess/router.php` vs Laravel public | Workspace root web dapat mengekspos struktur Laravel non-public | Rekomendasi docroot api/public; status proteksi belum diuji HTTP |


