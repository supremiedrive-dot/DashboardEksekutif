<?php

// Guarded local phase runner: never prints connection credentials or row contents.
if (in_array('--testing', $argv, true)) {
    putenv('APP_ENV=testing');
}

require dirname(__DIR__).'/vendor/autoload.php';
$app = require dirname(__DIR__).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $environment = $app->environment();
    $expected = match ($environment) {
        'testing' => 'dashboard_pertanahan_test',
        'local' => 'dashboard_pertanahan_dev',
        default => throw new RuntimeException('Only local/testing environments are permitted.'),
    };
    $db = Illuminate\Support\Facades\DB::connection();
    if ($app->configurationIsCached() || config('database.default') !== 'mysql'
        || $db->getConfig('url') || $db->getConfig('read') || $db->getConfig('write')
        || $db->getDatabaseName() !== $expected
        || $db->selectOne('SELECT DATABASE() AS db')->db !== $expected) {
        throw new RuntimeException('Database isolation check failed.');
    }
    echo json_encode(['environment' => $environment, 'driver' => $db->getDriverName(),
        'database' => $expected, 'server' => $db->selectOne('SELECT VERSION() AS version')->version]).PHP_EOL;
    $schema = $db->getSchemaBuilder();
    foreach ($schema->getTableListing() as $table) {
        echo $table.' rows='.$db->table($table)->count().PHP_EOL;
    }
    if (in_array('--phase3a', $argv, true) && $schema->hasTable('indicator_definitions')) {
        $identityColumns = collect($db->select("SHOW INDEX FROM observations WHERE Key_name = 'observations_identity_unique'"))
            ->sortBy('Seq_in_index')->pluck('Column_name')->values()->all();
        $xiii4 = $db->table('indicator_definitions as definitions')
            ->join('indicators', 'indicators.id', '=', 'definitions.indicator_id')
            ->where('indicators.canonical_code', 'XIII.4')
            ->select(['definitions.formula_key', 'definitions.formula_metadata', 'indicators.quality_status'])
            ->first();
        $mapDefinitions = $db->table('indicator_definitions as definitions')
            ->join('indicators', 'indicators.id', '=', 'definitions.indicator_id')
            ->where('indicators.canonical_code', 'MAP.1')->count();
        echo 'observation_identity='.implode(',', $identityColumns).PHP_EOL;
        echo 'xiii4_formula_key='.(is_null($xiii4?->formula_key) ? 'NULL' : 'SET')
            .' formula_metadata='.(is_null($xiii4?->formula_metadata) ? 'NULL' : 'SET')
            .' quality_status='.($xiii4?->quality_status ?? 'MISSING').PHP_EOL;
        echo 'map1_definition_count='.$mapDefinitions.PHP_EOL;
    }
    if (in_array('--phase3b', $argv, true) && $schema->hasTable('import_batches')) {
        $promotedRegions = $db->table('observations')->distinct()->count('region_id');
        $promotedIndicators = $db->table('observations')->distinct()->count('indicator_id');
        $nonAtrObservations = $db->table('observations')
            ->join('indicators', 'indicators.id', '=', 'observations.indicator_id')
            ->join('data_owners', 'data_owners.id', '=', 'indicators.data_owner_id')
            ->where('data_owners.code', '<>', 'atr_bpn')->count();
        $lineageMissing = $db->table('observation_revisions')->where('input_method', 'excel')
            ->where(fn ($query) => $query->whereNull('import_value_id')->orWhereNull('source_sheet')
                ->orWhereNull('source_row')->orWhereNull('source_column')->orWhereNull('source_checksum')
                ->orWhereNull('source_period_text'))->count();
        echo 'phase3b_batches='.$db->table('import_batches')->count()
            .' rows='.$db->table('import_rows')->count()
            .' values='.$db->table('import_values')->count()
            .' staged_formulas='.$db->table('import_values')->whereNotNull('formula_type')->count()
            .' import_qc='.$db->table('import_quality_flags')->count().PHP_EOL;
        echo 'phase3b_observations='.$db->table('observations')->count()
            .' revisions='.$db->table('observation_revisions')->where('input_method', 'excel')->count()
            .' regions='.$promotedRegions.' indicators='.$promotedIndicators
            .' non_atr_observations='.$nonAtrObservations.' missing_lineage='.$lineageMissing.PHP_EOL;
    }
} catch (Throwable $e) {
    if ($e instanceof Illuminate\Database\QueryException) {
        fwrite(STDERR, 'SQLSTATE='.$e->getCode().' driver_code='.($e->errorInfo[1] ?? 'unknown').PHP_EOL);
    }
    fwrite(STDERR, 'Database inspection failed: '.get_class($e).'; details withheld.'.PHP_EOL);
    exit(1);
}
