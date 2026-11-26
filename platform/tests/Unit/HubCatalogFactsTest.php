<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Services\HubCatalogFacts as F;
use PHPUnit\Framework\TestCase;
final class HubCatalogFactsTest extends TestCase
{
    public function test_explicit_volume_attributes_and_title_agree(): void
    {
        $p=F::volume('Haarfarbe 60 ml',['item_volume'=>[['value'=>0.06,'unit'=>'liters','marketplace_id'=>'A1PA6795UKMFR9']]],[]);
        self::assertSame(['Inhalt/Menge'=>'60','Maßeinheit'=>'ml'],$p['fields']);
        self::assertSame('verified',$p['state']);
        self::assertSame(['Inhalt/Menge'=>'250','Maßeinheit'=>'ml'],F::volume('Shampoo',['item_volume'=>[['value'=>250,'unit'=>'milliliters']]],[])['fields']);
    }
    public function test_conflicts_never_fill_missing_half_of_mismatched_quantity(): void
    {
        self::assertSame('conflict',F::volume('Haarfarbe 60 ml',['item_volume'=>[['value'=>90,'unit'=>'milliliters']]],[])['state']);
        self::assertSame('conflict',F::volume('Haarfarbe 60 ml',[],['Inhalt/Menge'=>'90'])['state']);
        self::assertSame('conflict',F::volume('Haarfarbe 60 ml',[],['Maßeinheit'=>'Stk'])['state']);
        self::assertSame('missing',F::volume('Haarfarbe',['unit_count'=>[['value'=>60,'unit'=>'count']]],[])['state']);
        self::assertSame(['Maßeinheit'=>'ml'],F::volume('Haarfarbe 60 ml',[],['Inhalt/Menge'=>'60'])['fields']);
    }
}
