<?php
declare(strict_types=1);
namespace App\Domain\Pricing;
use InvalidArgumentException;

/**
 * Presentation-only projection. Channel and price kind never overwrite one another.
 * A missing channel remains explicitly unavailable; all values keep exact source.
 */
final class PriceBoard
{
    /** @param ChannelPrice[] $observations */
    public function build(array $observations): array
    {
        $rows=[];
        foreach ($observations as $entry) {
            if (!$entry instanceof ChannelPrice) throw new InvalidArgumentException('PriceBoard expects ChannelPrice records');
            $row=$entry->toArray();
            // Different accounts/markets and offer types must never collapse into one "price".
            $key=implode('|', [$entry->channel,$entry->type,$entry->marketplace,
                $entry->account,$entry->customerGroup,$entry->offerId]);
            $rows[$key][]=$row;
        }
        foreach ($rows as &$list) {
            usort($list,static fn(array $a,array $b):int =>
                strcmp($b['observed_at']??'', $a['observed_at']??''));
        }
        unset($list);
        $out=[];
        foreach (['jtl','shopify','amazon','ebay'] as $channel) {
            $observed=[];
            foreach($rows as $list) if($list[0]['channel']===$channel) $observed=array_merge($observed,$list);
            $out[$channel]=$observed?:[[
                'channel'=>$channel,'price_type'=>null,'currency'=>null,'gross'=>null,'net'=>null,
                'status'=>'unavailable','source'=>null,'observed_at'=>null,
                'message'=>'Keine verifizierte Preisquelle angebunden; kein Preis erfunden',
            ]];
        }
        return $out;
    }
}
