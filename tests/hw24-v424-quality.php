<?php
declare(strict_types=1);
require_once __DIR__.'/../api/hw24-quality-rules.php';
require_once __DIR__.'/../api/hw24-quality-filters.php';
function check(bool $pass,string $msg): void {
 if(!$pass)throw new RuntimeException('FAILED: '.$msg);
}
check(hw24QualityGtinValid('3474630337398'),'reference EAN checksum');
check(!hw24QualityGtinValid('3474630337397'),'reference EAN bad checksum rejected');
$a=['jtl_article_id'=>'1','artikelname'=>'A','bestand'=>10.0,'netto_ek'=>null,
 'letzter_ek'=>null,'critical_count'=>0,'issue_count'=>1];
$b=['jtl_article_id'=>'2','artikelname'=>'B','bestand'=>0.0,'netto_ek'=>5.0,
 'letzter_ek'=>5.0,'critical_count'=>3,'issue_count'=>5];
$c=['jtl_article_id'=>'3','artikelname'=>'C','bestand'=>0.0,'netto_ek'=>null,
 'letzter_ek'=>null,'critical_count'=>2,'issue_count'=>4];
$d=['jtl_article_id'=>'4','artikelname'=>'D','bestand'=>null,'netto_ek'=>null,
 'letzter_ek'=>null,'critical_count'=>1,'issue_count'=>2];
check(hw24QualityQuantityMatches($a,'positive','all','or'),'stock-only positive filter');
check(!hw24QualityQuantityMatches($b,'positive','all','or'),'stock-only excludes zero');
check(hw24QualityQuantityMatches($b,'all','positive','or'),'EK-only positive filter');
check(!hw24QualityQuantityMatches($a,'all','positive','or'),'EK-only excludes no EK');
check(hw24QualityQuantityMatches($a,'positive','positive','or'),'OR matches stock');
check(hw24QualityQuantityMatches($b,'positive','positive','or'),'OR matches EK');
check(!hw24QualityQuantityMatches($c,'positive','positive','or'),'OR excludes neither');
check(!hw24QualityQuantityMatches($a,'positive','positive','and'),'AND excludes no EK');
check(!hw24QualityQuantityMatches($b,'positive','positive','and'),'AND excludes zero stock');
check(hw24QualityQuantityMatches($a,'positive','missing_both','and'),'stock & missing EK');
check(hw24QualityQuantityMatches($d,'missing','missing_both','and'),'null stock reported missing');
check(!hw24QualityQuantityMatches($d,'zero','all','or'),'null stock is not zero');
check(hw24QualityCompare($a,$b,'priority')<0,'stocked article first even if fewer issues');
check(hw24QualityCompare($b,$a,'ek_desc')<0,'numeric EK descending');
check(hw24QualityCompare($b,$a,'ek_asc')<0,'missing EK last in ascending sort');
check(hw24QualityCompare($b,$d,'stock_asc')<0,'zero stock before missing stock');
check(hw24QualityCompare($a,$b,'stock_desc')<0,'positive stock before zero descending');
echo "HW24 V4.24 reference GTIN, stock/EK independent filters and numeric sorting: PASS\n";
