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
}
