<?php
declare(strict_types=1);
require_once __DIR__.'/../api/hw24-quality-rules.php';
function ok(bool $condition,string $message): void {
 if(!$condition){fwrite(STDERR,"FAIL: $message\n");exit(1);}
}
function flags(array $row): array {return hw24QualityEvaluate($row)['issues'];}
$base=[
 'artikelnummer'=>'4711','ean'=>'4006381333931','han'=>'ABC4711',
 'artikelname'=>'Beispiel Haarfarbe 60 ml','hersteller'=>'Hersteller',
 'has_short'=>1,'has_description'=>1,'warengruppe_id'=>'4',
 'warengruppe_name'=>'Haarfarben','lieferstatus_id'=>'2',
 'bestand'=>'12.0000','netto_ek'=>'1.2500','letzter_ek'=>'1.5000'
];
ok(hw24QualityGtinValid('4006381333931'),'known GTIN-13 checksum');
ok(!hw24QualityGtinValid('4006381333932'),'reject bad GTIN-13 checksum');
ok(!hw24QualityGtinValid('12345678'),'reject invalid GTIN-8');
ok(!hw24QualityGtinValid('12abc'),'reject alphanumeric GTIN');
ok(count(flags($base))===0,'fully populated sample has no detected technical issue');
$bad=$base;$bad['netto_ek']='0';$bad['letzter_ek']=null;
ok(in_array('stock_without_ek',flags($bad),true),'positive stock but neither EK populated');
ok(hw24QualityEvaluate($bad)['critical_count']===1,'missing stock EK is high priority');
$bad['bestand']='0';
ok(!in_array('stock_without_ek',flags($bad),true),'zero stock does not trigger stocked-EK rule');
$bad['bestand']='1';$bad['letzter_ek']='3.50';
ok(!in_array('stock_without_ek',flags($bad),true),'last EK is a plausible field indication');
$bad=$base;$bad['ean']='4006381333932';
ok(in_array('gtin_invalid',flags($bad),true),'reject wrong checksum');
$bad['ean']=null;
ok(in_array('gtin_missing',flags($bad),true),'missing EAN is a review item not replacement with HAN');
$bad=$base;$bad['hersteller']='';$bad['han']=null;
ok(in_array('manufacturer_missing',flags($bad),true),'manufacturer review');
ok(in_array('han_missing',flags($bad),true),'HAN independent from GTIN');
$bad=$base;$bad['warengruppe_name']='';
ok(in_array('group_unresolved',flags($bad),true),'JTL FK without resolution is distinct from missing group');
ok(!in_array('group_missing',flags($bad),true),'existing JTL group FK not treated as empty');
$bad['warengruppe_id']='0';
ok(in_array('group_missing',flags($bad),true),'unassigned JTL group');
$bad=$base;$bad['lieferstatus_id']=null;$bad['has_short']=0;$bad['has_description']=0;
ok(in_array('delivery_unknown',flags($bad),true),'undocumented delivery status');
ok(in_array('short_missing',flags($bad),true),'no short text');
ok(in_array('description_missing',flags($bad),true),'no long text');
ok(!array_key_exists('responsible_person',hw24QualityCatalog()),
 'missing unmirrored legal person MUST NOT be asserted as an audit result');
fwrite(STDOUT,"HW24 Quality V4.23 rules: PASS\n");