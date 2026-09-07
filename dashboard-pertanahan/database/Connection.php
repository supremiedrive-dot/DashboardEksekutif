<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/environment.php';

final class DatabaseConnection
{
    public static function create(): PDO
    {
        load_environment(__DIR__ . '/../.env');
        $settings = [];
        foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
            $value = getenv($key);
            if ($value === false || ($key !== 'DB_PASS' && trim($value) === '')) {
                throw new RuntimeException('Konfigurasi wajib belum diisi: ' . $key);
            }
            $settings[$key] = $value;
        }
        if ((getenv('DB_DRIVER') ?: 'mysql') !== 'mysql'
            || !preg_match('/^[a-zA-Z0-9_.:-]+$/D', $settings['DB_HOST'])
            || !preg_match('/^[a-zA-Z0-9_]+$/D', $settings['DB_NAME'])
            || !ctype_digit($settings['DB_PORT'])
            || (int) $settings['DB_PORT'] < 1 || (int) $settings['DB_PORT'] > 65535) {
            throw new RuntimeException('Konfigurasi koneksi MySQL tidak valid.');
        }
        try {
            return new PDO(sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $settings['DB_HOST'], $settings['DB_PORT'], $settings['DB_NAME']),
                $settings['DB_USER'], $settings['DB_PASS'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
        } catch (PDOException $exception) {
            // Do not attach connection details to the public exception.
            throw new RuntimeException('Koneksi MySQL gagal. Periksa konfigurasi dan layanan database.');
        }
    }
}
