<?php
require __DIR__.'/../app/Domain/Pricing/ChannelPrice.php';
require __DIR__.'/../app/Domain/Pricing/VerifiedGrossDeriver.php';
use App\Domain\Pricing\VerifiedGrossDeriver;
$gross=VerifiedGrossDeriver::forJtl('10.00','19.00','JTL tax class and Germany zone',
 'JTL standard net','2026-11-22 10:00:00','DE','standard');
if($gross->gross!=='11.90'||$gross->status!=='derived'||$gross->net!=='10.00')
 throw new RuntimeException('FAIL: 10.00 net at verified 19% must yield derived gross 11.90');
$gross=VerifiedGrossDeriver::forJtl('12.3450','7.00','JTL tax class Germany zone',
 'JTL standard net','2026-11-22 10:00:00','DE','standard');
if($gross->gross!=='13.21')throw new RuntimeException('FAIL: rounding derived gross');
$blocked=false;try{VerifiedGrossDeriver::forJtl('10.00','19.00','',
 'JTL standard net','2026-11-22 10:00:00','DE','standard');}
catch(InvalidArgumentException){$blocked=true;}
if(!$blocked)throw new RuntimeException('FAIL: should reject missing tax provenance');
echo "PASS: only verified JTL tax + net can yield labeled derived gross\n";
