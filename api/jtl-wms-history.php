<?php
declare(strict_types=1);
/**
 * Explicit WMS integration status until a verified, read-only SQL Server
 * movement-history source and its MariaDB mirror have been implemented.
 * Previously, the UI called a missing endpoint and showed a generic error.
 * This DOES NOT claim to expose live WMS stock or movements.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
if (($_SERVER['REQUEST_METHOD']??'')!=='GET') {
 http_response_code(405);header('Allow: GET');
 echo json_encode(['ok'=>false,'error'=>'method_not_allowed']);exit;
}
$auth=$_SERVER['PHP_AUTH_USER']??$_SERVER['REMOTE_USER']??$_SERVER['REDIRECT_REMOTE_USER']??'';
if ($auth==='') {
 http_response_code(403);
 echo json_encode(['ok'=>false,'error'=>'site_authentication_required']);exit;
}
if (($_SERVER['HTTPS']??'')!=='on'&&($_SERVER['HTTP_X_FORWARDED_PROTO']??'')!=='https') {
 http_response_code(403);
 echo json_encode(['ok'=>false,'error'=>'https_required']);exit;
}
$article=(string)($_GET['article_id']??'');
$offset=filter_var($_GET['offset']??0,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>1000000]]);
if (!preg_match('/^[1-9][0-9]{0,17}$/D',$article)||$offset===false) {
 http_response_code(400);echo json_encode(['ok'=>false,'error'=>'invalid_query']);exit;
}
echo json_encode([
 'ok'=>true,'status'=>'not_synchronized','article_id'=>$article,
 'total'=>0,'offset'=>(int)$offset,'rows'=>[],
 'source'=>'WMS movement feed not configured',
 'next'=>'verify_sql_server_wms_views_and_configure_read_only_mirror',
 'note'=>'Es ist keine WMS-Bewegungshistorie synchronisiert. Keine Lagerbewegungen werden hier erfunden.'
],JSON_UNESCAPED_UNICODE);
