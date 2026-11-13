<?php
declare(strict_types=1);
/* HW24 historical lookup API. Read-only. Credentials live OUTSIDE web root.
   Server file: /var/www/vhosts/hairworld24.de/hw24_private/db.php
   This API is protected by the site's Plesk HTTP authentication. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

function respond(int $code, array $payload): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET');
    respond(405, ['ok'=>false,'error'=>'method_not_allowed']);
}
$ean = trim((string)($_GET['ean'] ?? ''));
if (!preg_match('/^[0-9]{8,14}$/D', $ean)) {
    respond(400, ['ok'=>false,'error'=>'invalid_identifier_format']);
}
function validGtIn(string $code): bool {
    if (!in_array(strlen($code), [8,12,13,14], true) || preg_match('/^0+$/D', $code)) return false;
    $sum = 0;
    for ($p = strlen($code) - 2, $weight = 3; $p >= 0; $p--, $weight = $weight === 3 ? 1 : 3) {
        $sum += (int)$code[$p] * $weight;
    }
    return ((10 - $sum % 10) % 10) === (int)$code[strlen($code)-1];
}
function excelColumn(string $coord): string {
    return preg_match('/^([A-Z]+)[0-9]+$/D', $coord, $m) ? $m[1] : '';
}
function excelCellValue(array $cell): ?string {
    $v = $cell[4] ?? $cell[3] ?? null;
    if (!is_scalar($v)) return null;
    $s = trim((string)$v);
    return $s === '' ? null : $s;
}
function excelHeaderMap(PDO $pdo, string $sha, int $sheetNo): array {
    // The imported historical workbook has non-standard header positions.
    // These three were verified directly against the source SQL archive.
    $fixed = [1=>4, 16=>3, 19=>3];
    $q = $pdo->prepare('SELECT excel_row,cells_json FROM hw24_excel_rows
                        WHERE file_sha256=? AND sheet_no=? AND excel_row BETWEEN 1 AND 12
                        ORDER BY excel_row ASC');
    $q->execute([$sha,$sheetNo]);
    $candidates = [];
    while ($r = $q->fetch(PDO::FETCH_ASSOC)) {
        $doc = json_decode($r['cells_json'], true);
        if (!is_array($doc)) continue;
        $headers = [];
        foreach (($doc['cells'] ?? []) as $cell) {
            if (!is_array($cell) || count($cell) < 5) continue;
            $column = excelColumn((string)$cell[0]);
            $v = excelCellValue($cell);
            if ($column === '' || $v === null || mbLenSafe($v) > 130) continue;
            if (preg_match('/^[0-9., %+\-]+$/D', $v)) continue;
            $headers[$column] = $v;
        }
        $candidates[(int)$r['excel_row']] = $headers;
    }
    if (isset($fixed[$sheetNo]) && !empty($candidates[$fixed[$sheetNo]])) {
        return ['row'=>$fixed[$sheetNo], 'labels'=>$candidates[$fixed[$sheetNo]]];
    }
    $bestRow = 0; $best = []; $bestScore = 0;
    foreach ($candidates as $r=>$headers) {
        $score = count($headers);
        // Reject sparse explanatory notes as column headers.
        if ($score < 3) continue;
        $score += min(5, count(array_filter($headers, static function($h) {
            return preg_match('/ean|asin|artikel|preis|menge|name|hersteller|beschreibung|sku|bestand|gtin|gewicht/i', $h);
        }))) * 3;
        if ($score > $bestScore) { $bestScore = $score; $bestRow = $r; $best = $headers; }
    }
    return ['row'=>$bestRow, 'labels'=>$best];
}
function mbLenSafe(string $s): int {
    return function_exists('mb_strlen') ? mb_strlen($s) : strlen($s);
}
function trimCellValue(string $v): string {
    return function_exists('mb_substr') ? mb_substr($v,0,500) : substr($v,0,500);
}

$configFile = dirname(__DIR__, 2) . '/hw24_private/db.php';
if (!is_file($configFile)) respond(503, ['ok'=>false,'error'=>'database_not_configured']);
try {
    $cfg = require $configFile;
    if (!is_array($cfg) || empty($cfg['user']) || !isset($cfg['password'])) {
        respond(503, ['ok'=>false,'error'=>'database_not_configured']);
    }
    $dsn = 'mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4';
    $pdo = new PDO($dsn, $cfg['user'], $cfg['password'], [
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
        PDO::ATTR_TIMEOUT=>5
    ]);
    $q = $pdo->prepare("SELECT i.file_sha256, i.sheet_no, i.excel_row, i.excel_cell,
                               i.source_status, sh.sheet_name, r.cells_json
                        FROM hw24_excel_identifiers i
                        INNER JOIN hw24_excel_rows r
                          ON r.file_sha256=i.file_sha256 AND r.sheet_no=i.sheet_no AND r.excel_row=i.excel_row
                        LEFT JOIN hw24_excel_sheets sh
                          ON sh.file_sha256=i.file_sha256 AND sh.sheet_no=i.sheet_no
                        WHERE i.identifier_type='EAN' AND i.identifier_value=?
                        ORDER BY CASE WHEN i.sheet_no=1 THEN 0 ELSE 1 END, i.sheet_no, i.excel_row
                        LIMIT 60");
    $q->execute([$ean]);
    $rows = $q->fetchAll();
    $items = [];
    $seen = []; $headerCache = [];
    foreach ($rows as $r) {
        $id = $r['file_sha256'].':'.$r['sheet_no'].':'.$r['excel_row'];
        if (isset($seen[$id])) continue;
        $seen[$id] = true;
        $sheet = (int)$r['sheet_no'];
        $key = $r['file_sha256'].':'.$sheet;
        if (!isset($headerCache[$key])) {
            $headerCache[$key] = excelHeaderMap($pdo, $r['file_sha256'], $sheet);
        }
        $header = $headerCache[$key];
        $doc = json_decode($r['cells_json'], true);
        $fields = [];
        foreach (($doc['cells'] ?? []) as $cell) {
            if (!is_array($cell) || count($cell) < 5) continue;
            $value = excelCellValue($cell);
            if ($value === null) continue;
            $coord = (string)$cell[0];
            $column = excelColumn($coord);
            $label = $header['labels'][$column] ?? null;
            $fields[] = [
                'cell'=>$coord,
                'column'=>$column,
                'label'=>$label !== null ? (string)$label : 'Spalte '.$column,
                'header_verified'=>$label !== null,
                'value'=>trimCellValue($value)
            ];
            if (count($fields) >= 120) break;
        }
        $items[] = [
            'sheet_no'=>$sheet,
            'sheet_name'=>(string)($r['sheet_name'] ?? 'Tabellenblatt '.$sheet),
            'header_row'=>(int)$header['row'],
            'excel_row'=>(int)$r['excel_row'],
            'source_status'=>(string)$r['source_status'],
            'cells'=>$fields
        ];
        if (count($items) >= 12) break;
    }
    respond(200, [
        'ok'=>true, 'ean'=>$ean, 'gtin_valid'=>validGtIn($ean),
        'historical'=>true, 'matches'=>$items, 'total_returned'=>count($items),
        'limited'=>count($rows) >= 60,
        'note'=>'Historische Excel-Daten, nicht als aktuelle JTL-Daten oder bestätigte Produkte behandeln'
    ]);
} catch (Throwable $e) {
    error_log('HW24 historical lookup failed: ' . $e->getMessage());
    respond(503, ['ok'=>false,'error'=>'database_unavailable']);
}
