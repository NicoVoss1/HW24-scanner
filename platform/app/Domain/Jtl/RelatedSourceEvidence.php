<?php
declare(strict_types=1);

namespace App\Domain\Jtl;

use App\Services\HubStockReader;

/**
 * Reuses the exact read-only JTL related_sources contract from the existing
 * HW24 legacy warehouse view. No calculations from movements or reservations.
 */
final class RelatedSourceEvidence
{
    private static function scalar(mixed $value, int $max=180): ?string
    {
        if (!is_scalar($value))return null;
        $text=trim((string)$value);
        return $text===''?null:mb_substr($text,0,$max);
    }

    private static function number(mixed $v): ?string
    {
        $s=self::scalar($v,60);
        return $s!==null&&preg_match('/^-?(?:0|[1-9][0-9]{0,14})(?:\.[0-9]{1,13})?$/D',$s)
            ?$s:null;
    }

    private static function blocked(mixed $v): bool
    {
        $s=mb_strtolower(self::scalar($v,12)??'');
        return in_array($s,['1','true','ja','y'],true);
    }

    public static function present(array $article): array
    {
        $related=is_array($article['related_sources']??null)?$article['related_sources']:[];
        $stockSource=is_array($related['bestand']??null)?$related['bestand']:[];
        $stockRows=is_array($stockSource['rows']??null)?$stockSource['rows']:[];
        $stockCount=count($stockRows);
        $stock=$stockCount===1 && array_is_list($stockRows) && is_array($stockRows[0]) && in_array($stockSource['truncated']??null,[null,false,0,'false','0'],true)
            ?$stockRows[0]:[];

        $wms=is_array($related['wms_lagerplaetze']??null)?$related['wms_lagerplaetze']:[];
        $wmsConfirmed=($wms['confirmed']??false)===true;
        $wmsRows=$wmsConfirmed&&is_array($wms['rows']??null)?$wms['rows']:[];
        $bins=[];
        foreach(array_slice($wmsRows,0,1000) as $row){
            if(!is_array($row))continue;
            $bins[]=[
                'warehouse'=>self::scalar($row['warenlager_name']??null,180)
                    ??('Warenlager-ID '.(self::scalar($row['warenlager_id']??null,30)??'unbekannt')),
                'warehouse_id'=>self::scalar($row['warenlager_id']??null,32),
                'bin'=>self::scalar($row['lagerplatz']??null,180)??'Lagerfach unbekannt',
                'bin_id'=>self::scalar($row['lagerplatz_id']??null,32),
                'quantity'=>HubStockReader::number($row['menge']??null),
                'lock_known'=>array_key_exists('gesperrt',$row)&&$row['gesperrt']!==null,
                'shipping_lock_known'=>array_key_exists('auslieferung_gesperrt',$row)&&$row['auslieferung_gesperrt']!==null,
                'locked'=>self::blocked($row['gesperrt']??null),
                'shipping_locked'=>self::blocked($row['auslieferung_gesperrt']??null),
                'last_movement'=>self::scalar($row['letzte_bewegung_utc']??$row['letzte_bewegung']??null,50),
                'last_source'=>self::scalar($row['letzte_bewegung_quelle']??null,90),
                'type_id'=>self::scalar($row['lagerplatztyp_id']??null,32),
                'status_id'=>self::scalar($row['status_id']??null,32),
                'comment'=>self::scalar($row['lagerplatz_hinweis']??null,250),
            ];
        }

        $amazonSource=is_array($related['amazon_angebote']??null)?$related['amazon_angebote']:[];
        $amazonVerified=($amazonSource['confirmed']??false)===true;
        $jtlOffers=[];
        if($amazonVerified&&is_array($amazonSource['rows']??null)){
            foreach(array_slice($amazonSource['rows'],0,30) as $row){
                if(!is_array($row))continue;
                $asin=strtoupper(self::scalar($row['asin']??null,10)??'');
                $jtlOffers[]=[
                    'asin'=>preg_match('/^[A-Z0-9]{10}$/D',$asin)?$asin:null,
                    'sku'=>self::scalar($row['seller_sku']??null,100),
                    'marketplace'=>self::scalar($row['plattform']??null,80),
                    'jtl_price'=>self::number($row['preis_angebot']??null),
                    'jtl_quantity'=>self::number($row['menge_angebot']??null),
                    'fulfillment'=>self::scalar($row['fulfillment']??null,80),
                ];
            }
        }

        return [
            'stock'=>[
                'source_confirmed'=>$stockCount===1&&!empty($stock),
                'total'=>HubStockReader::number($stock['fLagerbestand']??null),
                'available'=>HubStockReader::number($stock['fVerfuegbar']??null),
                'in_orders'=>HubStockReader::number($stock['fInAuftraegen']??null),
                'incoming'=>HubStockReader::number($stock['fZulauf']??null),
                // No derived reserved = total - available: not a true JTL source.
                'reservations'=>null,
            ],
            'wms'=>[
                'confirmed'=>$wmsConfirmed,
                'truncated'=>!in_array($wms['truncated']??null,[null,false,0,'false','0'],true) || count($wmsRows)>1000 || count($bins)!==count($wmsRows),
                'count'=>count($bins),
                'positive_bins'=>count(array_filter($bins,static fn(array $r)=>
                    $r['quantity']!==null&&(float)$r['quantity']>0)),
                'locked_bins'=>count(array_filter($bins,static fn(array $r)=>
                    $r['locked']||$r['shipping_locked'])),
                'bins'=>$bins,
            ],
            'amazon_jtl'=>[
                'confirmed'=>$amazonVerified,
                'truncated'=>($amazonSource['truncated']??false)===true,
                'offers'=>$jtlOffers,
                // These JTL rows are not verified Amazon SP-API live listings.
            ],
        ];
    }
}
