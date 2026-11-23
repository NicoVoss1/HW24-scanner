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
