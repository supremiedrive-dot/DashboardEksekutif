<?php

namespace App\Console\Commands;

use App\Services\JawaBaratImportService;
use Illuminate\Console\Command;
use RuntimeException;
use Throwable;

class ImportJawaBaratWorkbook extends Command
{
    protected $signature = 'dashboard:import-jabar
        {--file= : Path workbook Input Data Jawa Barat.xlsx}
        {--as-of-date= : Tanggal snapshot YYYY-MM-DD}
        {--dry-run : Validasi dan hitung tanpa menulis staging atau observation}';

    protected $description = 'Import workbook Jawa Barat dengan staging, lineage, QC, dan revision idempotent';

    public function handle(JawaBaratImportService $service): int
    {
        try {
            $path = $this->sourcePath((string)$this->option('file'));
            $date = (string)$this->option('as-of-date');
            $report = $service->import($path, $date, (bool)$this->option('dry-run'));
            $this->line(json_encode($report, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR));
            return $report['status'] === 'blocked' ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error('Import gagal: '.$exception->getMessage());
            return self::FAILURE;
        }
    }

    private function sourcePath(string $requested): string
    {
        if ($requested === '') throw new RuntimeException('Option --file wajib diisi.');
        $canonical = realpath(dirname(base_path()).DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'source'
            .DIRECTORY_SEPARATOR.'Input Data Jawa Barat.xlsx');
        $candidates = [$requested, base_path($requested), dirname(base_path()).DIRECTORY_SEPARATOR.$requested];
        $resolved = null;
        foreach ($candidates as $candidate) {
            $path = realpath($candidate);
            if ($path !== false) { $resolved = $path; break; }
        }
        if (! $canonical || ! $resolved || $resolved !== $canonical) {
            throw new RuntimeException('Command ini hanya menerima workbook sumber Jawa Barat yang kanonis.');
        }
        return $resolved;
    }
}
