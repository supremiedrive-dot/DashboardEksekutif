# Checklist integrasi KKP

Adaptor ada di `services/KkpClient.php`. Saat ini aplikasi berjalan aman pada `KKP_MODE=mock`; tidak ada permintaan yang dikirim ke sistem KKP.

## Informasi yang perlu diterima dari Pusdatin/KKP

1. Base URL lingkungan uji dan produksi.
2. Endpoint metrik pertanahan serta parameter wilayah dan periode.
3. Mekanisme autentikasi, masa berlaku token, dan cara pembaruan token.
4. Contoh respons berhasil, respons gagal, serta batas laju permintaan.
5. Pemetaan indikator dan kode wilayah resmi.
6. Kepemilikan data: apakah Pemda atau KKP menjadi sumber utama ketika dua snapshot tersedia pada periode yang sama.

## Kontrak kanonis aplikasi

Setiap respons KKP dinormalisasi ke field berikut:

| Field | Tipe | Aturan |
| --- | --- | --- |
| `region_code` | string | Kode BPS kabupaten/kota. |
| `report_date` | tanggal | Format `YYYY-MM-DD`. |
| `total_records` | integer | Tidak boleh negatif. |
| `validated_stage_1` | integer | Tidak boleh melebihi total. |
| `validated_stage_2` | integer | Tidak boleh melebihi tahap 1. |
| `area_hectare` | angka | Satuan hektare, tidak boleh negatif. |

## Mengaktifkan koneksi live

1. Salin nilai pada `config/kkp.env.example` ke konfigurasi environment Apache/PHP; jangan simpan token pada Git atau source code.
2. Isi `KKP_MODE=live`, `KKP_API_BASE_URL`, `KKP_METRICS_ENDPOINT`, dan `KKP_API_BEARER_TOKEN`.
3. Jika field API KKP tidak sama dengan kontrak kanonis, ubah satu fungsi `normalizePayload()` dalam `services/KkpClient.php` berdasarkan `data/mappings/kkp-field-mapping.example.json`.
4. Jalankan sinkronisasi untuk satu provinsi dan satu periode di lingkungan uji, lalu cocokkan totalnya dengan data sumber.
5. Baru aktifkan jadwal sinkronisasi dan logging produksi setelah UAT disetujui.

## Aturan agregasi

Dashboard menjumlahkan metrik absolut kabupaten/kota. Persentase diturunkan ulang dari total provinsi. Jika ada snapshot Pemda dan KKP untuk wilayah/periode yang sama, implementasi MVP memprioritaskan snapshot Pemda agar tidak terjadi hitung ganda. Keputusan sumber utama ini harus dikonfirmasi oleh pemilik data.
