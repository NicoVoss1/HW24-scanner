#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * HW24 private warehouse pilot, source dbo.tlagerbestandProLagerLagerartikel.
 *
 * OFF by default: parse a private CSV and print aggregate quality metrics.
 * --apply can ONLY create a NEW sqlite file (never existing main Laravel DB).
 * NO SQL Server connection, NO JTL write, NO HTTP route, NO scheduler.
 * Original quantities are stored as TEXT to avoid floating-point changes.
 * "Stock" and both locked fields are NOT available-to-sell calculations.
 *
 * Dry-run:
 *   php platform/ops/jtl-warehouse-csv-stage.php --input=/root/hw24-private/warehouse.csv
 * Explicit isolated stage:
 *   php platform/ops/jtl-warehouse-csv-stage.php --input=/root/hw24-private/warehouse.csv \
 *     --apply --expected-count=12717 --sha256=ACTUAL_HEX_SHA256 \
 *     --db=/root/hw24-private/warehouse-stage.sqlite
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
const WAREHOUSE_HEADERS = [
    'jtl_article_id', 'jtl_warehouse_id', 'jtl_stock_raw',
    'jtl_available_blocked_raw', 'jtl_shipping_blocked_raw',
    'jtl_rowversion_hex',
];

function warehouseFail(string $reason): never
{
    fwrite(STDERR, "STOP: " . $reason . PHP_EOL);
    exit(2);
}

function warehouseArgs(array $args): array
{
    $out = ['apply' => false];
    foreach ($args as $arg) {
        if ($arg === '--help') {
            echo "Dry run: --input=/PRIVATE/stock.csv; apply: add --apply --db=NEW_FILE.sqlite --expected-count=N --sha256=SHA256\n";
            exit(0);
        }
        if ($arg === '--apply') {
            $out['apply'] = true;
            continue;
        }
        if (!preg_match('/^--(input|db|expected-count|sha256)=(.+)$/D', $arg, $m)) {
            warehouseFail('unknown or incomplete command option');
        }
        if (isset($out[$m[1]])) {
            warehouseFail('duplicate option');
        }
        $out[$m[1]] = $m[2];
    }
    if (empty($out['input'])) {
        warehouseFail('--input is required');
    }
    if (!$out['apply'] && isset($out['db'])) {
        warehouseFail('--db is not permitted without --apply');
    }
    return $out;
}

function warehouseRejectRepoFile(string $file): void
{
    $repoRoot = realpath(dirname(__DIR__, 2));
    if ($repoRoot !== false && ($file === $repoRoot || str_starts_with($file, $repoRoot . '/'))) {
        warehouseFail('private stock data must remain outside GitHub checkout and public application');
    }
}

function warehouseSourceRows(string $input): array
{
    $path = realpath($input);
    if ($path === false || !is_file($path) || !is_readable($path)) {
        warehouseFail('private input file missing');
    }
    warehouseRejectRepoFile($path);
    if ((filesize($path) ?: 0) > 30 * 1024 * 1024) {
        warehouseFail('CSV exceeds pilot 30 MiB limit');
    }
    $fh = fopen($path, 'rb');
    if ($fh === false) {
        warehouseFail('CSV input could not be opened');
    }
    try {
        $header = fgetcsv($fh, 0, ';', '"', '');
        if (!is_array($header)) {
            warehouseFail('CSV is empty');
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header[0]);
        if ($header !== WAREHOUSE_HEADERS) {
            warehouseFail('CSV header mismatch (six exact Phase-9 fields expected)');
        }
        $rows = [];
        $keys = [];
        $stats = [
            'nonzero_raw_stock_rows' => 0,
            'positive_raw_stock_rows' => 0,
            'negative_raw_stock_rows' => 0,
            'missing_rowversion_rows' => 0,
            'distinct_articles' => 0,
            'distinct_warehouses' => 0,
        ];
        $articles = [];
        $warehouses = [];
        $line = 1;
        while (($row = fgetcsv($fh, 0, ';', '"', '')) !== false) {
            $line++;
            if (count($row) !== 6) {
                warehouseFail("CSV field count mismatch at line {$line}");
            }
            if (!preg_match('/^[1-9][0-9]*$/D', (string)$row[0])
                || !preg_match('/^[1-9][0-9]*$/D', (string)$row[1])) {
                warehouseFail("invalid source article/warehouse key at line {$line}");
            }
            $key = $row[0] . ':' . $row[1];
            if (isset($keys[$key])) {
                warehouseFail("duplicate composite JTL warehouse stock key at line {$line}");
            }
            $keys[$key] = true;
            $articles[$row[0]] = true;
            $warehouses[$row[1]] = true;
            foreach ([2, 3, 4] as $i) {
                if (!preg_match('/^-?(?:[0-9]+)(?:\.[0-9]+)?$/D', (string)$row[$i])) {
                    warehouseFail("invalid raw decimal quantity at line {$line}");
                }
            }
            $rawStock = (string)$row[2];
            if ((float)$rawStock !== 0.0) {
                $stats['nonzero_raw_stock_rows']++;
            }
            if ((float)$rawStock > 0.0) {
                $stats['positive_raw_stock_rows']++;
            }
            if ((float)$rawStock < 0.0) {
                $stats['negative_raw_stock_rows']++;
            }
            $rv = (string)$row[5];
            if ($rv === '' || $rv === '\\N') {
                $stats['missing_rowversion_rows']++;
            } elseif (!preg_match('/^0x[0-9a-fA-F]{16}$/D', $rv)) {
                warehouseFail("invalid source rowversion hex at line {$line}");
            }
            $rows[] = array_map(static fn($value): string => (string)$value, $row);
        }
        if (!$rows || count($rows) > 100000) {
            warehouseFail('row count outside isolated pilot bounds');
        }
        $stats['distinct_articles'] = count($articles);
        $stats['distinct_warehouses'] = count($warehouses);
        return [$rows, $stats, $path];
    } finally {
        fclose($fh);
    }
}

function warehouseNewStagePath(string $path): string
{
    if ($path === '' || $path[0] !== '/' || !str_ends_with($path, '.sqlite')) {
        warehouseFail('destination must be a new absolute .sqlite path');
    }
    $parent = realpath(dirname($path));
    if ($parent === false || !is_dir($parent)) {
        warehouseFail('destination private directory must already exist');
    }
    warehouseRejectRepoFile($parent);
    $perms = fileperms($parent);
    if ($perms === false || ($perms & 0077) !== 0) {
        warehouseFail('destination directory must be private, chmod 700');
    }
    $destination = $parent . '/' . basename($path);
    if (file_exists($destination) || is_link($destination)) {
        warehouseFail('destination exists: no overwrite permitted');
    }
    return $destination;
}

function warehouseStage(string $path, array $rows, string $hash): void
{
    if (!extension_loaded('pdo_sqlite')) {
        warehouseFail('pdo_sqlite extension is unavailable');
    }
    $temp = tempnam(dirname($path), '.hw24-stock-stage-');
    if ($temp === false) {
        warehouseFail('cannot create private isolated stage');
    }
    chmod($temp, 0600);
    try {
        $db = new PDO('sqlite:' . $temp);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA journal_mode=DELETE');
        $db->exec('PRAGMA synchronous=FULL');
        $db->beginTransaction();
        $db->exec('CREATE TABLE jtl_warehouse_stock_stage (
            jtl_article_id INTEGER NOT NULL,
            jtl_warehouse_id INTEGER NOT NULL,
            jtl_stock_raw TEXT NOT NULL,
            jtl_available_blocked_raw TEXT NOT NULL,
            jtl_shipping_blocked_raw TEXT NOT NULL,
            jtl_rowversion_hex TEXT,
            PRIMARY KEY (jtl_article_id,jtl_warehouse_id)
        )');
        $db->exec('CREATE TABLE stage_manifest (
            id INTEGER PRIMARY KEY CHECK(id=1),
            source_database TEXT NOT NULL,
            source_table TEXT NOT NULL,
            source_file_sha256 TEXT NOT NULL,
            stock_row_count INTEGER NOT NULL,
            imported_utc TEXT NOT NULL,
            is_consistent_cross_table_snapshot INTEGER NOT NULL DEFAULT 0
        )');
        $insert = $db->prepare('INSERT INTO jtl_warehouse_stock_stage VALUES (?,?,?,?,?,?)');
        foreach ($rows as $row) {
            $row[5] = ($row[5] === '' || $row[5] === '\\N') ? null : $row[5];
            $insert->execute($row);
        }
        $manifest = $db->prepare('INSERT INTO stage_manifest VALUES (1,?,?,?,?,?,0)');
        $manifest->execute(['eazybusiness','dbo.tlagerbestandProLagerLagerartikel',$hash,count($rows),gmdate('c')]);
        $db->commit();
        if ((int)$db->query('SELECT COUNT(*) FROM jtl_warehouse_stock_stage')->fetchColumn() !== count($rows)
            || $db->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
            throw new RuntimeException('isolated stock staging integrity check failed');
        }
        unset($db);
        chmod($temp, 0600);
        if (file_exists($path) || is_link($path) || !rename($temp,$path)) {
            throw new RuntimeException('destination appeared or staging rename failed');
        }
    } catch (Throwable $e) {
        @unlink($temp);
        warehouseFail('private stock staging failed: '.$e->getMessage());
    }
}

$opts=warehouseArgs(array_slice($argv,1));
[$rows,$stats,$inputFile]=warehouseSourceRows($opts['input']);
$sha=hash_file('sha256',$inputFile);
if ($sha===false) {
    warehouseFail('source SHA256 could not be computed');
}
if ($opts['apply']) {
    if (!isset($opts['db'],$opts['expected-count'],$opts['sha256'])) {
        warehouseFail('--apply requires --db, --expected-count, --sha256');
    }
    if (!ctype_digit((string)$opts['expected-count'])
        || (int)$opts['expected-count']!==count($rows)) {
        warehouseFail('expected source row count mismatch');
    }
    if (!preg_match('/^[0-9a-fA-F]{64}$/D',(string)$opts['sha256'])
        || !hash_equals(strtolower($sha),strtolower((string)$opts['sha256']))) {
        warehouseFail('source SHA256 mismatch');
    }
    if ($stats['missing_rowversion_rows']!==0) {
        warehouseFail('missing stock rowversion: stage only with complete source');
    }
    warehouseStage(warehouseNewStagePath($opts['db']),$rows,$sha);
}
echo json_encode([
    'ok'=>true,'mode'=>$opts['apply']?'private_stock_stage_created':'validate_only',
    'source_database'=>'eazybusiness',
    'source_table'=>'dbo.tlagerbestandProLagerLagerartikel',
    'rows'=>count($rows),'sha256'=>$sha,
    'sync_enabled'=>false,
    'is_consistent_cross_table_snapshot'=>false,
    'quality'=>$stats,
],JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
