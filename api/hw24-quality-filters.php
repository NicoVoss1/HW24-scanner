<?php
declare(strict_types=1);
/** Pure, testable stock/EK filter and column-sort functions. */
function hw24QualityQuantityMatches(array $row,string $stock,string $ek,string $combine): bool {
 $stockNumber=isset($row['bestand'])?(float)$row['bestand']:null;
 $ekNet=isset($row['netto_ek'])?(float)$row['netto_ek']:null;
 $ekLast=isset($row['letzter_ek'])?(float)$row['letzter_ek']:null;
 $stockMatch=match($stock){
  'positive'=>$stockNumber!==null&&$stockNumber>0,
  'zero'=>$stockNumber!==null&&$stockNumber===0.0,
  'negative'=>$stockNumber!==null&&$stockNumber<0,
  'missing'=>$stockNumber===null,
  default=>true
 };
 $ekMatch=match($ek){
  'positive'=>($ekNet!==null&&$ekNet>0)||($ekLast!==null&&$ekLast>0),
  'missing_both'=>!(($ekNet!==null&&$ekNet>0)||($ekLast!==null&&$ekLast>0)),
  'netto_missing'=>$ekNet===null||$ekNet<=0,
  default=>true
 };
 if($stock!=='all'&&$ek!=='all')return $combine==='or'
  ?($stockMatch||$ekMatch):($stockMatch&&$ekMatch);
 return $stockMatch&&$ekMatch;
}
function hw24QualityCompare(array $a,array $b,string $sort): int {
 $num=static function($left,$right,bool $descending): int {
  if($left===null)return $right===null?0:1;
  if($right===null)return -1;
  return $descending?($right<=>$left):($left<=>$right);
 };
 $name=static fn()=>strnatcasecmp($a['artikelname'],$b['artikelname'])
   ?:strcmp((string)$a['jtl_article_id'],(string)$b['jtl_article_id']);
 if($sort==='stock_desc'||$sort==='stock_asc')
  return $num($a['bestand'],$b['bestand'],$sort==='stock_desc')
   ?:($b['critical_count']<=>$a['critical_count'])?:$name();
 if($sort==='ek_desc'||$sort==='ek_asc')
  return $num($a['netto_ek'],$b['netto_ek'],$sort==='ek_desc')
   ?:($num($a['bestand'],$b['bestand'],true))?:$name();
 // A positive stock beats any critical out-of-stock item in default work order.
 $aPositive=$a['bestand']!==null&&$a['bestand']>0;
 $bPositive=$b['bestand']!==null&&$b['bestand']>0;
 return ($bPositive<=>$aPositive)
   ?:($b['critical_count']<=>$a['critical_count'])
   ?:($b['issue_count']<=>$a['issue_count'])
   ?:($num($a['bestand'],$b['bestand'],true))
   ?:$name();
}
