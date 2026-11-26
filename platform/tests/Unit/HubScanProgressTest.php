<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Services\HubScanProgress;
use PHPUnit\Framework\TestCase;

final class HubScanProgressTest extends TestCase
{
    public function test_queue_is_not_running_and_jtl_is_not_guessed(): void
    {
        $p=HubScanProgress::present(['review'=>'scan_only'],['status'=>'queued'],[]);
        self::assertSame('Wartet auf Worker',$p['Recherche']);
        self::assertSame('Aktueller Stand offen',$p['JTL']);
        self::assertSame('Noch nicht gespeichert',$p['Entwurf']);
        self::assertSame('Nicht freigegeben',$p['Ameise']);
    }
    public function test_archived_research_and_ready_draft_are_separate(): void
    {
        $p=HubScanProgress::present(['review'=>'ready'],['status'=>'completed','evidence'=>['catalog_state'=>'cache','ai_state'=>'read','ai_count'=>0]],['state'=>'absent']);
        self::assertSame('Archiv verwendet',$p['Amazon']);
        self::assertSame('Nicht vorhanden',$p['JTL']);
        self::assertSame('Kein Bericht · bei Bedarf manuell',$p['KI']);
        self::assertSame('Prüfexport freigegeben',$p['Ameise']);
    }
}
