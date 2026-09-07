# Runbook Presentasi

## Persiapan

1. Hidupkan Apache dan MariaDB sesuai runtime lokal.
2. Jalankan `scripts\start-dashboard.cmd`.
3. Buka `/login` lalu `/dashboard`; fungsi utama tidak memerlukan internet.

## Urutan demo

1. Login super admin dan tampilkan dashboard Excel Jawa Barat.
2. Gunakan filter submenu, wilayah, dan periode.
3. Login operator Pemda, buat draft, lalu ajukan.
4. Login super admin, buka Review Data, lalu publish atau reject.
5. Tampilkan nilai published pada dashboard.
6. Jelaskan bahwa no-data berbeda dari nol.

## Matriks akun

Gunakan role `super_admin`, `operator_pemda`, `operator_kantah`, dan `viewer_eksekutif`. Credential disimpan terpisah oleh pengguna dan tidak dicatat di Git.

## Troubleshooting

- Port 8000 dipakai: hentikan proses yang memakai port atau pilih port lokal lain secara manual.
- CSS tidak tampil: pastikan `public/build/manifest.json` tersedia dan jalankan build frontend.
- Database gagal: pastikan MariaDB aktif dan konfigurasi lokal tersedia tanpa menampilkan `.env`.
- Cache: jalankan `php artisan optimize:clear` dari `api`.
- Menu tidak muncul: periksa role aktif dan scope wilayah akun.
- Review kosong: belum ada revision berstatus submitted.

## Larangan

Jangan menjalankan `migrate:fresh`, seed/import ulang, membuka `.env`, mencetak credential, menjalankan debug query sensitif, atau push ke remote.
