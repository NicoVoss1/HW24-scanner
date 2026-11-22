<?php
declare(strict_types=1);

namespace App\Domain\Amazon;

/**
 * Present the EXISTING legacy api/amazon-pricing.php response, faithfully.
 * Winning the Buy Box is independent of being the cheapest visible offer.
 * No storage of competitor pricing or supplier identity inference.
 */
final class OfferEvidence
{
    private static function txt(mixed $value,int $limit=140): ?string
    {
        if(!is_scalar($value))return null;
        $text=trim((string)$value);
        return $text===''?null:mb_substr($text,0,$limit);
    }

    private static function amount(mixed $value): ?float
    {
        if(!is_numeric($value))return null;
        $float=(float)$value;
        return is_finite($float)&&$float>=0&&$float<10000000?$float:null;
    }

    private static function money(mixed $row): ?array
    {
        if(!is_array($row))return null;
        $amount=self::amount($row['amount']??null);
        $currency=self::txt($row['currency']??null,3);
        if($amount===null||$currency===null||
            !preg_match('/^[A-Z]{3}$/D',$currency))return null;
        return ['amount'=>$amount,'currency'=>$currency];
    }

    public static function present(array $api,array $sellerNames=[]): array
    {
        $offers=[];
        $winnerCount=0;
        foreach(array_slice(is_array($api['offers']??null)?$api['offers']:[],0,20) as $row){
            if(!is_array($row))continue;
            $seller=self::txt($row['seller_id']??null,40);
            if($seller===null||!preg_match('/^[A-Z0-9]{5,40}$/D',$seller))continue;
            $total=self::money($row['total']??null);
            $item=self::money($row['item_price']??null);
            $shipping=self::money($row['shipping']??null);
            $feedback=is_array($row['seller_feedback']??null)?$row['seller_feedback']:[];
            $percent=self::amount($feedback['positive_percent']??null);
            if($percent!==null&&$percent>100)$percent=null;
            $feedbackCount=filter_var($feedback['feedback_count']??null,FILTER_VALIDATE_INT);
            $shippingTime=is_array($row['shipping_time']??null)?$row['shipping_time']:[];
            $shipsFrom=is_array($row['ships_from']??null)?$row['ships_from']:[];
            $isWinner=($row['is_buybox_winner']??false)===true;
            if($isWinner)$winnerCount++;
            $verifiedName=self::txt($sellerNames[$seller]??null,200);
            $offers[]=[
                'seller_id'=>$seller,
                'seller_name'=>$verifiedName,
                'winner'=>$isWinner,
                'eligible'=>($row['is_featured_merchant']??null)===true?true:
                    (($row['is_featured_merchant']??null)===false?false:null),
                'fba'=>($row['is_fba']??null)===true,
                'prime'=>($row['prime']??null)===true?true:
                    (($row['prime']??null)===false?false:null),
                'total'=>$total,
                'item'=>$item,
                'shipping'=>$shipping,
                'feedback_percent'=>$percent,
                'feedback_count'=>$feedbackCount===false?null:$feedbackCount,
                'condition'=>self::txt($row['condition']??null,50),
                'dispatch_min'=>self::amount($shippingTime['min_hours']??null),
                'dispatch_max'=>self::amount($shippingTime['max_hours']??null),
                'availability'=>self::txt($shippingTime['availability']??null,80),
                'ships_from'=>self::txt($shipsFrom['country']??null,3),
            ];
        }

        $buyBox=null;
        foreach(is_array($api['buybox_prices']??null)?$api['buybox_prices']:[] as $row){
            if(!is_array($row))continue;
            $buyBox=self::money($row['landed_price']??null)??self::money($row['listing_price']??null);
            if($buyBox!==null)break;
        }
        $lowestFromSummary=null;
        foreach(is_array($api['lowest_prices']??null)?$api['lowest_prices']:[] as $row){
            if(!is_array($row))continue;
            $price=self::money($row['landed_price']??null)??self::money($row['listing_price']??null);
            if($price!==null&&($lowestFromSummary===null||
                ($price['currency']===$lowestFromSummary['currency']&&$price['amount']<$lowestFromSummary['amount']))) {
                $lowestFromSummary=$price;
            }
        }
        $lowestVisible=null;
        foreach($offers as $row){
            $price=$row['total'];
            if($price!==null&&($lowestVisible===null||
                ($price['currency']===$lowestVisible['currency']&&$price['amount']<$lowestVisible['amount']))) {
                $lowestVisible=$price;
            }
        }

        usort($offers,static function(array $a,array $b): int {
            if($a['winner']!==$b['winner'])return $a['winner']?-1:1;
            if($a['total']===null)return $b['total']===null?0:1;
            if($b['total']===null)return -1;
            if($a['total']['currency']!==$b['total']['currency'])
                return strcmp($a['total']['currency'],$b['total']['currency']);
            return $a['total']['amount']<=>$b['total']['amount'];
        });

        return [
            'source'=>'amazon_live', // The legacy offers API is never an archive.
            'retrieved_at'=>self::txt($api['retrieved_at_utc']??null,40),
            'total_offers'=>filter_var($api['total_offer_count']??null,FILTER_VALIDATE_INT)?:null,
            'returned'=>count($offers),
            'winners'=>$winnerCount,
            'buy_box'=>$buyBox,
            'lowest_summary'=>$lowestFromSummary,
            'lowest_visible'=>$lowestVisible,
            'offers'=>$offers,
        ];
    }

    public static function sellerIds(array $api): array
    {
        $ids=[];
        foreach(array_slice(is_array($api['offers']??null)?$api['offers']:[],0,20) as $row){
            if(!is_array($row))continue;
            $seller=(string)($row['seller_id']??'');
            if(preg_match('/^[A-Z0-9]{5,40}$/D',$seller))$ids[$seller]=true;
        }
        return array_slice(array_keys($ids),0,20);
    }
}
