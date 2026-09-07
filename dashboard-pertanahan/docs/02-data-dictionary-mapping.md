# Mapping lengkap — bukti Tahap 0 dan keputusan Fase 1A

2026-09-06. Fakta diekstrak langsung ZIP/XML kedua XLSX di data/source/ menggunakan 00-read-workbooks.ps1. Bukti seluruh sel, formula/shared index, cache, style, header dan merge: 00-workbook-evidence.json. URL sumber di tabel adalah teks kamus, tidak diakses atau dianggap endpoint tersedia.

## Keputusan target Fase 1A (2026-09-06)

Bukti sel, nama, formula kamus asli, jumlah baris/kolom dan hasil hitung audit tetap fakta Tahap 0; tidak dibaca ulang sebagai test aplikasi. Bagian keputusan mapping diperbarui sesuai arahan pengguna. Workbook tetap 58 entri fisik/57 kode sumber unik; katalog target menjadi 58 kode kanonis (57 indikator + fitur MAP.1). Baris peta r62 mempertahankan source_code XIX.1; layanan r15 tetap XIX.1. Generator docs/00-build-mapping.ps1 adalah artefak historis, jangan dijalankan untuk menimpa keputusan ini.

Target Laravel 12 api/, MySQL/MariaDB, identitas Laravel/Sanctum tunggal. as_of_date adalah tanggal snapshot, tahun/bulan turunan filter; tanggal awal belum diberikan. Tanggal/cakupan sumber per indikator tetap lineage. Tanggal valid tanpa nilai -> no-data, bukan latest otomatis. Hanya Excel Jawa Barat sumber nilai operasional awal; kamus metadata.

PBB/NOP Excel awal lalu manual Pemda; PKKPR/indikator Pemda tanpa kolom valid awal no-data dan input manual. Sertipikasi aset lahan Pemda manual; dasar Excel valid boleh awal, persen oleh service. BPHTB Excel awal; API eksternal ditunda kontrak. Sebaran detail hanya jika sumber valid; Kantah/E-Sertipikat/ZNT detail/dominan tanpa sumber no-data. Formula turunan bukan manual bebas. Raw value/formula/result/row/column/checksum disimpan, termasuk quality flag 10 selisih luas ±1 ha, 2 teks GAP terbalik, 8 denominator RDTR nol; dash/blank berbeda dari nol.

## Cakupan dan aturan baca

- Kamus: sheet Kamus Data A1:L63 dan Legenda A1:D43. Header r4; **58 entri fisik r5–r62, 57 kode unik**. No 38 berulang r42/r43; XIX.1 berulang r15/r62. Peta r62 tidak punya Nama Data/Tipe/Satuan. Identitas audit memakai baris + kode.
- Jawa Barat: satu sheet JAWA BARAT (4 Agst), A1:BB987, 54 kolom. Header r2–r5 dan catatan r1. Hanya r6–r30 memiliki data wilayah (25 unik), tidak ada sel bernilai/formula setelah r30. NO tidak berurutan dan bukan identitas wilayah.
- 292 sel formula termasuk shared followers; tidak ada cached error tipe e. Cache belum tentu segar/rumus benar; formula tidak dieksekusi. Shared follower teks kosong mengacu master sharedIndex, bukan manual.
- Usulan persen canonical 0–100; Rp decimal disimpan rupiah, miliar/triliun hanya skala tampilan. Blank/dash null, bukan nol. Denominator nol/null -> no-data. Tidak ada imputasi.
- Waktu sumber campur: APL 13/7/2026, aset Maret 2026, HT 2025, sertipikat 2000–2026, GTRA 2018–2025/APBN 2026, tahun AD/AG per wilayah. Nama sheet 4 Agst bukan periode lengkap; periode laporan belum ditetapkan.

## Seluruh entri kamus → sumber dan owner

Exact match berarti makna/header/satuan cocok pada inspeksi, bukan approval kualitas. Klasifikasi terpisah dari owner: PBB/NOP/aset dapat bersumber Excel awal lalu manual. Semua level asli Kab/Kota; rincian bawahnya perlu sumber baru.

| Baris / kode | Nama Data | Tipe / satuan | Formula kamus asli | Sumber / asal / level asli | Klasifikasi | Kolom Excel | Keputusan mapping dan owner |
|---|---|---|---|---|---|---|---|
| r5 / I.1 | Total Luasan APL | Numerik / Ha | Input langsung (agregasi) | aplikasi.atrbpn.go.id/dashboard/dataelektronik/KotaLengkap / Sistem ATR/BPN / Kab/Kota | Exact match | C | ATR/BPN/Kantah; Excel bila cocok; Ha; agregasi hanya wilayah tersedia, bukan total provinsi lengkap. |
| r6 / I.2 | % Luasan APL Bersertifikat | Persentase / % | Jumlah luasan bersertifikat / Total APL | aplikasi.atrbpn.go.id/dashboard/statistik/BukuTanahNew/Jumlah / Sistem ATR/BPN / Kab/Kota | Transformasi/formula | D,C; F pembanding | ATR/BPN/Kantah; Excel bila cocok; 100*D/C; F6=0.51 dibulatkan, bukan presisi penuh; C=0/null -> no-data. |
| r7 / I.3 | % Luasan APL Belum Bersertifikat | Persentase / % | 100% - I.2 | Turunan dari I.1 dan I.2 / Sistem ATR/BPN / Kab/Kota | Transformasi/formula | C,D,E; G pembanding | ATR/BPN/Kantah; Excel bila cocok; 100-I.2; cek E=C-D dan G; jangan menjumlah persen. |
| r8 / II.1 | Total Luasan APL Terpetakan | Numerik / Ha | Total APL - KW456 | Turunan dari I.1 & KW456 / Sistem ATR/BPN / Kab/Kota | Transformasi/formula | C,BB | ATR/BPN/Kantah; Excel bila cocok; C-BB ha; validasi KW456 subset APL dan BB<=C, bukan C-BA. |
| r9 / II.2 | Total Luasan APL Belum Terpetakan | Numerik / Ha | Sum Nilai Luas KW456 | aplikasi.atrbpn.go.id/dashboard/dataelektronik/KW/KW / Sistem ATR/BPN / Kab/Kota | Exact match | BB | ATR/BPN/Kantah; Excel bila cocok; Ha; cache formula m2/10000; BA jumlah bidang bukan luas. |
| r10 / III.1 | GTRA - Status Pembentukan | Kategorikal / — | Ada/Tidak ada | Data Inventarisasi Ditjen Pentag / Sistem ATR/BPN / Kab/Kota | Exact match | AK | ATR/BPN/Kantah; Excel bila cocok; Normalisasi Ada/Tidak Ada; pembentukan 2018-2025 metadata. |
| r11 / III.2 | GTRA - Dukungan APBN (2026) | Kategorikal / — | Ada/Tidak ada | Data Inventarisasi Ditjen Pentag / Sistem ATR/BPN / Kab/Kota | Exact match | AL | ATR/BPN/Kantah; Excel bila cocok; Header APBN 2026; GTRA aktif dipadankan dukungan APBN berdasarkan header, cek tahun sumber. |
| r12 / III.3 | FPR - Status Pembentukan | Kategorikal / — | Ada/Tidak ada | Informasi Pemda/Kantah / PEMDA / Kab/Kota | Input manual Pemda | — | Pemda; FPR berbeda GTRA AK/AL; status belum tersedia. |
| r13 / III.4 | FPR - Tahun Pembentukan | Tahun / YYYY | Input langsung | Informasi Pemda/Kantah / PEMDA / Kab/Kota | Input manual Pemda | — | Pemda; Tahun FPR tidak ada; jangan pakai AD/AG. |
| r14 / IV.1 | % E-Sertifikat | Persentase / % | Data STEL / Data BT x 100% | Data STEL:  https://aplikasi.atrbpn.go.id/dashboard/dataelektronik/RekapSertipikatEl<br>Data BT:  https://aplikasi.atrbpn.go.id/dashboard/dataelektronik/RekapSertipikatEl / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Butuh STEL dan BT; F/G serta AS:AX bukan sertipikat elektronik. |
| r15 / XIX.1 | Kuasa( PPAT/PPATS) | Persentase / % | % Jumlah Berkas Kuasa (PPAT/PPATS)/ Jumlah Berkas Keseluruhan | Persentase berkas: Kantor Pertanahan / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Kuasa PPAT/PPATS / total berkas; bukan NIK; kode ganda dengan peta r62. |
| r16 / XIX.2 | Kuasa PPAT/PPATS (jumlah berkas) | Numerik / Berkas | Jumlah Berkas Kuasa PPAT/PPATS | Jumlah berkas kuasa: Kantor Pertanahan / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Jumlah berkas kuasa; definisikan cakupan denominator. |
| r17 / XIX.3 | % Non Kuasa (Pemohon Langsung) | Persentase / % | % Jumlah Berkas Non Kuasa/ Jumlah Berkas Keseluruhan | Persentase berkas: Kantor Pertanahan / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Nonkuasa / total berkas; bukan otomatis 100-kuasa jika ada kategori lain. |
| r18 / XIX.4 | Non Kuasa - Pemohon Langsung (jumlah berkas) | Numerik / Berkas | Jumlah Berkas Non Kuasa | Jumlah berkas kuasa: Kantor Pertanahan / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Jumlah nonkuasa; sumber J18 menyebut kuasa, konflik teks. |
| r19 / V.1 | Total PAD (PBB+BPHTB) | Numerik / Miliar Rp | V.2 +V.3 | Turunan dari V.2 & V.3 / Campuran / Kab/Kota | Transformasi/formula | H,J,L | Sistem menghitung; sumber Pemda/ATR-BPN; H+J Rp, /1e9 tampilan miliar; L cache. Ini subtotal PBB+BPHTB bukan seluruh PAD. |
| r20 / V.2 | Total PBB | Numerik / Miliar Rp | Input langsung | Data dari Pemda / PEMDA / Kab/Kota | Transformasi/formula | H | Pemda; Rp /1e9 tampilan; Excel awal lalu manual Pemda. |
| r21 / V.3 | Total BPHTB | Numerik / Miliar Rp | Input langsung | aplikasi.atrbpn.go.id/dashboard/statistik/BPHTB / Sistem ATR/BPN / Kab/Kota | Transformasi/formula | J | ATR/BPN/Kantah; Excel bila cocok; Keputusan: Rp /1e9 presentasi, Excel satu-satunya nilai awal; owner kamus ATR/BPN metadata, integrasi API ditunda kontrak akses. |
| r22 / VI.1 | Total NIB (bidang) | Numerik / Bidang | Input langsung | aplikasi.atrbpn.go.id/dashboard/dataelektronik/DataLengkap/DataLengkap / Sistem ATR/BPN / Kab/Kota | Exact match | N | ATR/BPN/Kantah; Excel bila cocok; Jumlah bidang NIB, bukan orang/pemilik. |
| r23 / VI.2 | Total NOP (bidang) | Numerik / Bidang | Input langsung | Data dari Pemda / PEMDA / Kab/Kota | Exact match | O | Pemda; Jumlah NOP; Excel awal lalu manual Pemda. |
| r24 / VI.3 | GAP NIB-NOP (bidang) | Numerik / Bidang | VI.1 - VI.2 | Turunan dari VI.1 & VI.2 / Campuran / Kab/Kota | Transformasi/formula | N,O; P/M pembanding | Sistem menghitung; sumber Pemda/ATR-BPN; Keputusan: GAP=N-O signed; tanda menentukan NIB > NOP / NIB = NOP / NIB < NOP. P6/P7 raw + quality flag; tampilan hitung kanonis. M koneksi tetap evidence, bukan klaim integrasi teknis. |
| r25 / VII.1 | % Cakupan Luas ZNT | Persentase / % | Input langsung | sipenta.atrbpn.go.id/dashboard/zona/series / Sistem ATR/BPN / Kab/Kota | Exact match | AB | ATR/BPN/Kantah; Excel bila cocok; Persen teks 0-100, AB6=97.68; jangan dikali 100 lagi. |
| r26 / VII.2 | % Luas ZNT Detail | Persentase / % | Input langsung | Ditjen Pengadaan Tanah / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | —; AC bukan match | ATR/BPN/Kantah; Excel bila cocok; AC luas belum ZNT ha bukan persen ZNT detail. |
| r27 / VII.3 | Nilai Bidang Tanah (paling dominan) | Rentang nilai / Rp | Input langsung (kelas nilai) | sipenta.atrbpn.go.id/dashboard/nbt / Sistem ATR/BPN / Kab/Kota | Tidak tersedia/ambigu | AE kandidat ditolak | ATR/BPN/Kantah; Excel bila cocok; Rata-rata ZNT bukan dominan/rentang nilai bidang; butuh distribusi kelas nilai. |
| r28 / VIII.1 | Total Nilai Kredit | Numerik / Triliun Rp | Input langsung | aplikasi.atrbpn.go.id/dashboard/dataelektronik/RekapHT / Sistem ATR/BPN / Kab/Kota | Transformasi/formula | Q | ATR/BPN/Kantah; Excel bila cocok; Rp /1e12 triliun; cakupan HT versus seluruh kredit perlu metadata owner. |
| r29 / VIII.2 | % Sertipikat di HT-kan | Persentase / % | % Jumlah Sertipikat HT/Total Sertipikat | Data Total Sertipikat: aplikasi.atrbpn.go.id/dashboard/statistik/BukuTanahNew/Jumlah<br>Data Sertipikat HT: aplikasi.atrbpn.go.id/dashboard/dataelektronik/RekapHT / Sistem ATR/BPN / Kab/Kota | Transformasi/formula | R,S,T | ATR/BPN/Kantah; Excel bila cocok; 100*R/S, T cache; pembilang 2025 vs penyebut 2000-2026 wajib ditandai beda waktu. |
| r30 / IX.1 | Jumlah RDTR Terbit Perkada | Numerik / Perkada | Input langsung | tataruang.atrbpn.go.id/protaru/rdtr/status/ / Sistem ATR/BPN / Kab/Kota | Exact match (keputusan target 1A) | AO | ATR/BPN/Kantah; Excel bila cocok; Keputusan: AO input dasar jumlah RDTR total/terbit; simpan pertanyaan AO1 sebagai metadata/QC, bukan alasan menebak ulang formula. |
| r31 / IX.2 | % RDTR Terintegrasi OSS | Persentase / % | Input langsung | tataruang.atrbpn.go.id/protaru/rdtr/status/ / Sistem ATR/BPN / Kab/Kota | Transformasi/formula (keputusan 1A) | AP,AO; AQ evidence | ATR/BPN/Kantah; Excel bila cocok; Keputusan: 100*AP/AO jika dasar valid dan AO>0; AO nol/kosong -> NULL/no-data. AQ tetap raw evidence/QC sampai definisi tervalidasi. |
| r32 / IX.3 | % RDTR Belum Terintegrasi OSS | Persentase / % | 100% - IX.2 | tataruang.atrbpn.go.id/protaru/rdtr/status/ / Sistem ATR/BPN / Kab/Kota | Transformasi/formula (keputusan 1A) | AO,AP; AR evidence | ATR/BPN/Kantah; Excel bila cocok; Keputusan: 100-IX.2 hanya denominator valid; AO nol/kosong -> NULL/no-data, bukan 100%. AR tetap evidence/QC sampai definisi tervalidasi. |
| r33 / X.1 | Total Persetujuan KKPR | Numerik / Dokumen | Input langsung | Pemerintah Daerah / PEMDA / Kab/Kota | Input manual Pemda | — | Pemda; Dokumen persetujuan KKPR tidak ada kolom sumber. |
| r34 / X.2 | Nilai Potensi Investasi | Numerik / Triliun Rp | Input langsung | Pemerintah Daerah / PEMDA / Kab/Kota | Input manual Pemda | — | Pemda; Potensi investasi bukan kredit HT Q; simpan Rp tampil triliun. |
| r35 / XI.1 | Aset Belum Sertipikat (%) | Persentase / % | Input langsung | Pemerintah Daerah / PEMDA / Kab/Kota | Transformasi/formula | U,X,AA | Pemda; Keputusan: input dasar manual Pemda (Excel valid boleh awal); 100*X/(U+X) dihitung service, tidak input persen bebas. AA raw evidence. |
| r36 / XI.2 | Aset Belum Sertipikat (Luas m²) | Numerik / m² | Input langsung | Pemerintah Daerah / PEMDA / Kab/Kota | Exact match | Y | Pemda; m2; Excel awal lalu manual Pemda. |
| r37 / XI.3 | Aset Belum Sertipikat (Nilai Rp) | Numerik / Miliar Rp | Input langsung | Pemerintah Daerah / PEMDA / Kab/Kota | Transformasi/formula | Z | Pemda; Rp /1e9 miliar; Excel awal lalu manual Pemda. |
| r38 / XII.1 | Aset Tersertipikat (jumlah) | Numerik / Sertipikat | Input langsung | KKP (Hak Pakai) / Sistem ATR/BPN / Kab/Kota | Tidak tersedia/ambigu | U kandidat | ATR/BPN/Kantah; Excel bila cocok; Jumlah bidang sudah sertipikat belum membuktikan jumlah sertipikat Hak Pakai. |
| r39 / XII.2 | Sebaran Aset - Kab/Kota (%) | Persentase / % | Input langsung | KKP (Hak Pakai) / Sistem ATR/BPN / Kab/Kota | Tidak tersedia/ambigu | U,B parsial | ATR/BPN/Kantah; Excel bila cocok; Tidak ada denominator/distribusi Hak Pakai lengkap; 25 wilayah bukan seluruh provinsi. |
| r40 / XII.3 | Sebaran Aset - Kecamatan (%) | Persentase / % | Input langsung | KKP (Hak Pakai) / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Kecamatan/geometri/denominator aset tidak tersedia; level input Kab/Kota perlu klarifikasi. |
| r41 / XII.4 | Sebaran Aset - Desa/Kelurahan (%) | Persentase / % | Input langsung | KKP (Hak Pakai) / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Desa/kelurahan/geometri/denominator tidak tersedia. |
| r42 / XIII | Jumlah Berkas | Numerik / Berkas | Input langsung | KKP (Jumlah Berkas Layanan Prioritas) / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Total berkas tujuh layanan dari Kantah; Q/R bukan berkas layanan. |
| r43 / XIII.1 | Pengecekan  % | Persentase / % | jumlah berkas pengecekan/total berkas layanan prioritas x 100% | KKP (Jumlah Berkas Layanan Prioritas) / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Berkas pengecekan / XIII *100; nilai turunan sistem. |
| r44 / XIII.2 | SKPT % | Persentase / % | jumlah berkas SKPT/total berkas layanan prioritas x 100% | KKP (Jumlah Berkas Layanan Prioritas) / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Berkas SKPT / XIII *100. |
| r45 / XIII.3 | HT-EL (%) | Persentase / % | jumlah berkas HTEL/total berkas layanan prioritas x 100% | KKP (Jumlah Berkas Layanan Prioritas) / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Berkas HT-EL / XIII *100; tidak memakai R/S. |
| r46 / XIII.4 | Roya (%) | Persentase / % | jumlah berkas pengecekan/total berkas layanan prioritas x 100% | KKP (Jumlah Berkas Layanan Prioritas) / Sistem ATR/BPN / Kab/Kota | Tidak tersedia/ambigu | — | ATR/BPN/Kantah; Excel bila cocok; Nama Roya tetapi formula kamus numerator pengecekan; calon Roya perlu koreksi disetujui; data Kantah belum ada. |
| r47 / XIII.5 | Peralihan (%) | Persentase / % | jumlah berkas peralihan/total berkas layanan prioritas x 100% | KKP (Jumlah Berkas Layanan Prioritas) / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Berkas peralihan / XIII *100. |
| r48 / XIII.6 | Pendaftaran SK (%) | Persentase / % | jumlah berkas pendaftaran SK/total berkas layanan prioritas x 100% | KKP (Jumlah Berkas Layanan Prioritas) / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Berkas pendaftaran SK / XIII *100. |
| r49 / XIII.7 | Perubahan Hak (%) | Persentase / % | jumlah berkas perubahan hak/total berkas layanan prioritas x 100% | KKP (Jumlah Berkas Layanan Prioritas) / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Berkas perubahan hak / XIII *100. |
| r50 / XV.1 | Pengecekan (durasi) | Numerik / Hari | Input langsung | https://aplikasi.atrbpn.go.id/dashboard/dataelektronik/BusinessReady  / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Durasi pengecekan hari; definisikan hari kerja/kalender dan statistik. |
| r51 / XV.2 | SKPT (durasi) | Numerik / Hari | Input langsung | https://aplikasi.atrbpn.go.id/dashboard/dataelektronik/BusinessReady  / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Durasi SKPT hari, bukan jumlah berkas. |
| r52 / XV.3 | HT-EL (durasi) | Numerik / Hari | Input langsung | https://aplikasi.atrbpn.go.id/dashboard/dataelektronik/BusinessReady  / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Durasi HT-EL hari, bukan nilai HT. |
| r53 / XV.4 | Roya (durasi) | Numerik / Hari | Input langsung | https://aplikasi.atrbpn.go.id/dashboard/dataelektronik/BusinessReady  / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Durasi Roya hari. |
| r54 / XV.5 | Peralihan (durasi) | Numerik / Hari | Input langsung | https://aplikasi.atrbpn.go.id/dashboard/dataelektronik/BusinessReady  / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Durasi peralihan hari. |
| r55 / XV.6 | Pendaftaran SK (durasi) | Numerik / Hari | Input langsung | https://aplikasi.atrbpn.go.id/dashboard/dataelektronik/BusinessReady  / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Durasi pendaftaran SK hari. |
| r56 / XV.7 | Perubahan Hak (durasi) | Numerik / Hari | Input langsung | https://aplikasi.atrbpn.go.id/dashboard/dataelektronik/BusinessReady  / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Durasi perubahan hak hari. |
| r57 / XVI.1 | Waktu Tunggu Pengukuran | Numerik / Hari | Input langsung | https://aplikasi.atrbpn.go.id/dashboard/statistik/Pengukuran/Layananpengukuranterj<br>adwal / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Waktu tunggu pengukuran hari berbeda durasi proses. |
| r58 / XVI.2 | Durasi Pengukuran | Numerik / Hari | Input langsung | https://aplikasi.atrbpn.go.id/dashboard/statistik/Pengukuran/Layananpengukuranterj<br>adwal / Sistem ATR/BPN / Kab/Kota | KKP/Kantah belum tersedia | — | ATR/BPN/Kantah; Excel bila cocok; Durasi pengukuran hari; butuh definisi statistik. |
| r59 / XVII.1 | Status Penerbitan SK KP2B/LP2B | Kategorikal / — | Status sudah/belum | Pemerintah Daerah / PEMDA / Kab/Kota | Input manual Pemda | AN tidak cukup | Pemda; AN status RTRW tidak membuktikan SK terbit; input dan bukti dokumen terpisah. |
| r60 / XVII.2 | Integrasi ke Dalam RTRW | Kategorikal / — | Integrasi Sudah/belum | Pemerintah Daerah / PEMDA / Kab/Kota | Tidak tersedia/ambigu | AN kandidat | Pemda; Header 87% KP2B/LP2B dan Sudah/Belum; calon integrasi RTRW, bedakan status SK; pembaruan manual Pemda. |
| r61 / XVIII.1 | Status Belum Tersertipikat (Ha) | Numerik / Ha | Input langsung | Data Direktorat Jenderal Penataan Agraria / Sistem ATR/BPN / Kab/Kota | Exact match | AM | ATR/BPN/Kantah; Excel bila cocok; TORA belum sertipikat ha sesuai cakupan kamus; bukan total semua pelepasan hutan. |
| r62 / XIX.1 | (kosong; kebutuhan peta) | / |  | Bhumi ATR/BPN / Sistem ATR/BPN / Kab/Kota | Tidak tersedia/ambigu | — | ATR/BPN/Kantah; Excel bila cocok; Keputusan: canonical_code MAP.1, source_code XIX.1 r62; placeholder/link terkonfigurasi BHUMI, bukan indikator numerik/API/embed rekaan. |

## Seluruh 54 kolom Excel → tujuan atau alasan tidak dipakai

Header diwariskan hanya dari merge yang mencakup sel. Statistik r6–r30: isi = sel bernilai, kosong = tanpa nilai, dash bagian dari isi. Tipe XML n numeric; s shared string (bisa angka teks). Formula termasuk shared.

| Kolom | Header bertingkat r2–r5 | Unit/peran | Kode tujuan / disposition | Isi / kosong / dash | Tipe XML | Formula contoh |
|---|---|---|---|---|---|---|
| A | NO | ordinal | NO metadata, bukan PK | 25 / 0 / 0 | n | — |
| B | DAREAH | wilayah | Alias master; DAREAH mentah dipertahankan | 25 / 0 / 0 | s | — |
| C | TOTAL APL → Luas APL <br>(ha) → data 13/7/2026 | ha | I.1; denominator I.2/I.3; II.1 | 25 / 0 / 0 | n | — |
| D | TOTAL APL → Luas lahan bersertipikat <br>(ha) → data 13/7/2026 | ha | Pembilang I.2; bukan IV.1 | 25 / 0 / 0 | n | — |
| E | TOTAL APL → blm bersertipikat (ha) → data 13/7/2026 | ha | Cek C-D untuk I.3; pendukung tanpa kode mandiri | 25 / 0 / 0 | n | — |
| F | TOTAL APL → Luas lahan bersertipikat <br>(%) → data 13/7/2026 | rasio % | Cache I.2; bukan E-Sertipikat | 25 / 0 / 0 | n | — |
| G | TOTAL APL → Luas lahan belum bersertipikat <br>(%) → data 13/7/2026 | rasio % | Cache I.3 | 25 / 0 / 0 | n | — |
| H | TOTAL PENDAPATAN SEKTOR PERTANAHAN → PBB <br>(Rp) | Rp | V.2, V.1; manual Pemda setelah Excel awal | 25 / 0 / 0 | n | — |
| I | TOTAL PENDAPATAN SEKTOR PERTANAHAN → % PBB | rasio % | Tambahan %PBB H/L tanpa kode; staging | 25 / 0 / 0 | n | I6: H6/L6 (25 sel) |
| J | TOTAL PENDAPATAN SEKTOR PERTANAHAN → BPHTB <br>(Rp) | Rp | V.3, V.1; owner kamus ATR/BPN | 25 / 0 / 0 | n | — |
| K | TOTAL PENDAPATAN SEKTOR PERTANAHAN → % BPHTB | rasio % | Tambahan %BPHTB J/L tanpa kode; staging | 25 / 0 / 0 | n | K6: J6/L6 (25 sel) |
| L | TOTAL PENDAPATAN SEKTOR PERTANAHAN → JUMLAH PBB & BPHTB (Rp)<br> | Rp | V.1 cache H+J | 25 / 0 / 0 | n | L6: H6+J6 (25 sel) |
| M | STATUS Terkoneksi | status | Koneksi sumber evidence; status tampilan perbandingan dari tanda GAP, bukan M | 25 / 0 / 0 | s | — |
| N | NIB DAN NOP → NIB <br>(BIDANG) | bidang | VI.1, VI.3 | 25 / 0 / 0 | n | — |
| O | NIB DAN NOP → NOP | bidang | VI.2, VI.3; manual Pemda | 25 / 0 / 0 | n | — |
| P | NIB DAN NOP → KETERANGAN | teks | Keterangan raw tetap, quality flag P6/P7; tampilan dari tanda GAP | 25 / 0 / 0 | s | — |
| Q | TOTAL KREDIT → HT<br>(Rp) | Rp | VIII.1 tampil triliun | 25 / 0 / 0 | n | — |
| R | TOTAL KREDIT → TOTAL SERTIPIKAT YANG DI HT TAHUN 2025<br>(Bidang) | bidang/2025 | Pembilang VIII.2 bukan berkas XIII.3 | 25 / 0 / 0 | n | — |
| S | TOTAL KREDIT → TOTAL SERTIPIKAT TAHUN 2000 - 2026<br>(Bidang) | bidang/2000–2026 | Denominator VIII.2 beda waktu | 25 / 0 / 0 | n | — |
| T | TOTAL KREDIT → SERTIFIKAT (%) | rasio % | Cache VIII.2 | 25 / 0 / 0 | n | T6: R6/S6 (25 sel) |
| U | ASET PEMDA → Aset Pemda (Bidang) data s.d. Maret 2026 → Sudah Sertipikat → Bidang | bidang | XI.1 pendukung; XII.1 ambigu objek Hak Pakai | 25 / 0 / 0 | n | — |
| V | ASET PEMDA → Aset Pemda (Bidang) data s.d. Maret 2026 → Sudah Sertipikat → Luas (m2) | m2 | Tambahan luas aset sudah; tanpa kode mandiri, staging | 25 / 0 / 0 | n,s | — |
| W | ASET PEMDA → Aset Pemda (Bidang) data s.d. Maret 2026 → Sudah Sertipikat → Nilai (Rp) | Rp | Tambahan nilai aset sudah; tanpa kode, staging | 25 / 0 / 1 | n,s | — |
| X | ASET PEMDA → Aset Pemda (Bidang) data s.d. Maret 2026 → Belum Sertipikat → Bidang | bidang | XI.1 pembilang; tambahan jumlah belum | 25 / 0 / 0 | n | — |
| Y | ASET PEMDA → Aset Pemda (Bidang) data s.d. Maret 2026 → Belum Sertipikat → Luas (m2) | m2 | XI.2 | 25 / 0 / 1 | n,s | — |
| Z | ASET PEMDA → Aset Pemda (Bidang) data s.d. Maret 2026 → Belum Sertipikat → Nilai (Rp) | Rp | XI.3 tampil miliar | 25 / 0 / 2 | n,s | — |
| AA | % Belum Sertipikat | rasio % | XI.1 cache X/(U+X) | 25 / 0 / 0 | n | AA6: X6/(U6+X6) (25 sel) |
| AB | ZNT → Cakupan ZNT → % Cakupan | persen 0–100 | VII.1, jangan *100 lagi | 25 / 0 / 0 | n,s | — |
| AC | ZNT → Cakupan ZNT → Luas belum (ha) | ha | Tambahan luas belum ZNT; bukan VII.2 | 25 / 0 / 0 | n | — |
| AD | ZNT → Cakupan ZNT → Tahun | tahun | Metadata ZNT bukan periode global | 25 / 0 / 0 | n,s | — |
| AE | ZNT → Cakupan ZNT → Nilai Rata-Rata ZNT (Rp.) | Rp rata-rata | Tambahan rata-rata ZNT; bukan VII.3 dominan | 25 / 0 / 0 | n | — |
| AF | ZNT → Cakupan ZNT → PKS | status PKS | Tambahan ZNT tanpa kode, staging | 25 / 0 / 0 | s | — |
| AG | KAWASAN KUMUH & PERTANIAN → KT → Tahun | tahun | Tambahan kawasan kumuh/KT bukan FPR | 25 / 0 / 2 | n,s | — |
| AH | KAWASAN KUMUH & PERTANIAN → KT → Luas wilayah kumuh → (Ha) | ha | Tambahan kawasan kumuh tanpa kode, staging | 25 / 0 / 2 | n,s | — |
| AI | KAWASAN KUMUH & PERTANIAN → KT → Luas Usulan LP2B → (Ha) | ha | Tambahan usulan LP2B bukan status XVII | 25 / 0 / 0 | n | — |
| AJ | KAWASAN KUMUH & PERTANIAN → KT → Ada Lokasi KT 5 Tahun Terakhir | status | Tambahan lokasi KT 5 tahun bukan GTRA/FPR | 25 / 0 / 0 | s | — |
| AK | GTRA → Pembentukan GTRA<br>(2018-2025) | status/2018–2025 | III.1 | 25 / 0 / 0 | s | — |
| AL | GTRA → GTRA Aktif<br>(APBN 2026) | status/APBN 2026 | III.2 | 25 / 0 / 0 | s | — |
| AM | PELEPASAN KAWASAN HUTAN → LUAS TORA BELUM SERTIPIKAT (Ha) | ha | XVIII.1 TORA belum sertipikat | 25 / 0 / 0 | n | — |
| AN | RTRW → 87% KP2B/LP2B → Sudah/Belum | status | XVII.2 kandidat ambigu; tidak untuk XVII.1 sekaligus | 25 / 0 / 0 | s | — |
| AO | TOTAL RDTR → RDTR OSS → Jumlah Perkada RDTR | Perkada | IX.1 input dasar total/terbit diputuskan; AO1 raw metadata/QC | 25 / 0 / 0 | n | — |
| AP | TOTAL RDTR → RDTR OSS → Jumlah Terintegrasi OSS | jumlah RDTR OSS | Pembilang IX.2, input dasar terintegrasi OSS | 25 / 0 / 0 | n | — |
| AQ | TOTAL RDTR → RDTR OSS → SUDAH | rasio % | Evidence/QC IX.2; nilai kanonis dihitung AP/AO*100, AO nol/kosong NULL | 25 / 0 / 0 | n | AQ6: AP6/AO6 (17 sel) |
| AR | TOTAL RDTR → RDTR OSS → BELUM | rasio % | Evidence/QC IX.3; komplemen hasil kanonis hanya denominator valid | 25 / 0 / 0 | n | AR6: 100%-AQ6 (25 sel) |
| AS | KEPEMILIKAN LAHAN BERSERTIFIKAT → NIK | jumlah NIK | Tambahan kepemilikan bukan IV.1 atau XIX | 25 / 0 / 0 | n | — |
| AT | KEPEMILIKAN LAHAN BERSERTIFIKAT → % | persen 0–100 | Tambahan AS/(AS+AU)*100, staging | 25 / 0 / 0 | n | AT6: AS6/(AS6+AU6)*100 (25 sel) |
| AU | KEPEMILIKAN LAHAN BERSERTIFIKAT → NON NIK | jumlah non-NIK | Tambahan kepemilikan bukan nonkuasa XIX.4 | 25 / 0 / 0 | n | — |
| AV | KEPEMILIKAN LAHAN BERSERTIFIKAT → % | persen 0–100 | Tambahan AU/(AS+AU)*100, staging | 25 / 0 / 0 | n | AV6: AU6/(AU6+AS6)*100 (25 sel) |
| AW | KEPEMILIKAN LAHAN BERSERTIFIKAT → JUMLAH PENDUDUK<br>(UMUR >30) | orang umur >30 | Tambahan penduduk bukan jumlah sertipikat | 25 / 0 / 0 | n | — |
| AX | KEPEMILIKAN LAHAN BERSERTIFIKAT → JUMLAH SERTIPIKAT (NIK + TANPA NIK) | jumlah sertipikat | Tambahan AS+AU bukan STEL/BT otomatis | 25 / 0 / 0 | n | AX6: AS6+AU6 (25 sel) |
| AY | KEPEMILIKAN LAHAN BERSERTIFIKAT → % BELUM KEPEMILIKAN TANAH | rasio % | Tambahan (AW-AX)/AW; orang vs sertipikat ambigu | 25 / 0 / 0 | n | AY6: (AW6-AX6)/AW6 (25 sel) |
| AZ | MPP → STATUS | status MPP | Tambahan tanpa kode, bukan layanan prioritas | 25 / 0 / 0 | s | — |
| BA | KKN TEMATIK PERTANAHAN → JUMLAH KW 456 | jumlah KW456 | Pendukung tambahan; tidak dikurangi dari C ha | 25 / 0 / 0 | n | — |
| BB | KKN TEMATIK PERTANAHAN → LUAS KW456 (Ha) | ha | II.2, II.1; m2/10000 sumber | 25 / 0 / 0 | n | BB6: 85340577/10000 (25 sel) |

## Seluruh wilayah aktual

| Baris | NO sumber | Nama |
|---|---|---|
| 6 | 1 | Kabupaten Bandung |
| 7 | 2 | Kabupaten Bandung Barat |
| 8 | 3 | Kabupaten Bekasi |
| 9 | 6 | Kabupaten Ciamis |
| 10 | 7 | Kabupaten Cianjur |
| 11 | 8 | Kabupaten Cirebon |
| 12 | 9 | Kabupaten Garut |
| 13 | 10 | Kabupaten Indramayu |
| 14 | 11 | Kabupaten Karawang |
| 15 | 12 | Kabupaten Kuningan |
| 16 | 13 | Kabupaten Majalengka |
| 17 | 15 | Kabupaten Purwakarta |
| 18 | 16 | Kabupaten Subang |
| 19 | 17 | Kabupaten Sukabumi |
| 20 | 18 | Kabupaten Sumedang |
| 21 | 19 | Kabupaten Tasikmalaya |
| 22 | 20 | Kota Bandung |
| 23 | 21 | Kota Banjar |
| 24 | 22 | Kota Bekasi |
| 25 | 23 | Kota Bogor |
| 26 | 24 | Kota Cimahi |
| 27 | 25 | Kota Cirebon |
| 28 | 26 | Kota Depok |
| 29 | 27 | Kota Sukabumi |
| 30 | 28 | Kota Tasikmalaya |

Dibanding master lokal database/seeders/regions_jawa_barat.sql, Kabupaten Bogor dan Kabupaten Pangandaran tidak ada di workbook. Ini perbandingan artefak lokal, bukan verifikasi master nasional terkini. Tidak dibuat nilai wilayah absen. Kecamatan/desa/koordinat tidak tersedia.

## Hitung ulang sampel Bandung r6

| Perhitungan | Hasil ulang | Cache/kesimpulan |
|---|---|---|
| APL D+E | 125758 | C6=125758 |
| % sertipikat 100*D/C | 51.43768189697673 | F6=0.51 -> 51%, beda pembulatan |
| PAD H+J | 358392027945 | L6=358392027945 |
| GAP N-O | -91318 | P6=NIB > NOP bertentangan |
| % HT 100*R/S | 1.453419052070317 | T6 rasio 1.4534190520703174E-2 |
| % aset belum | 73.22274881516587 | AA6 rasio 0.73222748815165872 |
| APL terpetakan C-BB | 117223.9423 | Usulan, subset perlu validasi |

AB6 shared-string angka 97.68; AT6 numeric 62.901582954849054 formula *100 dan format angka; F6 numeric 0.51 format % (numFmtId 9). V7 teks 1.868.493 perlu normalisasi ribuan. Jangan terapkan satu pengali persen untuk seluruh kolom. Simpan raw/cache/formula/style dan versi transformasi; kosong/dash null, bukan nol.

## Pemeriksaan konsistensi seluruh 25 wilayah

Pemeriksaan aritmetika baca-saja 2026-09-06, bukan koreksi sumber. Semua nilai C/D/E, N/O dan AO/AQ/AR diperiksa. Ditemukan 10 selisih luas sebesar ±1 ha, 2 keterangan GAP terbalik dan 8 denominator RDTR nol. Penyebab selisih luas mungkin pembulatan (inferensi), belum disahkan owner. Tidak ada rasio F/G/T/AA/AQ/AR/AY di luar 0–1 pada cached values.

| Sel | Wilayah | Bukti nilai | Temuan |
|---|---|---|---|
| P6 | Kabupaten Bandung | NIB > NOP; N-O=-91318 | Teks terbalik dengan angka |
| P7 | Kabupaten Bandung Barat | NIB < NOP; N-O=54790 | Teks terbalik dengan angka |
| C7/D7/E7 | Kabupaten Bandung Barat | C-D-E=-1 ha | Quality flag; pertahankan sumber, turunan hitung konsisten |
| AO7/AQ7/AR7 | Kabupaten Bandung Barat | AO=0; AQ=0; AR=1 | No-data denominator, bukan bukti 100% belum |
| C8/D8/E8 | Kabupaten Bekasi | C-D-E=1 ha | Quality flag; pertahankan sumber, turunan hitung konsisten |
| C10/D10/E10 | Kabupaten Cianjur | C-D-E=-1 ha | Quality flag; pertahankan sumber, turunan hitung konsisten |
| AO10/AQ10/AR10 | Kabupaten Cianjur | AO=0; AQ=0; AR=1 | No-data denominator, bukan bukti 100% belum |
| AO12/AQ12/AR12 | Kabupaten Garut | AO=0; AQ=0; AR=1 | No-data denominator, bukan bukti 100% belum |
| AO15/AQ15/AR15 | Kabupaten Kuningan | AO=0; AQ=0; AR=1 | No-data denominator, bukan bukti 100% belum |
| C16/D16/E16 | Kabupaten Majalengka | C-D-E=-1 ha | Quality flag; pertahankan sumber, turunan hitung konsisten |
| C19/D19/E19 | Kabupaten Sukabumi | C-D-E=1 ha | Quality flag; pertahankan sumber, turunan hitung konsisten |
| C20/D20/E20 | Kabupaten Sumedang | C-D-E=1 ha | Quality flag; pertahankan sumber, turunan hitung konsisten |
| C23/D23/E23 | Kota Banjar | C-D-E=1 ha | Quality flag; pertahankan sumber, turunan hitung konsisten |
| AO23/AQ23/AR23 | Kota Banjar | AO=0; AQ=0; AR=1 | No-data denominator, bukan bukti 100% belum |
| AO24/AQ24/AR24 | Kota Bekasi | AO=0; AQ=0; AR=1 | No-data denominator, bukan bukti 100% belum |
| AO26/AQ26/AR26 | Kota Cimahi | AO=0; AQ=0; AR=1 | No-data denominator, bukan bukti 100% belum |
| C28/D28/E28 | Kota Depok | C-D-E=1 ha | Quality flag; pertahankan sumber, turunan hitung konsisten |
| AO28/AQ28/AR28 | Kota Depok | AO=0; AQ=0; AR=1 | No-data denominator, bukan bukti 100% belum |
| C29/D29/E29 | Kota Sukabumi | C-D-E=1 ha | Quality flag; pertahankan sumber, turunan hitung konsisten |
| C30/D30/E30 | Kota Tasikmalaya | C-D-E=-1 ha | Quality flag; pertahankan sumber, turunan hitung konsisten |

Semua 25×54=1.350 sel data memiliki isi cached/text; tidak ada sel benar-benar kosong di rentang itu. Ada delapan penanda dash yang semantik nilainya missing: AG13, AH13, Y14, Z14, AG16, AH16, W22, Z22. Statistik kosong 0 pada matriks tidak berarti tidak ada data missing. Seluruh r31–987 kosong nilai/formula.

