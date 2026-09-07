@echo off
setlocal
cd /d "%~dp0..\api"
where php >nul 2>&1 || (echo PHP tidak ditemukan. Aktifkan PHP/XAMPP lalu ulangi.&pause&exit /b 1)
if not exist ".env" (echo api\.env tidak ditemukan.&pause&exit /b 1)
if not exist "vendor\autoload.php" (echo Dependensi Laravel belum tersedia. Jalankan setup sesuai runbook.&pause&exit /b 1)
if not exist "public\build\manifest.json" (echo Build frontend belum tersedia. Jalankan npm run build.&pause&exit /b 1)
echo Login: http://127.0.0.1:8000/login
echo Dashboard: http://127.0.0.1:8000/dashboard
php artisan optimize:clear
if errorlevel 1 (echo Gagal membersihkan cache.&pause&exit /b 1)
php artisan serve --host=127.0.0.1 --port=8000
pause
