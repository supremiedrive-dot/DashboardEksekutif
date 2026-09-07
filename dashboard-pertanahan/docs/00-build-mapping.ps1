$ErrorActionPreference='Stop'
$e=Get-Content -Raw -Encoding UTF8 docs/00-workbook-evidence.json | ConvertFrom-Json
$wb=$e | Where-Object {$_.file -like 'Input*'}; $dict=$e | Where-Object {$_.file -like 'Kamus*'}
$dc=@{}; foreach($c in $dict.sheets[0].cells){$dc[$c.ref]=$c.value}; $xc=@{}; foreach($c in $wb.sheets[0].cells){$xc[$c.ref]=$c}
function esc($v){([string]$v -replace '\|','&#124;' -replace '\r?\n','<br>').Trim()}
$map=@{}
@'
I.1|Exact match|C|Ha; agregasi hanya wilayah tersedia, bukan total provinsi lengkap.
I.2|Transformasi/formula|D,C; F pembanding|100*D/C; F6=0.51 dibulatkan, bukan presisi penuh; C=0/null -> no-data.
I.3|Transformasi/formula|C,D,E; G pembanding|100-I.2; cek E=C-D dan G; jangan menjumlah persen.
II.1|Transformasi/formula|C,BB|C-BB ha; validasi KW456 subset APL dan BB<=C, bukan C-BA.
II.2|Exact match|BB|Ha; cache formula m2/10000; BA jumlah bidang bukan luas.
III.1|Exact match|AK|Normalisasi Ada/Tidak Ada; pembentukan 2018-2025 metadata.
III.2|Exact match|AL|Header APBN 2026; GTRA aktif dipadankan dukungan APBN berdasarkan header, cek tahun sumber.
III.3|Input manual Pemda|—|FPR berbeda GTRA AK/AL; status belum tersedia.
III.4|Input manual Pemda|—|Tahun FPR tidak ada; jangan pakai AD/AG.
IV.1|KKP/Kantah belum tersedia|—|Butuh STEL dan BT; F/G serta AS:AX bukan sertipikat elektronik.
V.1|Transformasi/formula|H,J,L|H+J Rp, /1e9 tampilan miliar; L cache. Ini subtotal PBB+BPHTB bukan seluruh PAD.
V.2|Transformasi/formula|H|Rp /1e9 tampilan; Excel awal lalu manual Pemda.
V.3|Transformasi/formula|J|Rp /1e9; owner kamus ATR/BPN, Excel awal; seed lama menyebut Pemda, perlu rekonsiliasi.
VI.1|Exact match|N|Jumlah bidang NIB, bukan orang/pemilik.
VI.2|Exact match|O|Jumlah NOP; Excel awal lalu manual Pemda.
VI.3|Transformasi/formula|N,O; P/M pembanding|N-O bertanda boleh negatif; M status koneksi bukan akibat kesamaan jumlah; aturan status integrasi belum tersedia.
VII.1|Exact match|AB|Persen teks 0-100, AB6=97.68; jangan dikali 100 lagi.
VII.2|KKP/Kantah belum tersedia|—; AC bukan match|AC luas belum ZNT ha bukan persen ZNT detail.
VII.3|Tidak tersedia/ambigu|AE kandidat ditolak|Rata-rata ZNT bukan dominan/rentang nilai bidang; butuh distribusi kelas nilai.
VIII.1|Transformasi/formula|Q|Rp /1e12 triliun; cakupan HT versus seluruh kredit perlu metadata owner.
VIII.2|Transformasi/formula|R,S,T|100*R/S, T cache; pembilang 2025 vs penyebut 2000-2026 wajib ditandai beda waktu.
IX.1|Tidak tersedia/ambigu|AO kandidat|Header Perkada cocok tetapi AO1 mempertanyakan target RDTR; konfirmasi sebelum publikasi.
IX.2|Tidak tersedia/ambigu|AP,AO,AQ kandidat|Calon 100*AP/AO; AQ cache/konstanta, pertanyaan AQ1 belum dijawab; nol denominator -> no-data.
IX.3|Tidak tersedia/ambigu|AR,AQ kandidat|Calon 100-IX.2; AR1 mempertanyakan klasifikasi; nol denominator bukan 100% belum.
X.1|Input manual Pemda|—|Dokumen persetujuan KKPR tidak ada kolom sumber.
X.2|Input manual Pemda|—|Potensi investasi bukan kredit HT Q; simpan Rp tampil triliun.
XI.1|Transformasi/formula|U,X,AA|100*X/(U+X) berbasis bidang; kamus input langsung/manual, usulan turunan terkunci perlu keputusan owner.
XI.2|Exact match|Y|m2; Excel awal lalu manual Pemda.
XI.3|Transformasi/formula|Z|Rp /1e9 miliar; Excel awal lalu manual Pemda.
XII.1|Tidak tersedia/ambigu|U kandidat|Jumlah bidang sudah sertipikat belum membuktikan jumlah sertipikat Hak Pakai.
XII.2|Tidak tersedia/ambigu|U,B parsial|Tidak ada denominator/distribusi Hak Pakai lengkap; 25 wilayah bukan seluruh provinsi.
XII.3|KKP/Kantah belum tersedia|—|Kecamatan/geometri/denominator aset tidak tersedia; level input Kab/Kota perlu klarifikasi.
XII.4|KKP/Kantah belum tersedia|—|Desa/kelurahan/geometri/denominator tidak tersedia.
XIII|KKP/Kantah belum tersedia|—|Total berkas tujuh layanan dari Kantah; Q/R bukan berkas layanan.
XIII.1|KKP/Kantah belum tersedia|—|Berkas pengecekan / XIII *100; nilai turunan sistem.
XIII.2|KKP/Kantah belum tersedia|—|Berkas SKPT / XIII *100.
XIII.3|KKP/Kantah belum tersedia|—|Berkas HT-EL / XIII *100; tidak memakai R/S.
XIII.4|Tidak tersedia/ambigu|—|Nama Roya tetapi formula kamus numerator pengecekan; calon Roya perlu koreksi disetujui; data Kantah belum ada.
XIII.5|KKP/Kantah belum tersedia|—|Berkas peralihan / XIII *100.
XIII.6|KKP/Kantah belum tersedia|—|Berkas pendaftaran SK / XIII *100.
XIII.7|KKP/Kantah belum tersedia|—|Berkas perubahan hak / XIII *100.
XV.1|KKP/Kantah belum tersedia|—|Durasi pengecekan hari; definisikan hari kerja/kalender dan statistik.
XV.2|KKP/Kantah belum tersedia|—|Durasi SKPT hari, bukan jumlah berkas.
XV.3|KKP/Kantah belum tersedia|—|Durasi HT-EL hari, bukan nilai HT.
XV.4|KKP/Kantah belum tersedia|—|Durasi Roya hari.
XV.5|KKP/Kantah belum tersedia|—|Durasi peralihan hari.
XV.6|KKP/Kantah belum tersedia|—|Durasi pendaftaran SK hari.
XV.7|KKP/Kantah belum tersedia|—|Durasi perubahan hak hari.
XVI.1|KKP/Kantah belum tersedia|—|Waktu tunggu pengukuran hari berbeda durasi proses.
XVI.2|KKP/Kantah belum tersedia|—|Durasi pengukuran hari; butuh definisi statistik.
XVII.1|Input manual Pemda|AN tidak cukup|AN status RTRW tidak membuktikan SK terbit; input dan bukti dokumen terpisah.
XVII.2|Tidak tersedia/ambigu|AN kandidat|Header 87% KP2B/LP2B dan Sudah/Belum; calon integrasi RTRW, bedakan status SK; pembaruan manual Pemda.
XVIII.1|Exact match|AM|TORA belum sertipikat ha sesuai cakupan kamus; bukan total semua pelepasan hutan.
XIX.1|KKP/Kantah belum tersedia|—|Kuasa PPAT/PPATS / total berkas; bukan NIK; kode ganda dengan peta r62.
XIX.2|KKP/Kantah belum tersedia|—|Jumlah berkas kuasa; definisikan cakupan denominator.
XIX.3|KKP/Kantah belum tersedia|—|Nonkuasa / total berkas; bukan otomatis 100-kuasa jika ada kategori lain.
XIX.4|KKP/Kantah belum tersedia|—|Jumlah nonkuasa; sumber J18 menyebut kuasa, konflik teks.
'@ -split "`n" | ForEach-Object {if($_.Trim()){$a=$_.Trim() -split '\|',4;$map[$a[0]]=$a[1..3]}}
$lines=[Collections.Generic.List[string]]::new()
$lines.Add('# Tahap 0 — Mapping lengkap kamus dan Excel Jawa Barat')
$lines.Add('')
$lines.Add('2026-09-06. Fakta diekstrak langsung ZIP/XML kedua XLSX di data/source/ menggunakan 00-read-workbooks.ps1. Bukti seluruh sel, formula/shared index, cache, style, header dan merge: 00-workbook-evidence.json. URL sumber di tabel adalah teks kamus, tidak diakses atau dianggap endpoint tersedia.')
$lines.Add('')
$lines.Add('## Cakupan dan aturan baca')
$lines.Add('')
$lines.Add('- Kamus: sheet Kamus Data A1:L63 dan Legenda A1:D43. Header r4; **58 entri fisik r5–r62, 57 kode unik**. No 38 berulang r42/r43; XIX.1 berulang r15/r62. Peta r62 tidak punya Nama Data/Tipe/Satuan. Identitas audit memakai baris + kode.')
$lines.Add('- Jawa Barat: satu sheet JAWA BARAT (4 Agst), A1:BB987, 54 kolom. Header r2–r5 dan catatan r1. Hanya r6–r30 memiliki data wilayah (25 unik), tidak ada sel bernilai/formula setelah r30. NO tidak berurutan dan bukan identitas wilayah.')
$lines.Add('- 292 sel formula termasuk shared followers; tidak ada cached error tipe e. Cache belum tentu segar/rumus benar; formula tidak dieksekusi. Shared follower teks kosong mengacu master sharedIndex, bukan manual.')
$lines.Add('- Usulan persen canonical 0–100; Rp decimal disimpan rupiah, miliar/triliun hanya skala tampilan. Blank/dash null, bukan nol. Denominator nol/null -> no-data. Tidak ada imputasi.')
$lines.Add('- Waktu sumber campur: APL 13/7/2026, aset Maret 2026, HT 2025, sertipikat 2000–2026, GTRA 2018–2025/APBN 2026, tahun AD/AG per wilayah. Nama sheet 4 Agst bukan periode lengkap; periode laporan belum ditetapkan.')
$lines.Add('')
$lines.Add('## Seluruh entri kamus → sumber dan owner')
$lines.Add('')
$lines.Add('Exact match berarti makna/header/satuan cocok pada inspeksi, bukan approval kualitas. Klasifikasi terpisah dari owner: PBB/NOP/aset dapat bersumber Excel awal lalu manual. Semua level asli Kab/Kota; rincian bawahnya perlu sumber baru.')
$lines.Add('')
$lines.Add('| Baris / kode | Nama Data | Tipe / satuan | Formula kamus asli | Sumber / asal / level asli | Klasifikasi | Kolom Excel | Keputusan mapping dan owner |')
$lines.Add('|---|---|---|---|---|---|---|---|')
$blue=[Collections.Generic.List[string]]::new()
$blue.Add('# Tahap 0 — Blueprint menu dan dashboard');$blue.Add('');$blue.Add('2026-09-06. Rekomendasi desain, belum implementasi. Seluruh entri fisik kamus dipertahankan; baris membedakan kode ganda.');$blue.Add('');$blue.Add('## Navigasi usulan');$blue.Add('');$blue.Add('1. Informasi Umum: Total APL → Lahan Terpetakan → Kepemilikan E-Sertipikat → Manajemen Isu Pertanahan & Ruang. E-Sertipikat tetap ada dengan no-data.');$blue.Add('2. Informasi Tematik: Keuangan Daerah → Iklim Investasi Daerah → Optimalisasi Aset Pemda → Layanan Pertanahan → Aspek Tata Ruang. Kelompok Pengguna tetap di Layanan sesuai sheet utama/konteks.');$blue.Add('3. Penampil Peta BHUMI: menu utama sementara untuk kebutuhan PETA GTRA. Placeholder “Integrasi BHUMI belum tersedia”; tanpa endpoint, token, iframe/geometri rekaan.');$blue.Add('4. Utilitas sesuai role/scope: Input Pemda, validasi dan impor admin, profil/logout. Sinkronisasi KKP memerlukan kontrak/izin sebelum diaktifkan.');$blue.Add('');$blue.Add('## Kategori Level 1 → Subtab → Kartu/Blok → Nama Data → visual');$blue.Add('');$blue.Add('| Baris/kode | Kategori Level 1 | Subtab | Kartu/Blok | Nama Data | Visual disarankan | Sumber awal |');$blue.Add('|---|---|---|---|---|---|---|')
for($r=5;$r -le 62;$r++){
 $code=$dc["B$r"];$m=$map[$code];if(!$m){throw "Missing $code"};$name=$dc["F$r"]
 if($r -eq 62){$m=@('Tidak tersedia/ambigu','—','Placeholder BHUMI, kode ganda r62; bukan indikator numerik. Owner ATR/BPN; kontrak belum ada.');$name='(kosong; kebutuhan peta)'}
 $owner=if($dc["K$r"] -eq 'PEMDA'){'Pemda; '}elseif($dc["K$r"] -eq 'Campuran'){'Sistem menghitung; sumber Pemda/ATR-BPN; '}else{'ATR/BPN/Kantah; Excel bila cocok; '}
 $vals=@("r$r / $code",$name,($dc["G$r"]+' / '+$dc["H$r"]),$dc["I$r"],($dc["J$r"]+' / '+$dc["K$r"]+' / '+$dc["L$r"]),$m[0],$m[1],($owner+$m[2])) | ForEach-Object {esc $_};$lines.Add('| '+($vals -join ' | ')+' |')
 $visual=if($dc["G$r"] -eq 'Persentase'){'KPI % dan bar wilayah; tampilkan denominator'}elseif($dc["G$r"] -eq 'Kategorikal'){'Badge dan tabel status; unknown terpisah'}elseif($dc["G$r"] -eq 'Tahun'){'Tahun dan tabel wilayah'}elseif($dc["G$r"] -eq 'Rentang nilai'){'Kelas/rentang min–max, bukan rata-rata'}elseif($dc["H$r"] -eq 'Hari'){'KPI durasi dan bar layanan; definisi statistik'}else{'KPI dan tabel/perbandingan wilayah'}
 if($code -eq 'VI.3'){$visual='GAP bertanda dan diverging bar; status integrasi terpisah'};if($code -like 'XII.*'){$visual='Tabel sebaran; peta hanya dengan geometri valid'};if($r -eq 62){$visual='Placeholder Penampil Peta BHUMI'}
 $vals=@("r$r / $code",$dc["C$r"],$dc["D$r"],$dc["E$r"],$name,$visual,$m[0])|ForEach-Object {esc $_};$blue.Add('| '+($vals -join ' | ')+' |')
}
$lines.Add('');$lines.Add('## Seluruh 54 kolom Excel → tujuan atau alasan tidak dipakai');$lines.Add('');$lines.Add('Header diwariskan hanya dari merge yang mencakup sel. Statistik r6–r30: isi = sel bernilai, kosong = tanpa nilai, dash bagian dari isi. Tipe XML n numeric; s shared string (bisa angka teks). Formula termasuk shared.');$lines.Add('');$lines.Add('| Kolom | Header bertingkat r2–r5 | Unit/peran | Kode tujuan / disposition | Isi / kosong / dash | Tipe XML | Formula contoh |');$lines.Add('|---|---|---|---|---|---|---|')
$specs=@'
A|ordinal|NO metadata, bukan PK
B|wilayah|Alias master; DAREAH mentah dipertahankan
C|ha|I.1; denominator I.2/I.3; II.1
D|ha|Pembilang I.2; bukan IV.1
E|ha|Cek C-D untuk I.3; pendukung tanpa kode mandiri
F|rasio %|Cache I.2; bukan E-Sertipikat
G|rasio %|Cache I.3
H|Rp|V.2, V.1; manual Pemda setelah Excel awal
I|rasio %|Tambahan %PBB H/L tanpa kode; staging
J|Rp|V.3, V.1; owner kamus ATR/BPN
K|rasio %|Tambahan %BPHTB J/L tanpa kode; staging
L|Rp|V.1 cache H+J
M|status|Koneksi NIB-NOP; bukan GAP; staging tunggu aturan
N|bidang|VI.1, VI.3
O|bidang|VI.2, VI.3; manual Pemda
P|teks|Keterangan NIB-NOP kontradiktif; staging
Q|Rp|VIII.1 tampil triliun
R|bidang/2025|Pembilang VIII.2 bukan berkas XIII.3
S|bidang/2000–2026|Denominator VIII.2 beda waktu
T|rasio %|Cache VIII.2
U|bidang|XI.1 pendukung; XII.1 ambigu objek Hak Pakai
V|m2|Tambahan luas aset sudah; tanpa kode mandiri, staging
W|Rp|Tambahan nilai aset sudah; tanpa kode, staging
X|bidang|XI.1 pembilang; tambahan jumlah belum
Y|m2|XI.2
Z|Rp|XI.3 tampil miliar
AA|rasio %|XI.1 cache X/(U+X)
AB|persen 0–100|VII.1, jangan *100 lagi
AC|ha|Tambahan luas belum ZNT; bukan VII.2
AD|tahun|Metadata ZNT bukan periode global
AE|Rp rata-rata|Tambahan rata-rata ZNT; bukan VII.3 dominan
AF|status PKS|Tambahan ZNT tanpa kode, staging
AG|tahun|Tambahan kawasan kumuh/KT bukan FPR
AH|ha|Tambahan kawasan kumuh tanpa kode, staging
AI|ha|Tambahan usulan LP2B bukan status XVII
AJ|status|Tambahan lokasi KT 5 tahun bukan GTRA/FPR
AK|status/2018–2025|III.1
AL|status/APBN 2026|III.2
AM|ha|XVIII.1 TORA belum sertipikat
AN|status|XVII.2 kandidat ambigu; tidak untuk XVII.1 sekaligus
AO|Perkada|IX.1 kandidat; AO1 menanyakan target/terbit
AP|jumlah RDTR OSS|Pembilang IX.2 kandidat, tanpa kode mandiri
AQ|rasio %|IX.2 kandidat; formula/konstanta campur
AR|rasio %|IX.3 kandidat; 100%-AQ
AS|jumlah NIK|Tambahan kepemilikan bukan IV.1 atau XIX
AT|persen 0–100|Tambahan AS/(AS+AU)*100, staging
AU|jumlah non-NIK|Tambahan kepemilikan bukan nonkuasa XIX.4
AV|persen 0–100|Tambahan AU/(AS+AU)*100, staging
AW|orang umur >30|Tambahan penduduk bukan jumlah sertipikat
AX|jumlah sertipikat|Tambahan AS+AU bukan STEL/BT otomatis
AY|rasio %|Tambahan (AW-AX)/AW; orang vs sertipikat ambigu
AZ|status MPP|Tambahan tanpa kode, bukan layanan prioritas
BA|jumlah KW456|Pendukung tambahan; tidak dikurangi dari C ha
BB|ha|II.2, II.1; m2/10000 sumber
'@ -split "`n"
function idx($c){$n=0;foreach($ch in $c.ToCharArray()){$n=$n*26+([int]$ch-64)};$n}
foreach($spec in $specs){if(!$spec.Trim()){continue};$a=$spec.Trim() -split '\|',3;$col=$a[0];$headers=@();foreach($r in 2..5){$cell=$xc["$col$r"];if(!$cell){foreach($merge in $wb.sheets[0].merges){if($merge -match '^([A-Z]+)(\d+):([A-Z]+)(\d+)$'){$l=idx $Matches[1];$t=[int]$Matches[2];$rr=idx $Matches[3];$b=[int]$Matches[4];if((idx $col) -ge $l -and (idx $col) -le $rr -and $r -ge $t -and $r -le $b){$cell=$xc[($Matches[1]+$Matches[2])];break}}}};if($cell -and $cell.value -and $headers -notcontains $cell.value){$headers+=$cell.value}}
 $cc=@($wb.sheets[0].cells | Where-Object {$_.ref -match "^$col\d+$" -and [int]($_.ref -replace '[A-Z]','') -ge 6 -and [int]($_.ref -replace '[A-Z]','') -le 30});$filled=@($cc | Where-Object {$_.value -ne ''}).Count;$dash=@($cc | Where-Object {$_.value.Trim() -eq '-'}).Count;$fc=@($cc | Where-Object {$_.formula -or $_.formulaType});$f=$fc | Where-Object {$_.formula} | Select-Object -First 1;$formula=if($f){$f.ref+': '+$f.formula+' ('+$fc.Count+' sel)'}elseif($fc.Count){'Shared '+$fc.Count+' sel'}else{'—'};$types=($cc | ForEach-Object {if($_.type){$_.type}else{'n'}} | Sort-Object -Unique) -join ','
 $vals=@($col,($headers -join ' → '),$a[1],$a[2],"$filled / $(25-$filled) / $dash",$types,$formula)|ForEach-Object {esc $_};$lines.Add('| '+($vals -join ' | ')+' |')
}
$lines.Add('');$lines.Add('## Seluruh wilayah aktual');$lines.Add('');$lines.Add('| Baris | NO sumber | Nama |');$lines.Add('|---|---|---|');foreach($r in 6..30){$lines.Add('| '+$r+' | '+$xc["A$r"].value+' | '+$xc["B$r"].value+' |')}
$lines.Add('');$lines.Add('Dibanding master lokal database/seeders/regions_jawa_barat.sql, Kabupaten Bogor dan Kabupaten Pangandaran tidak ada di workbook. Ini perbandingan artefak lokal, bukan verifikasi master nasional terkini. Tidak dibuat nilai wilayah absen. Kecamatan/desa/koordinat tidak tersedia.')
$lines.Add('');$lines.Add('## Hitung ulang sampel Bandung r6');$lines.Add('');$lines.Add('| Perhitungan | Hasil ulang | Cache/kesimpulan |');$lines.Add('|---|---|---|')
function num($ref){[double]::Parse($xc[$ref].value,[Globalization.CultureInfo]::InvariantCulture)}
$samples=@(@('APL D+E',((num D6)+(num E6)),('C6='+$xc.C6.value)),@('% sertipikat 100*D/C',(100*(num D6)/(num C6)),'F6=0.51 -> 51%, beda pembulatan'),@('PAD H+J',((num H6)+(num J6)),('L6='+$xc.L6.value)),@('GAP N-O',((num N6)-(num O6)),'P6=NIB > NOP bertentangan'),@('% HT 100*R/S',(100*(num R6)/(num S6)),('T6 rasio '+$xc.T6.value)),@('% aset belum',(100*(num X6)/((num U6)+(num X6))),('AA6 rasio '+$xc.AA6.value)),@('APL terpetakan C-BB',((num C6)-(num BB6)),'Usulan, subset perlu validasi'))
foreach($s in $samples){$lines.Add('| '+$s[0]+' | '+$s[1].ToString('G16',[Globalization.CultureInfo]::InvariantCulture)+' | '+$s[2]+' |')}
$lines.Add('');$lines.Add('AB6 shared-string angka 97.68; AT6 numeric 62.901582954849054 formula *100 dan format angka; F6 numeric 0.51 format % (numFmtId 9). V7 teks 1.868.493 perlu normalisasi ribuan. Jangan terapkan satu pengali persen untuk seluruh kolom. Simpan raw/cache/formula/style dan versi transformasi; kosong/dash null, bukan nol.')
$lines | Set-Content -Encoding UTF8 docs/02-data-dictionary-mapping.md
$blue.Add('');$blue.Add('## Filter, provenance dan state');$blue.Add('');$blue.Add('- Filter global provinsi, kab/kota, kecamatan, desa/kelurahan (hanya bila referensi/data tersedia) dan periode eksplisit. Awal Jawa Barat, coverage 25 wilayah; wilayah/provinsi lain no-data. Jangan agregasikan parent dan child sekaligus.');$blue.Add('- Setiap KPI memiliki unit, periode laporan dan cakupan waktu sumber, owner, status validasi, coverage dan waktu pembaruan. Rasio provinsi dihitung dari jumlah numerator/denominator sebanding, bukan sum persen. Durasi tidak dijumlahkan.');$blue.Add('- Keuangan: PAD dilabeli subtotal PBB+BPHTB. PBB/NOP manual; PAD/GAP/rasio formula terkunci. Status koneksi tidak disimpulkan dari kesamaan NIB/NOP.');$blue.Add('- Aset XI manual Pemda dengan Excel awal; XII membutuhkan objek Hak Pakai. Tabel sebelum peta; jangan membuat titik/poligon kecamatan/desa rekaan.');$blue.Add('- ZNT hanya VII.1 cocok langsung. AC/AD/AE/AF metadata tambahan setelah persetujuan, bukan pengganti VII.2/VII.3. Kolom ekstra tidak otomatis menu baru.');$blue.Add('- Layanan: tujuh prioritas, kelompok pengguna, durasi dan pengukuran; semua no-data sampai sumber Kantah tersedia, tidak memakai dummy validasi.');$blue.Add('- BHUMI: label presentasi pengguna, source_label PETA GTRA/r62 tetap terlacak. Mode link/embed/layer dan hak akses ditentukan kemudian.');$blue.Add('- State: loading, empty filter, no-data, error/retry aman, draft/rejected, serta nol hanya jika sumber nol. Badge terverifikasi hanya dari approval.');$blue.Add('- Pertahankan card/sidebar/tabel/tema/logo dan pola responsif lama. Kelak uji keyboard, fokus, kontras, 360/768/1366, overflow, unit dan provenance. Belum ada verifikasi browser.');$blue.Add('- Konflik Legenda: Kelompok Pengguna di Umum dan MVP hanya dua layanan; keputusan sementara mengikuti konteks terbaru/sheet utama tanpa menghapus entri.');$blue | Set-Content -Encoding UTF8 docs/03-menu-dashboard-blueprint.md

