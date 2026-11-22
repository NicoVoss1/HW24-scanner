<?php
declare(strict_types=1);
require __DIR__.'/../app/Domain/Pricing/ChannelPrice.php';
require __DIR__.'/../app/Domain/Pricing/PriceBoard.php';

use App\Domain\Pricing\ChannelPrice;
use App\Domain\Pricing\PriceBoard;
function expected(bool $v,string $label):void{if(!$v)throw new RuntimeException('FAIL: '.$label);}
$jtl = new ChannelPrice('jtl','standard','EUR',null,'10.00','unverified',
    'HW24 JTL mirror netto_vk',null, customerGroup:'standard', taxCountry:'DE');
$shopify = new ChannelPrice('shopify','own_offer','EUR','14.00',null,'verified',
    'Shopify Admin GraphQL ProductVariant.price','2026-11-22 00:00:00',marketplace:'DE',
    publicationStatus:'product_active_visibility_unverified');
$amazon = new ChannelPrice('amazon','market_offer','EUR','13.45',null,'verified',
    'Amazon SP-API competitive offers','2026-11-22 00:00:00',marketplace:'DE',
    offerId:'example_only');
$board=(new PriceBoard)->build([$jtl,$shopify,$amazon]);
expected($board['jtl'][0]['gross']===null,'never manufacture JTL gross');
expected($board['jtl'][0]['net']==='10.00','preserve JTL net');
expected($board['shopify'][0]['gross']==='14.00','preserve Shopify gross with origin');
expected($board['ebay'][0]['status']==='unavailable','no fake eBay active listing');
expected($board['amazon'][0]['price_type']==='market_offer','market price != own price');
$failure=false;
try {new ChannelPrice('jtl','standard','EUR','11.90','10.00','verified',null,null);}
catch(InvalidArgumentException) {$failure=true;}
expected($failure,'JTL gross without provenance must fail');
$failure=false;
try {new ChannelPrice('ebay','own_offer','EUR','14.00',null,'unavailable',null,null);}
catch(InvalidArgumentException) {$failure=true;}
expected($failure,'unavailable cannot have a fabricated price');
echo "PASS: source-aware 4-channel comparison; JTL net never mislabeled gross\n";
