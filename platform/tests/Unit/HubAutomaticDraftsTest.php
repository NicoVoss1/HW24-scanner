<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubAutomaticDrafts;
use PHPUnit\Framework\TestCase;

final class HubAutomaticDraftsTest extends TestCase
{
    public function test_only_explicit_test_batch_is_allowed(): void
    {
        $rows=[['id'=>1,'label'=>'TEST Aufnahme'],['id'=>2,'label'=>'Produktiv'],['id'=>3,'label'=>'TESTING']];
        self::assertTrue(HubAutomaticDrafts::allowedBatch($rows,1));
        self::assertFalse(HubAutomaticDrafts::allowedBatch($rows,2));
        self::assertFalse(HubAutomaticDrafts::allowedBatch($rows,3));
        self::assertFalse(HubAutomaticDrafts::allowedBatch($rows,9));
    }

    public function test_never_repopulates_manually_edited_or_existing_rows(): void
    {
        $row=['id'=>1,'ean'=>'4006381333931','resolution'=>'new_candidate','review_state'=>'scan_only','revision'=>0,'draft'=>[]];
        self::assertTrue(HubAutomaticDrafts::eligible($row));
        foreach(['resolution'=>'existing','review_state'=>'ready','revision'=>1,'draft'=>['Artikelname'=>'Eigener Text'],'ean'=>'4006381333932'] as $field=>$value){
            self::assertFalse(HubAutomaticDrafts::eligible(array_replace($row,[$field=>$value])));
        }
    }
}
