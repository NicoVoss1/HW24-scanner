<?php
declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use Throwable;

/**
 * Single pricing request, optional explicit archive dependency.
 * Not registered automatically in the container; caller must provide
 * an authorized archive connection and retention policy. No paid AI.
 */
final class AmazonPriceResearch
{
    public static function fetch(string $asin, LegacyReadBridge $bridge, ?AmazonPriceArchive $archive=null): array
    {
        if (!preg_match('/^[A-Z0-9]{10}$/D',$asin)) {
            throw new InvalidArgumentException('Invalid ASIN');
        }
        $response=$bridge->fetch('amazon.pricing',['asin'=>$asin]);
        $state=['state'=>$archive===null?'disabled':'not_recorded','id'=>null,'inserted'=>false,'history'=>[]];
        if ($archive!==null && ($response['ok']??false)===true
            && ($response['http_status']??null)===200 && is_array($response['data']??null)) {
            try {
                $saved=$archive->record($response['data'],$asin);
                $state=['state'=>'recorded','id'=>$saved['id'],'inserted'=>$saved['inserted'],'history'=>[]];
                // A history read error must not disguise a committed observation.
                try {
                    $state['history']=$archive->latest($asin,2);
                } catch (Throwable) {
                    $state['state']='recorded_history_unavailable';
                }
            } catch (Throwable) {
                // Do not expose DB credentials or provider payload on failure.
                $state['state']='archive_unavailable_or_invalid';
            }
        }
        return ['response'=>$response,'archive'=>$state];
    }
}
