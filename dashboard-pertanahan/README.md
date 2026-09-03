# Dashboard Pertanahan — MVP

MVP ini menggunakan struktur UI dashboard yang telah disediakan tim: sidebar, kartu progres, tabel pekerjaan, profil pengguna, dan mode gelap. Fungsinya mencakup login berbasis peran, dashboard agregat provinsi, input data Pemda, dan adaptor baca data KKP.

## Menjalankan aplikasi

1. Buka PowerShell pada folder aplikasi.
2. Jalankan `C:\xampp\php\php.exe -S localhost:8000`.
3. Buka `http://localhost:8000/login.php`. Setelah masuk, halaman utama ada di `dashboard.php`.

Gunakan salah satu akun berikut dengan kata sandi `Demo123!`:

- `joshua@demo.local` — eksekutif, hanya melihat dashboard.
- `admin@demo.local` — melihat dashboard dan menginput data.
- `pemda@demo.local` — melihat dashboard dan menginput data.

## Mekanisme data

1. Pemda mengirim angka per kabupaten/kota dan periode melalui halaman **Input Pemda**.
2. Aplikasi menolak nilai negatif, tahap 1 yang melebihi total, dan tahap 2 yang melebihi tahap 1.
3. Data tersimpan secara idempoten dengan kunci `wilayah + periode + sumber`; pengiriman ulang memperbarui snapshot Pemda yang sama.
4. Dashboard menjumlahkan hanya angka absolut kabupaten/kota untuk periode tersebut. Persentase tahap validasi dihitung kembali dari jumlah provinsi.
5. Endpoint `api/kkp.php?province=32&date=2026-09-01` menampilkan snapshot KKP yang tersimpan. Halaman **Sinkronisasi KKP** memanggil adaptor API.

## Struktur dan tindak lanjut tim

- `db.php`: database, skema, data awal; pemilik: Rio.
- `index.php`: dashboard eksekutif; pemilik: Joshua dan Rezky.
- `input-data.php`: kanal input Pemda dan validasi; pemilik: Firdaus dan Wijaya.
- `api/kkp.php`: adaptor integrasi; pemilik: Agung Pratama dan Igan.
- `assets/style.css`: desain responsif dan aksesibilitas; pemilik: Rezky.
- Kamus data/CSV dan pengujian agregasi: Wafir.
- SOP operasi, UAT, dokumentasi serta demo: Agung Krido dan Putu Dedi.

## Sebelum integrasi KKP produksi

Gunakan [KKP-INTEGRATION.md](KKP-INTEGRATION.md) sebagai checklist teknis. Konfigurasi contoh ada di `config/kkp.env.example`; token tidak pernah disimpan pada source code.
