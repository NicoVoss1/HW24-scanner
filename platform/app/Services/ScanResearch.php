<?php
declare(strict_types=1);
namespace App\Services;

use App\Domain\Amazon\OfferEvidence;

/** Explicit operator-initiated research; never writes a draft or calls paid AI. */
final class ScanResearch
{
    public const FIELDS=[
        'Artikelnummer','GTIN','HAN','ASIN','Artikelname','Hersteller','Artikeltyp',
        'Warengruppe','Kategorie Ebene 1','Kategorie Ebene 2','Kategorie Ebene 3',
        'Steuerklasse','Herkunftsland','Währung','Kosten','Netto-VK','Brutto-VK',
        'Lieferant','Artikelnummer (Lieferant)','Netto-EK in Standardwährung',
        'Kurzbeschreibung','Beschreibung','Titel-Tag (SEO)','Meta-Description (SEO)','URL-Pfad','Anmerkung',
        'Inhalt/Menge','Maßeinheit','Artikelgewicht','Versandgewicht','Versandklasse','Lieferstatus',
        'Artikel aktiv','Bestandsführung aktiv','Überverkäufe möglich','Stückzahl teilbar',
        'Zusätzliche Bearbeitungszeit','Beschaffungszeit automatisch ermitteln',
        'Beschaffungszeit manuell setzen','Top-Artikel','Neu im Sortiment',
    ];
    public const REQUIRED=['GTIN','Artikelname','Hersteller','Warengruppe','Steuerklasse'];

    private static function txt(mixed $v,int $limit=500): string
    {
        return is_scalar($v)?mb_substr(trim((string)$v),0,$limit):'';
    }

    /** Report pack evidence separately. Missing pack quantity never means one. */
    public static function packEvidence(array $item,string $title): array
    {
        $units=[];
        $attrs=is_array($item['attributes']??null)?$item['attributes']:[];
        foreach(['number_of_items','item_package_quantity','package_quantity'] as $key){
            foreach(is_array($attrs[$key]??null)?$attrs[$key]:[] as $entry){
                $v=is_array($entry)?($entry['value']??null):null;
                if(is_numeric($v)&&(float)$v===(float)(int)$v&&(int)$v>=1&&(int)$v<=10000)$units[(int)$v]=true;
            }
        }
        if(preg_match_all('/\b(\d{1,3})\s*(?:x|×)\s*\d+(?:[,.]\d+)?\s*(?:ml|l|g|kg)\b/iu',$title,$m))
            foreach($m[1] as $n)if((int)$n>=1)$units[(int)$n]=true;
        if(preg_match_all('/\b(\d{1,3})\s*(?:er[\s-]*(?:pack|paket|set)|[- ]?pack|[- ]?stück)\b/iu',$title,$m))
            foreach($m[1] as $n)if((int)$n>=1)$units[(int)$n]=true;
        if(preg_match('/\b(?:doppelpack|zweierpack|duopack|twin[\s-]*pack)\b/iu',$title))$units[2]=true;
        if(preg_match('/\b(?:dreierpack|triple[\s-]*pack)\b/iu',$title))$units[3]=true;
        if(preg_match('/\b(?:einzelpack|einzelstück|single[\s-]*pack)\b/iu',$title))$units[1]=true;
        $values=array_keys($units);
        $kind=count($values)>1?'conflict':($values===[]?'unknown':($values[0]===1?'single':'multipack'));
        return ['kind'=>$kind,'units'=>count($values)===1?$values[0]:null];
    }

    public static function research(string $ean,LegacyReadBridge $bridge): array
    {
        $j=$bridge->fetch('jtl.article',['q'=>$ean]);
        $jraw=is_array($j['data']??null)?$j['data']:[];
        $matches=is_array($jraw['matches']??null)?$jraw['matches']:[];
        $status=($j['ok']??false)&&($jraw['jtl_status']??null)==='current_snapshot'
            ?match(count($matches)){0=>'new_candidate',1=>'existing',default=>'multiple'}:'unverified';
        // Reuse catalog endpoint; it already handles archive/cache and SP-API.
        $a=$bridge->fetch('amazon.catalog',['ean'=>$ean]);
        $raw=is_array($a['data']??null)?$a['data']:[];
        $items=[];
        if(($a['ok']??false)===true){
            foreach(array_slice(is_array($raw['items']??null)?$raw['items']:[],0,12) as $i){
                if(!is_array($i))continue;
                $asin=strtoupper(self::txt($i['asin']??null,10));
                if(!preg_match('/^[A-Z0-9]{10}$/D',$asin))continue;
                $exact=false;
                foreach(is_array($i['identifiers']??null)?$i['identifiers']:[] as $group){
                    if(!is_array($group))continue;
                    foreach(is_array($group['identifiers']??null)?$group['identifiers']:[] as $id){
                        if(!is_array($id)||!in_array(strtoupper((string)($id['identifierType']??'')),['EAN','UPC','GTIN'],true))continue;
                        $v=(string)($id['identifier']??'');
                        if(preg_match('/^(?:\d{8}|\d{12}|\d{13}|\d{14})$/D',$v)&&str_pad($v,14,'0',STR_PAD_LEFT)===str_pad($ean,14,'0',STR_PAD_LEFT))$exact=true;
                    }
                }
                $title=self::txt($i['title']??null);
                $pack=self::packEvidence($i,$title);
                $items[]=['asin'=>$asin,'title'=>$title,'brand'=>self::txt($i['brand']??null,180),
                    'manufacturer'=>self::txt($i['manufacturer']??null,180),'gtin_match'=>$exact,
                    'pack_kind'=>$pack['kind'],'pack_units'=>$pack['units']];
            }
        }
        return ['jtl'=>$status,'jtl_http'=>$j['http_status']??null,
            'jtl_name'=>$status==='existing'?self::txt($matches[0]['artikelname']??null):'',
            'amazon_ok'=>($a['ok']??false)===true,'http'=>$a['http_status']??null,
            'source'=>self::txt($raw['source']??null,30),'snapshot_id'=>(int)($raw['snapshot_id']??0),
            'at'=>self::txt($raw['fetched_at_utc']??null,40),'items'=>$items,
            'more'=>($raw['has_next_page']??false)===true,'error'=>self::txt($raw['error']??null,100)];
    }

    public static function pricing(string $asin,LegacyReadBridge $bridge): array
    {
        $r=$bridge->fetch('amazon.pricing',['asin'=>$asin]);
        $raw=is_array($r['data']??null)?$r['data']:[];
        $board=OfferEvidence::present($raw);
        return ['ok'=>($r['ok']??false)===true,'http'=>$r['http_status']??null,'asin'=>$asin,
            'buy_box'=>$board['buy_box'],'lowest'=>$board['lowest_summary']??$board['lowest_visible'],
            'at'=>$board['retrieved_at'],'offers'=>$board['total_offers']];
    }
}
