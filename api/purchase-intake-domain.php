<?php
declare(strict_types=1);

/**
 * Pure, testable HW24 purchase-intake rules.
 * No database, network, JTL or HTTP side effects.
 */

function hw24PurchaseUnitCents(string $amount): int {
    if (!preg_match('/^(?:0|[1-9][0-9]{0,6})(?:[,.][0-9]{1,2})?$/D', $amount)) {
        throw new InvalidArgumentException('invalid_unit_net');
    }
    $parts=preg_split('/[,.]/',$amount);
    return (int)$parts[0]*100+(int)str_pad($parts[1]??'0',2,'0');
}

function hw24PurchaseCode(string $prefix, string $yearMonth, int $sequence): string {
    if(!preg_match('/^[A-Z0-9]{2,8}$/D',$prefix)
       || !preg_match('/^20[0-9]{4}$/D',$yearMonth)
       || (int)substr($yearMonth,4,2)<1
       || (int)substr($yearMonth,4,2)>12
       || $sequence<1 || $sequence>999999) {
        throw new InvalidArgumentException('invalid_purchase_code_parts');
    }
    return $prefix.$yearMonth.'-'.$sequence;
}

/** Returns exactly rounded line totals in cents; rate is supplied by the invoice. */
function hw24PurchaseLineCents(int $quantity,int $unitNetCents,int $vatPercent): array {
    if($quantity<0 || $unitNetCents<0 || !in_array($vatPercent,[0,7,19],true)) {
        throw new InvalidArgumentException('invalid_purchase_line');
    }
    if($unitNetCents>0 && $quantity>intdiv(PHP_INT_MAX,$unitNetCents)) {
        throw new InvalidArgumentException('purchase_line_overflow');
    }
    $net=$quantity*$unitNetCents;
    if($net>intdiv(PHP_INT_MAX-50,$vatPercent?:1)) {
        throw new InvalidArgumentException('purchase_tax_overflow');
    }
    $tax=intdiv($net*$vatPercent+50,100);
    if($net>PHP_INT_MAX-$tax) {
        throw new InvalidArgumentException('purchase_gross_overflow');
    }
    return ['net_cents'=>$net,'tax_cents'=>$tax,'gross_cents'=>$net+$tax];
}


/** Archived catalog result is evidence, never a confirmed JTL identity.
 * Only one stored result on a snapshot page can be offered as a suggestion.
 * Never use arbitrary first hits or equate a brand with a manufacturer.
 */
function hw24PurchaseArchiveCandidate(?array $archive): array {
    if($archive===null)return ['status'=>'not_archived'];
    $result=[
        'status'=>'review_required',
        'snapshot_id'=>(int)$archive['snapshot_id'],
        'fetched_at_utc'=>(string)$archive['fetched_at_utc'],
        'result_count'=>(int)$archive['item_count'],
    ];
    if($result['result_count']===0){
        $result['status']='no_catalog_hit';
        return $result;
    }
    if($result['result_count']!==1||(int)$archive['stored_item_count']!==1){
        $result['status']='multiple_or_incomplete';
        return $result;
    }
    $asin=(string)($archive['asin']??'');
    if(!preg_match('/^[A-Z0-9]{10}$/D',$asin)){
        $result['status']='multiple_or_incomplete';
        return $result;
    }
    return $result+[
        'asin'=>$asin,
        'title'=>trim((string)($archive['item_name']??'')),
        'brand'=>trim((string)($archive['brand']??'')),
        'manufacturer'=>trim((string)($archive['manufacturer']??'')),
    ];
}
