<?php
declare(strict_types=1);
namespace App\Domain\Pricing;
use InvalidArgumentException;

/**
 * Creates a transparent *calculated* gross view ONLY when the corresponding
 * JTL/customer-group/tax-country tax rate and its source have been confirmed.
 * Does NOT imply JTL stores this gross value as a native tArtikel column.
 */
final class VerifiedGrossDeriver
{
    public static function forJtl(
        string $net, string $ratePercent, string $taxSource, string $priceSource,
        string $observedAt, string $taxCountry, string $customerGroup
    ): ChannelPrice {
        if (!preg_match('/^(?:0|[1-9][0-9]{0,9})(?:\.[0-9]{1,4})?$/D',$net) ||
            !preg_match('/^(?:0|[1-9][0-9]?|100)(?:\.[0-9]{1,2})?$/D',$ratePercent) ||
            (float)$ratePercent>100 ||
            trim($taxSource)==='' || trim($priceSource)==='' ||
            trim($observedAt)==='' || !preg_match('/^[A-Z]{2}$/D',$taxCountry) ||
            trim($customerGroup)==='') {
            throw new InvalidArgumentException('Verified net, tax, region, time and customer-group sources required');
        }
        // Integer arithmetic for standard euro-cent rounding (half up).
        $parts=explode('.',$net,2);
        $net4=((int)$parts[0]*10000)+(int)str_pad($parts[1]??'',4,'0');
        $rateParts=explode('.',$ratePercent,2);
        $rateBasis=(int)$rateParts[0]*100+(int)str_pad($rateParts[1]??'',2,'0');
        $cent=intdiv($net4*(10000+$rateBasis)+500000,1000000);
        $gross=intdiv($cent,100).'.'.str_pad((string)($cent%100),2,'0',STR_PAD_LEFT);
        return new ChannelPrice(
            channel:'jtl',type:'standard',currency:'EUR',gross:$gross,net:$net,
            status:'derived',source:'JTL Netto: '.$priceSource.'; verifizierter Steuersatz: '.$taxSource,
            observedAt:$observedAt,taxCountry:$taxCountry,customerGroup:$customerGroup
        );
    }
}
