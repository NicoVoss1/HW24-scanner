<?php
declare(strict_types=1);
/**
 * HW24 read-only JTL quality-rule engine, V4.23 TEST.
 * Only values explicitly supplied by the completed JTL mirror are evaluated.
 * This is a *work queue*, not certification of product/legislative compliance.
 */
function hw24QualityCatalog(): array {
 return [
  'stock_without_ek'=>['label'=>'Bestand vorhanden, EK im Spiegel nicht belegt','severity'=>'critical',
     'hint'=>'Belegten Netto-EK, letzten EK und ggf. Lieferanten-/Kommissionsdaten in JTL prüfen'],
  'gtin_invalid'=>['label'=>'GTIN ungültig / Prüfziffer falsch','severity'=>'critical',
     'hint'=>'EAN/GTIN anhand Herstellerverpackung oder Primärquelle kontrollieren'],
  'name_missing'=>['label'=>'Artikelbezeichnung fehlt','severity'=>'critical',
     'hint'=>'Original-Artikelbezeichnung aus verifizierter Quelle verwenden'],
  'manufacturer_missing'=>['label'=>'Hersteller fehlt','severity'=>'warning',
     'hint'=>'Hersteller-/Marken-Zuordnung und JTL-Referenztabelle prüfen'],
  'gtin_missing'=>['label'=>'GTIN/EAN fehlt','severity'=>'warning',
     'hint'=>'Bei Handelsware GTIN belegen; mögliche Ausnahmen für Dienstleistungen prüfen'],
  'han_missing'=>['label'=>'HAN fehlt','severity'=>'warning',
     'hint'=>'Herstellerartikelnummer belegen, nicht automatisch aus EAN kopieren'],
  'short_missing'=>['label'=>'Kurzbeschreibung fehlt','severity'=>'warning',
     'hint'=>'Inhalt anhand Hersteller- und vorhandener JTL-Informationen prüfen'],
  'description_missing'=>['label'=>'Beschreibung fehlt','severity'=>'warning',
     'hint'=>'Belegten beschreibenden Produkttext erstellen'],
  'group_missing'=>['label'=>'Warengruppe nicht zugeordnet','severity'=>'warning',
     'hint'=>'JTL-Warengruppe passend zur Produktart auswählen'],
  'group_unresolved'=>['label'=>'Warengruppen-ID ohne Klartext','severity'=>'warning',
     'hint'=>'Vorhandene JTL-Fremdschlüssel-Zuordnung im Windows-Spiegel ergänzen'],
  'delivery_unknown'=>['label'=>'Lieferstatus im Spiegel nicht belegt','severity'=>'warning',
     'hint'=>'JTL-Lieferstatus-ID und zugehörige Liefertage anhand tatsächlicher Stammtabelle prüfen']
 ];
}
function hw24QualityGtinValid(string $gtin): bool {
 if(!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$gtin))return false;
 $sum=0;$weight=3;
 for($i=strlen($gtin)-2;$i>=0;$i--){
  $sum+=(int)$gtin[$i]*$weight;
  $weight=$weight===3?1:3;
 }
 return (10-$sum%10)%10===(int)$gtin[strlen($gtin)-1];
}
function hw24QualityFilled($value): bool {
 return is_scalar($value)&&trim((string)$value)!=='';
}
function hw24QualityPositive($value): bool {
 if(!is_scalar($value)||trim((string)$value)==='')return false;
 $n=trim((string)$value);
 return preg_match('/^-?[0-9]+(?:\.[0-9]+)?$/D',$n)===1 && (float)$n>0;
}
function hw24QualityEvaluate(array $article): array {
 $issues=[];
 $add=static function(string $code) use (&$issues): void {$issues[]=$code;};
 $gtin=trim((string)($article['ean']??''));
 if($gtin==='')$add('gtin_missing');
 elseif(!hw24QualityGtinValid($gtin))$add('gtin_invalid');
 if(!hw24QualityFilled($article['artikelname']??null))$add('name_missing');
 if(!hw24QualityFilled($article['hersteller']??null))$add('manufacturer_missing');
 if(!hw24QualityFilled($article['han']??null))$add('han_missing');
 if(!hw24QualityFilled($article['has_short']??null)||
  (string)($article['has_short']??'0')==='0')$add('short_missing');
 if(!hw24QualityFilled($article['has_description']??null)||
  (string)($article['has_description']??'0')==='0')$add('description_missing');
 $groupId=trim((string)($article['warengruppe_id']??''));
 $groupName=trim((string)($article['warengruppe_name']??''));
 if($groupId===''||$groupId==='0'){
  if($groupName==='')$add('group_missing');
 }elseif($groupName==='')$add('group_unresolved');
 $status=trim((string)($article['lieferstatus_id']??''));
 if($status===''||$status==='0'||strtolower($status)==='null')$add('delivery_unknown');
 $hasStock=hw24QualityPositive($article['bestand']??null);
 $hasEk=hw24QualityPositive($article['netto_ek']??null)||
  hw24QualityPositive($article['letzter_ek']??null);
 if($hasStock&&!$hasEk)$add('stock_without_ek');
 $catalog=hw24QualityCatalog();
 $critical=0;
 foreach($issues as $code)if($catalog[$code]['severity']==='critical')$critical++;
 return [
   'issues'=>$issues,'issue_count'=>count($issues),
   'critical_count'=>$critical,
   'has_stock'=>$hasStock,
   'ek_in_mirror'=>$hasEk,
   'severity'=>$critical>0?'critical':(count($issues)>0?'warning':'no_detected_issues')
 ];
}
