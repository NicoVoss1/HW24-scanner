<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Filament\Pages\ReferenceTableWorkbench;
use PHPUnit\Framework\TestCase;
final class ReferenceColumnTest extends TestCase
{
    public function test_excel_column_positions_match_original_sheet():void
    {
        foreach([1=>'A',26=>'Z',27=>'AA',62=>'BJ',63=>'BK',68=>'BP',872=>'AGN'] as $position=>$label){
            self::assertSame($label,ReferenceTableWorkbench::excelColumn($position));
        }
    }
    public function test_every_partially_populated_column_is_visible_in_original_order():void
    {
        $columns=[];
        for($i=1;$i<=872;$i++){
            $filled=$i%3===0?0:($i%3===1?2693:1);
            $columns[]=['position'=>$i,'name'=>'Feld '.$i,'filled_count'=>$filled];
        }
        $visible=ReferenceTableWorkbench::visibleColumns($columns);
        self::assertCount(582,$visible);
        self::assertSame(2,$visible[1]['position']);
        self::assertSame(872,end($visible)['position']);
        self::assertSame($columns,ReferenceTableWorkbench::visibleColumns($columns,true));
    }
}
