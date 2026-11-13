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
    $q = $pdo->prepare("SELECT i.sheet_no, i.excel_row, i.excel_cell, i.source_status, r.cells_json
                        FROM hw24_excel_identifiers i
                        INNER JOIN hw24_excel_rows r
                         ON r.file_sha256=i.file_sha256 AND r.sheet_no=i.sheet_no AND r.excel_row=i.excel_row
                        WHERE i.identifier_type='EAN' AND i.identifier_value=?
                        ORDER BY i.sheet_no, i.excel_row, i.excel_cell LIMIT 30");
    $q->execute([$ean]);
    $rows = $q->fetchAll();
    $items = [];
    $seen = [];
    foreach ($rows as $r) {
        $id = $r['sheet_no'] . ':' . $r['excel_row'];
        if (isset($seen[$id])) continue;
        $seen[$id] = true;
        $doc = json_decode($r['cells_json'], true);
        $fields = [];
        foreach (($doc['cells'] ?? []) as $cell) {
            if (!is_array($cell) || count($cell) < 5) continue;
            $v = $cell[4] ?? $cell[3] ?? null; // historical cached Excel value; never recalculate formula
            if ($v === null || $v === '') continue;
            if (!is_scalar($v)) continue;
            $fields[] = ['cell'=>(string)$cell[0], 'value'=>mb_substr((string)$v,0,400)];
            if (count($fields) >= 85) break;
        }
        $items[] = [
            'sheet_no'=>(int)$r['sheet_no'],
            'excel_row'=>(int)$r['excel_row'],
            'source_status'=>(string)$r['source_status'],
            'cells'=>$fields
        ];
        if (count($items) >= 12) break;
    }
    respond(200, [
        'ok'=>true, 'ean'=>$ean, 'gtin_valid'=>validGtIn($ean),
        'historical'=>true, 'matches'=>$items, 'total_returned'=>count($items),
        'limited'=>count($rows) >= 30,
        'note'=>'Historische Excel-Daten, nicht als aktuelle JTL-Daten oder bestätigte Produkte behandeln'
    ]);
} catch (Throwable $e) {
    error_log('HW24 historical lookup failed: ' . $e->getMessage());
    respond(503, ['ok'=>false,'error'=>'database_unavailable']);
}
