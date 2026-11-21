<?php
declare(strict_types=1);
/* Read-only JTL mirror query. Never declares "new" without a fresh, complete sync.
   The actual SQL Server is internal and will later be read by a dedicated Windows synchroniser. */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
function out(int $status, array $data): void {
  http_response_code($status);
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
  exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
  header('Allow: GET');
  out(405,['ok'=>false,'error'=>'method_not_allowed']);
}
// Search by EAN/GTIN, JTL article number or HAN. Parameterized query below.
$identifier=trim((string)($_GET['q'] ?? $_GET['ean'] ?? ''));
if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N}._\/\-]{1,63}$/uD',$identifier)) {
  out(400,['ok'=>false,'error'=>'invalid_identifier_format']);
}
$path=dirname(__DIR__,2).'/hw24_private/db.php';
if (!is_file($path)) out(200,['ok'=>true,'jtl_status'=>'not_configured','matches'=>[]]);
try {
  $cfg=require $path;
  if (!is_array($cfg)||empty($cfg['user'])||!array_key_exists('password',$cfg)) {
    out(200,['ok'=>true,'jtl_status'=>'not_configured','matches'=>[]]);
  }
  $pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=hw24_artikelmanager;charset=utf8mb4',
    $cfg['user'],$cfg['password'],[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false,
      PDO::ATTR_TIMEOUT=>5
    ]);
  $t=$pdo->query("SELECT COUNT(*) FROM information_schema.TABLES
                   WHERE TABLE_SCHEMA=DATABASE()
                     AND TABLE_NAME IN ('hw24_jtl_sync_meta','hw24_jtl_articles')");
  if ((int)$t->fetchColumn()!==2) {
    out(200,['ok'=>true,'jtl_status'=>'not_configured','matches'=>[]]);
  }
  $q=$pdo->prepare('SELECT source_id,status,last_completed_utc,last_row_count,
                            TIMESTAMPDIFF(MINUTE,last_completed_utc,UTC_TIMESTAMP()) AS age_minutes
                     FROM hw24_jtl_sync_meta
                     WHERE status=? AND last_completed_utc IS NOT NULL
                     ORDER BY last_completed_utc DESC LIMIT 1');
  $q->execute(['ready']);
  $meta=$q->fetch();
  if (!$meta) {
    out(200,['ok'=>true,'jtl_status'=>'not_connected','matches'=>[]]);
  }
  $age=(int)$meta['age_minutes'];
  // 15-minute Windows Task Scheduler sync plus a 30-minute buffer for occasional delayed runs.
  // Fail closed after 45 minutes: no definitive new-article decision from stale JTL data.
  $fresh=$age>=0 && $age<=45;
  if (!$fresh) {
    out(200,['ok'=>true,'jtl_status'=>'stale','snapshot_at'=>$meta['last_completed_utc'],
      'age_minutes'=>$age,'matches'=>[]]);
  }
  $q=$pdo->prepare('SELECT jtl_article_id,artikelnummer,ean,han,asin,artikelname,hersteller,kurzbeschreibung,beschreibung,seo_titel,seo_beschreibung,
                           netto_vk,netto_ek,letzter_ek,letzter_ek_datum,amazon_vk,ebay_preis,bestand,synced_at_utc,raw_json
                    FROM hw24_jtl_articles
                    WHERE source_id=? AND (ean=? OR han=? OR artikelnummer=?)
                    ORDER BY jtl_article_id LIMIT 16');
  $q->execute([$meta['source_id'],$identifier,$identifier,$identifier]);
  $matches=$q->fetchAll();
  $limited=count($matches)>15;
  if ($limited) $matches=array_slice($matches,0,15);
  foreach($matches as &$article){
    $raw=json_decode((string)($article['raw_json']??''),true);
    $article['steuerklasse_id']=is_array($raw)?($raw['kSteuerklasse']??null):null;
    $article['hersteller_id']=is_array($raw)?($raw['kHersteller']??null):null;
    // Exact field names from the user's SQL Server 2017 tArtikel column listing.
    // Names/classes/stock availability are NOT guessed from foreign-key IDs.
    $safe=['kHersteller','kWarengruppe','kVersandklasse','kLieferStatus',
      'cAktiv','cTopArtikel','cTeilbar','cLagerAktiv','cLagerKleinerNull','cNeu',
      'cAnmerkung','nBearbeitungszeit','nLiefertageWennAusverkauft',
      'nAutomatischeLiefertageberechnung','kBenutzerLetzteAenderung',
      'dMod','nLagerbestand','kSteuerklasse','fEKNetto','fLetzterEK',
      'kMassEinheit','fMassMenge','kGrundPreisEinheit','fGrundpreisMenge','nVPE','fVPEWert','kVPEEinheit','kVerkaufsEinheit','fPackeinheit',
      'fAmazonVK','fEbayPreis','fVKNetto','cASIN'];
    $fields=[];
    if(is_array($raw)){
      foreach($safe as $key){
        if(array_key_exists($key,$raw)) $fields[$key]=$raw[$key];
      }
    }
    $article['jtl_source_fields']=$fields;
    // These optional related records come only from an explicitly verified,
    // admin-controlled, read-only Windows SQL source mapping. A missing
    // mapping is not evidence that a JTL field/table does not exist.
    $related=is_array($raw)?($raw['HW24_RelatedSources']??null):null;
    $article['related_sources']=is_array($related)?$related:[];
    // Only resolve one-to-one verified source values, never arbitrary rows
    // from supplier or marketplace collections. Zero stays a source value
    // and must not be mistaken for an actual purchase transaction.
    $singleValue=static function(string $source,string $column) use ($related): ?string {
      if (!is_array($related) || !isset($related[$source]) ||
          !is_array($related[$source]) || !empty($related[$source]['truncated'])) return null;
      $rows=$related[$source]['rows']??null;
      if (!is_array($rows) || count($rows)!==1 || !is_array($rows[0])) return null;
      $v=$rows[0][$column]??null;
      return ($v===null||!is_scalar($v))?null:(string)$v;
    };
    // Verified optional values emitted by the Windows JTL sync into raw_json.
    // No invented gross price: JTL may calculate it using tax zone/class/group prices.
    // These keys must be populated from a verified Windows SQL mapping.
    $gross=is_array($raw)?($raw['HW24_VKBrutto']??null):null;
    $grossSource=is_array($raw)?($raw['HW24_VKBruttoQuelle']??null):null;
    $article['brutto_vk']=is_scalar($gross)&&preg_match('/^[0-9]{1,10}(?:\\.[0-9]{1,4})?$/D',(string)$gross)
        &&(float)$gross>0&&is_string($grossSource)&&trim($grossSource)!==''
      ?(string)$gross:null;
    $article['brutto_vk_source']=$article['brutto_vk']===null?null:substr(trim($grossSource),0,200);
    $article['waehrung']=is_array($raw)&&isset($raw['HW24_Waehrung'])&&is_scalar($raw['HW24_Waehrung'])
       ?trim((string)$raw['HW24_Waehrung']):null;
    $article['artikeltyp']=is_array($raw)&&isset($raw['HW24_Artikeltyp'])&&is_scalar($raw['HW24_Artikeltyp'])
       ?trim((string)$raw['HW24_Artikeltyp']):null;
    $article['kosten']=$singleValue('kosten','fArtikelkosten');
    $article['kosten_source']=$article['kosten']!==null?'Artikel.tArtikelkosten.fArtikelkosten':null;
    $article['warengruppe_name']=$singleValue('warengruppe','cName');
    // Prefer the authoritative kWarengruppe -> tWarengruppe resolution made
    // during the Windows snapshot, independent of optional related-source maps.
    $fromCore=is_array($raw)?($raw['HW24_WarengruppeName']??null):null;
    if (is_scalar($fromCore) && trim((string)$fromCore)!=='') {
      $article['warengruppe_name']=trim((string)$fromCore);
    }
    $article['warengruppe_id']=is_array($raw)?($raw['kWarengruppe']??null):null;
    $article['warengruppe_source']=$article['warengruppe_name']!==null
      ?(is_scalar($fromCore)&&trim((string)$fromCore)!==''
         ?'dbo.tArtikel.kWarengruppe -> dbo.tWarengruppe.cName (Windows-Sync)'
         :'dbo.tWarengruppe.cName (geprüfte Zusatzquelle)')
      :null;
    $article['warengruppe_unresolved']=!empty($article['warengruppe_id']) &&
      ($article['warengruppe_name']===null || $article['warengruppe_name']==='');
    $article['steuerklasse_name']=$singleValue('steuerklasse','cName');
    $article['versandklasse_name']=$singleValue('versandklasse','cName');
    $article['bestand_verfuegbar']=$singleValue('bestand','fVerfuegbar');
    // ASIN candidates: linked to THIS JTL article only. Do not assume EAN:ASIN
    // is one-to-one. Multiple valid candidates stay separate and require review.
    $candidateValues=[];
    $addCandidate=static function($input,string $origin,array $context=[]) use (&$candidateValues): void {
      if(!is_scalar($input)) return;
      $asin=strtoupper(trim((string)$input));
      if(!preg_match('/^[A-Z0-9]{10}$/D',$asin)) return;
      if(!isset($candidateValues[$asin])) $candidateValues[$asin]=[
        'asin'=>$asin,'sources'=>[],'review_required'=>true
      ];
      $candidateValues[$asin]['sources'][]=array_merge(['name'=>$origin],$context);
    };
    $addCandidate($article['asin']??null,'JTL-Artikelstamm');
    if(is_array($related)){
      $asinRows=$related['artikel_asin']['rows']??[];
      if(is_array($asinRows)){
        foreach(array_slice($asinRows,0,8) as $row){
          if(is_array($row)) $addCandidate($row['cAsin']??null,'JTL-Artikel-ASIN-Zuordnung');
        }
      }
      $offerRows=$related['amazon_angebote']['rows']??[];
      if(is_array($offerRows)){
        foreach(array_slice($offerRows,0,8) as $row){
          if(!is_array($row)) continue;
          $addCandidate($row['asin']??null,'JTL-Amazon-Angebot',[
            'seller_sku'=>isset($row['seller_sku'])?(string)$row['seller_sku']:null,
            'plattform'=>isset($row['plattform'])?(string)$row['plattform']:null
          ]);
        }
      }
    }
    $article['asin_candidates']=array_values($candidateValues);
    // Absence of a configured source does NOT mean the Amazon catalog is complete.
    $article['asin_sources_truncated']=is_array($related) &&
      (!empty($related['artikel_asin']['truncated']) ||
       !empty($related['amazon_angebote']['truncated']));
    $article['matched_on']=[];
    foreach (['ean','han','artikelnummer'] as $matchKey) {
      if (trim((string)($article[$matchKey]??''))===$identifier) $article['matched_on'][]=$matchKey;
    }
    unset($article['raw_json']); // internal raw DB fields are never exposed through the read API
  }
  unset($article);
  out(200,[
    'ok'=>true,'jtl_status'=>'current_snapshot',
    'snapshot_at'=>$meta['last_completed_utc'],'age_minutes'=>$age,
    'matches'=>$matches,
    'limited'=>$limited,
    'note'=>'JTL-Spiegelstand aus der letzten abgeschlossenen Synchronisation. Suche über EAN/GTIN, HAN oder Artikelnummer; kein direkter Schreibzugriff auf JTL.'
  ]);
} catch (Throwable $e) {
  error_log('HW24 JTL mirror lookup failed: '.$e->getMessage());
  out(200,['ok'=>true,'jtl_status'=>'unavailable','matches'=>[]]);
}
