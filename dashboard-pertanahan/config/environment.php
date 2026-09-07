<?php
declare(strict_types=1);

// Literal .env values only. Existing server environment takes precedence.
function load_environment(string $path): void
{
    if (!is_file($path)) { return; }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) { throw new RuntimeException('Konfigurasi tidak dapat dibaca.'); }
    foreach ($lines as $index => $line) {
        if ($index === 0) { $line = preg_replace('/^\xEF\xBB\xBF/', '', $line); }
        $line = trim($line);
        if ($line === '' || $line[0] === '#') { continue; }
        if (!preg_match('/^([A-Z][A-Z0-9_]*)\s*=\s*(.*)$/D', $line, $matches)) {
            throw new RuntimeException('Format konfigurasi tidak valid pada baris ' . ($index + 1));
        }
        $value = trim($matches[2]);
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            if (strlen($value) < 2 || substr($value, -1) !== $value[0]) {
                throw new RuntimeException('Kutip konfigurasi tidak lengkap pada baris ' . ($index + 1));
            }
            $value = substr($value, 1, -1);
        }
        if (getenv($matches[1]) === false) { putenv($matches[1] . '=' . $value); }
    }
}
