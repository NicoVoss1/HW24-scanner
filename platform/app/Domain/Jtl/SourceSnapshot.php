<?php
declare(strict_types=1);

namespace App\Domain\Jtl;

/**
 * Read-only projection of a SINGLE article from the legacy source endpoint.
 * Full original data lives in the existing JTL MariaDB mirror; do not copy
 * every SQL row/column to Laravel's own database on every sync.
 */
final class SourceSnapshot
{
    public static function present(array $payload): array
    {
        if (($payload['ok']??false)!==true ||
            ($payload['status']??null)!=='current_snapshot') {
            return ['ok'=>false,'fields'=>[],'prices'=>[],
                'reason'=>'JTL-Snapshot nicht aktuell oder nicht bestätigt'];
        }
        $rows=[];
        $raw=is_array($payload['fields']??null)?$payload['fields']:[];
        foreach(array_slice($raw,0,100) as $row){
            if(!is_array($row))continue;
            $source=self::limited($row['source']??null,140);
            $field=self::limited($row['field']??null,128);
            if($source===''||$field===''||self::secretField($field))continue;
            $value=$row['value']??null;
            if($value!==null&&!is_scalar($value))$value=null;
            $rows[]=[
                'source'=>$source,
                'field'=>$field,
                'label'=>self::limited($row['label']??null,180),
                'value'=>$value===null?null:self::limited($value,8192),
                'row'=>max(0,min(10000,(int)($row['row']??0))),
                'truncated'=>($row['truncated']??false)===true,
            ];
        }
        $prices=is_array($payload['prices']??null)?$payload['prices']:[];
        $priceRows=[
            self::priceRow('JTL-Wawi','Standard-Netto','jtl_net','Netto-VK','jtl_net_source',$prices),
            self::priceRow('JTL-Wawi','Standard-Brutto','jtl_gross','Brutto-VK','jtl_gross_source',$prices),
            self::priceRow('Amazon','JTL Artikelstamm (kein Live-Angebot)','amazon_jtl_value',
                'Steuerinklusion offen','amazon_jtl_source',$prices),
            self::priceRow('eBay','JTL Artikelstamm (kontenübergreifend)','ebay_jtl_value',
                'Steuerinklusion offen','ebay_jtl_source',$prices),
            self::unknown('eBay','www.serverler24.shop'),
            self::unknown('eBay','friseurbedarf24.shop'),
            self::unknown('Shopify','HAIRWORLD24'),
            self::unknown('Shopify','Boosterwelt24'),
        ];
        return [
            'ok'=>true,
            'article_id'=>self::limited($payload['article_id']??null,30),
            'ean'=>self::limited($payload['ean']??null,14),
            'snapshot_at'=>self::limited($payload['snapshot_at']??null,40),
            'field_count'=>max(0,(int)($payload['field_count']??0)),
            'offset'=>max(0,(int)($payload['offset']??0)),
            'has_more'=>($payload['has_more']??false)===true,
            'fields'=>$rows,
            'prices'=>$priceRows,
        ];
    }
    private static function priceRow(
        string $channel,string $account,string $valueKey,string $meaning,string $sourceKey,array $prices
    ): array {
        $raw=$prices[$valueKey]??null;
        $value=is_scalar($raw)&&preg_match('/^-?[0-9]{1,12}(?:\.[0-9]{1,14})?$/D',(string)$raw)
            ? (string)$raw : null;
        $source=self::limited($prices[$sourceKey]??null,200);
        // A JTL tArtikel marketplace field is not a verified live marketplace price.
        // No currency or net/gross conversion without source confirmation.
        return [
            'channel'=>$channel,'account'=>$account,'value'=>$value,
            'meaning'=>$meaning,'currency'=>null,'source'=>$source,
            'status'=>$value===null?'unavailable':'JTL original, kein bestätigtes Live-Angebot',
        ];
    }
    private static function unknown(string $channel,string $label): array
    {
        return [
            'channel'=>$channel,'account'=>$label,'value'=>null,'meaning'=>'unbekannt',
            'currency'=>null,'source'=>null,'status'=>'Konto und Angebotspreis nicht verifiziert'
        ];
    }
    private static function limited(mixed $value,int $max): string
    {
        if(!is_scalar($value))return '';
        return mb_substr((string)$value,0,$max);
    }
    private static function secretField(string $field): bool
    {
        return (bool)preg_match(
            '/(?:password|passwort|secret|token|api.?key|credential|auth|cookie|session|private.?key)/i',
            $field
        );
    }
}
