#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * HW24 / eazybusiness: isolated JTL article CSV STAGING pilot.
 *
 * Standalone PHP 8.3 CLI / PDO_SQLITE only; never connects to JTL or Laravel's DB.
 * Default mode validates CSV and prints aggregate QA. Only --apply with an
 * exact source SHA256, expected row count, new destination file and private
 * 0700 directory may create a new SQLite staging database.
 *
 * NEVER call via HTTP. No .env, credentials, order/customer data, APIs,
 * cron jobs, existing DB migrations or existing SQLite modifications.
 *
 * Examples:
 *   php platform/ops/jtl-article-csv-stage.php --input=/root/hw24-stage/articles.csv
 *   php platform/ops/jtl-article-csv-stage.php --input=/root/hw24-stage/articles.csv \
 *     --apply --expected-count=2692 --sha256=<actual source sha256> \
 *     --db=/root/hw24-stage/jtl_articles_pilot.sqlite
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

const HEADERS = [
    'jtl_article_id',
    'jtl_article_number',
    'jtl_barcode',
    'jtl_active_raw',
    'jtl_deleted_raw',
    'jtl_sales_net_raw',
    'jtl_uvp_raw',
    'jtl_amazon_price_field_raw',
    'jtl_ebay_price_field_raw',
    'jtl_tax_class_id',
    'jtl_tax_class_label',
    'jtl_delivery_status_id',
    'jtl_delivery_label_language_1',
    'jtl_unit_id',
    'jtl_unit_symbol',
    'jtl_unit_quantity_raw',
    'jtl_article_stock_raw',
    'jtl_last_modified_raw',
    'jtl_rowversion_hex',
];

function failSafe(string $message): never
{
    fwrite(STDERR, "STOP: {$message}\n");
    exit(2);
}

function options(array $args): array
{
    $out = ['apply' => false];
    foreach ($args as $arg) {
        if ($arg === '--help') {
            echo "Validate: php jtl-article-csv-stage.php --input=/PRIVATE/articles.csv\n";
            echo "Stage: ... --apply --expected-count=N --sha256=64_HEX --db=/PRIVATE/stage.sqlite\n";
            exit(0);
        }
        if ($arg === '--apply') {
            $out['apply'] = true;
            continue;
        }
        if (!preg_match('/^--(input|db|expected-count|sha256)=(.+)$/D', $arg, $match)) {
            failSafe("unknown or incomplete option");
        }
        if (isset($out[$match[1]])) {
            failSafe("duplicate option");
        }
        $out[$match[1]] = $match[2];
    }
    if (empty($out['input'])) {
        failSafe("--input is required");
    }
    if (!$out['apply'] && isset($out['db'])) {
        failSafe("--db is permitted only with --apply");
    }
    return $out;
}

function checkDestination(string $rawPath, string $scriptDirectory): string
{
    if ($rawPath === '' || $rawPath[0] !== '/' || !str_ends_with($rawPath, '.sqlite')) {
        failSafe("--db must be an absolute new .sqlite file");
    }
    $parent = realpath(dirname($rawPath));
    if ($parent === false || !is_dir($parent)) {
        failSafe("destination parent directory must exist");
    }
    $mode = fileperms($parent);
    if ($mode === false || ($mode & 0077) !== 0) {
        failSafe("destination parent directory requires private owner-only permissions (0700)");
    }
    $project = realpath(dirname($scriptDirectory));
    if ($project !== false && ($parent === $project || str_starts_with($parent, $project . '/'))) {
        failSafe("destination must be outside the application/project tree");
    }
    $full = $parent . DIRECTORY_SEPARATOR . basename($rawPath);
    if (file_exists($full) || is_link($full)) {
        failSafe("destination already exists; refusing to overwrite or reset a stage");
    }
    return $full;
}

function csvRows(string $input): array
{
    $file = realpath($input);
    if ($file === false || !is_file($file) || !is_readable($file)) {
        failSafe("input file unavailable or unreadable");
    }
    if ((filesize($file) ?: 0) > 25 * 1024 * 1024) {
        failSafe("CSV exceeds the 25 MiB isolated pilot limit");
    }
    $project = realpath(dirname(__DIR__));
    if ($project !== false && str_starts_with($file, $project . '/public/')) {
        failSafe("input must not be in the public web root");
    }
    $fh = fopen($file, 'rb');
    if ($fh === false) {
        failSafe("cannot open input");
    }
    try {
        $header = fgetcsv($fh, 0, ';', '"', '');
        if (!is_array($header)) {
            failSafe("CSV is empty");
        }
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
        if ($header !== HEADERS) {
            failSafe("CSV header mismatch: expected the exact Phase-6 19-column export");
        }
        $rows = [];
        $ids = [];
        $numbers = [];
        $barcodes = [];
        $stats = [
            'missing_barcodes' => 0,
            'missing_unit_ids' => 0,
            'delivery_status_id_zero' => 0,
            'blank_rowversions' => 0,
            'zero_article_stock' => 0,
            'leading_zero_barcodes' => 0,
            'null_deleted_flags' => 0,
            'nonempty_rowversion_count' => 0,
        ];
        $line = 1;
        while (($row = fgetcsv($fh, 0, ';', '"', '')) !== false) {
            $line++;
            if (count($row) !== count(HEADERS)) {
                failSafe("wrong column count at line {$line}");
            }
            if (!preg_match('/^[1-9][0-9]*$/D', (string) $row[0])) {
                failSafe("invalid article ID at line {$line}");
            }
            if ((string) $row[1] === '') {
                failSafe("missing article number at line {$line}");
            }
            if (isset($ids[$row[0]]) || isset($numbers[$row[1]])) {
                failSafe("duplicate JTL article ID or article number at line {$line}");
            }
            $ids[$row[0]] = true;
            $numbers[$row[1]] = true;
            foreach ([5, 6, 7, 8, 15, 16] as $decimalIndex) {
                if (!preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D', (string)$row[$decimalIndex])) {
                    failSafe("invalid raw decimal at line {$line}");
                }
            }
            $barcode = (string)$row[2];
            if ($barcode === '' || $barcode === '\\N') {
                $stats['missing_barcodes']++;
            } else {
                $barcodes[$barcode] = ($barcodes[$barcode] ?? 0) + 1;
                if (str_starts_with($barcode, '0')) {
                    $stats['leading_zero_barcodes']++;
                }
            }
            if ($row[13] === '0' || $row[13] === '\\N') {
                $stats['missing_unit_ids']++;
            }
            if ($row[11] === '0') {
                $stats['delivery_status_id_zero']++;
            }
            if ($row[16] === '0') {
                $stats['zero_article_stock']++;
            }
            if ($row[4] === '\\N') {
                $stats['null_deleted_flags']++;
            }
            $version = (string) $row[18];
            if ($version === '' || $version === '\\N') {
                $stats['blank_rowversions']++;
            } elseif (!preg_match('/^0x[0-9a-fA-F]{16}$/D', $version)) {
                failSafe("unexpected rowversion hex encoding at line {$line}");
            } else {
                $stats['nonempty_rowversion_count']++;
            }
            $rows[] = array_map(
                static fn($value): ?string => $value === '\\N' ? null : (string)$value,
                $row
            );
        }
        if (!$rows || count($rows) > 100000) {
            failSafe("row count outside isolated pilot limits");
        }
        $stats['duplicate_barcode_groups'] = count(array_filter($barcodes, static fn(int $n) => $n > 1));
        $stats['articles_in_duplicate_barcode_groups'] = array_sum(array_filter($barcodes, static fn(int $n) => $n > 1));
        return [$rows, $stats, $file];
    } finally {
        fclose($fh);
    }
}

function writeIsolatedStage(string $path, array $rows, string $sha256): void
{
    if (!extension_loaded('pdo_sqlite')) {
        failSafe("PDO SQLite extension is required; no file was installed");
    }
    $temp = tempnam(dirname($path), '.hw24-stage-');
    if ($temp === false) {
        failSafe("cannot create private temporary stage");
    }
    chmod($temp, 0600);
    try {
        $db = new PDO('sqlite:' . $temp);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA journal_mode=DELETE');
        $db->exec('PRAGMA synchronous=FULL');
        $db->beginTransaction();
        $quoted = array_map(static fn(string $col) => '"' . $col . '" TEXT', HEADERS);
        $quoted[0] = '"jtl_article_id" INTEGER PRIMARY KEY';
        $db->exec('CREATE TABLE jtl_article_stage (' . implode(',', $quoted) . ')');
        $db->exec('CREATE TABLE stage_manifest (id INTEGER PRIMARY KEY CHECK (id=1), source_database TEXT NOT NULL, source_table TEXT NOT NULL, source_file_sha256 TEXT NOT NULL, article_count INTEGER NOT NULL, imported_utc TEXT NOT NULL, is_consistent_cross_table_snapshot INTEGER NOT NULL DEFAULT 0)');
        $names = implode(',', array_map(static fn(string $x) => '"' . $x . '"', HEADERS));
        $marks = implode(',', array_fill(0, count(HEADERS), '?'));
        $insert = $db->prepare("INSERT INTO jtl_article_stage ({$names}) VALUES ({$marks})");
        foreach ($rows as $row) {
            $insert->execute($row);
        }
        $manifest = $db->prepare('INSERT INTO stage_manifest(id,source_database,source_table,source_file_sha256,article_count,imported_utc,is_consistent_cross_table_snapshot) VALUES (1,?,?,?,?,?,0)');
        $manifest->execute(['eazybusiness', 'dbo.tArtikel', $sha256, count($rows), gmdate('c')]);
        $db->commit();
        $actual = (int)$db->query('SELECT COUNT(*) FROM jtl_article_stage')->fetchColumn();
        if ($actual !== count($rows) || $db->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
            throw new RuntimeException('staging SQLite integrity/row-count mismatch');
        }
        unset($db);
        chmod($temp, 0600);
        if (file_exists($path) || is_link($path)) {
            throw new RuntimeException('destination appeared during staging; refusing overwrite');
        }
        if (!rename($temp, $path)) {
            throw new RuntimeException('cannot atomically install new isolated stage');
        }
    } catch (Throwable $exception) {
        @unlink($temp);
        failSafe('isolated staging failed: ' . $exception->getMessage());
    }
}

$opts = options(array_slice($argv, 1));
[$rows, $stats, $inputFile] = csvRows($opts['input']);
$checksum = hash_file('sha256', $inputFile);
if ($checksum === false) {
    failSafe("cannot calculate source checksum");
}

if ($opts['apply']) {
    if (!isset($opts['db'], $opts['expected-count'], $opts['sha256'])) {
        failSafe("--apply requires --db, --expected-count and --sha256");
    }
    if (!ctype_digit((string)$opts['expected-count'])
        || (int)$opts['expected-count'] !== count($rows)) {
        failSafe("expected article count mismatch");
    }
    if (!preg_match('/^[0-9a-fA-F]{64}$/D', (string)$opts['sha256'])
        || !hash_equals(strtolower($checksum), strtolower((string)$opts['sha256']))) {
        failSafe("approved SHA256 does not match input");
    }
    $dbPath = checkDestination($opts['db'], __DIR__);
    writeIsolatedStage($dbPath, $rows, $checksum);
}
$summary = [
    'ok' => true,
    'mode' => $opts['apply'] ? 'private_stage_created' : 'validate_only',
    'jtl_database' => 'eazybusiness',
    'article_count' => count($rows),
    'source_sha256' => $checksum,
    'source_snapshot_consistent_across_tables' => false,
    'automatic_sync_enabled' => false,
    'rowversion_watermark_available' => $stats['nonempty_rowversion_count'] === count($rows),
    'quality' => $stats,
];
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
