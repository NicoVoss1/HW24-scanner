<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/api/purchase-intake-domain.php';
function expectEq(mixed $actual,mixed $expected,string $name): void {
    if($actual!==$expected){
        fwrite(STDERR,"FAIL $name: ".var_export($actual,true)." !== ".var_export($expected,true)."\n");
        exit(1);
    }
}
function expectInvalid(callable $run,string $name): void {
    try{$run();fwrite(STDERR,"FAIL $name did not reject bad input\n");exit(1);}
    catch(InvalidArgumentException $e){}
}
expectEq(hw24PurchaseUnitCents('1,20'),120,'comma price');
expectEq(hw24PurchaseUnitCents('1.20'),120,'dot price');
expectEq(hw24PurchaseUnitCents('2'),200,'integer price');
expectEq(hw24PurchaseUnitCents('0,01'),1,'cent');
expectEq(hw24PurchaseUnitCents('0'),0,'explicit zero');
foreach(['1.234','1,234','-1','1 000,00','1e4','', '1,','1,2,3'] as $bad){
    expectInvalid(fn()=>hw24PurchaseUnitCents($bad),'invalid price '.$bad);
}
expectEq(hw24PurchaseCode('ML','202611',1),'ML202611-1','Maike prefix monthly code');
expectEq(hw24PurchaseCode('ML','202611',2),'ML202611-2','second purchase contact');
expectEq(hw24PurchaseCode('AB','202611',1),'AB202611-1','independent contact counter');
expectEq(hw24PurchaseCode('ML','202612',1),'ML202612-1','new month reset');
foreach([['ML','202613',1],['M','202611',1],['ML','202611',0],['AB','202611',1000000]] as $bad){
    expectInvalid(fn()=>hw24PurchaseCode(...$bad),'invalid purchase code');
}
expectEq(hw24PurchaseLineCents(5,120,19),
    ['net_cents'=>600,'tax_cents'=>114,'gross_cents'=>714],
    'five repeated scans for 1.20 each');
expectEq(hw24PurchaseLineCents(5,101,19),
    ['net_cents'=>505,'tax_cents'=>96,'gross_cents'=>601],
    'line-based half-up VAT rounding');
expectEq(hw24PurchaseLineCents(4,150,7),
    ['net_cents'=>600,'tax_cents'=>42,'gross_cents'=>642],
    'seven-percent invoice');
expectEq(hw24PurchaseLineCents(3,200,0),
    ['net_cents'=>600,'tax_cents'=>0,'gross_cents'=>600],
    'zero percent explicit');
expectEq(hw24PurchaseLineCents(0,120,19),
    ['net_cents'=>0,'tax_cents'=>0,'gross_cents'=>0],
    'removed scan quantity zero');
expectInvalid(fn()=>hw24PurchaseLineCents(-1,120,19),'negative count');
expectInvalid(fn()=>hw24PurchaseLineCents(5,120,12),'unsupported VAT');
expectInvalid(fn()=>hw24PurchaseLineCents(PHP_INT_MAX,PHP_INT_MAX,19),'overflow');
echo "HW24 purchase domain tests: passed\n";
