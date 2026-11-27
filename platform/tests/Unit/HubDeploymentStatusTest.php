<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Services\HubDeploymentStatus as S;
use PHPUnit\Framework\TestCase;

final class HubDeploymentStatusTest extends TestCase
{
    public function test_current_release_does_not_display_previous_incident_as_active():void
    {
        $now=strtotime('2026-11-27T00:41:00Z');
        $head=str_repeat('a',40);
        $r=S::present("2026-11-27T00:40:01Z CURRENT HEAD=$head CURRENT $head",'2026-11-26T23:26:02Z BLOCKED HEAD=old',false,$now);
        self::assertTrue($r['healthy']);self::assertTrue($r['incident_history']);self::assertSame('aaaaaaa',$r['head']);
        self::assertSame('TEST-Version aktuell',$r['label']);
    }
    public function test_stale_blocked_unknown_and_notification_states_remain_visible():void
    {
        $now=strtotime('2026-11-27T00:41:00Z');$head=str_repeat('b',40);
        foreach(["2026-11-26T23:40:01Z CURRENT HEAD=$head", "2026-11-27T00:40:01Z BLOCKED HEAD=$head", "2026-11-27T00:40:01Z UNKNOWN HEAD=$head",''] as $line)
            self::assertFalse(S::present($line,'',false,$now)['healthy']);
        $r=S::present("2026-11-27T00:40:01Z DEPLOYED HEAD=$head",'',true,$now);
        self::assertTrue($r['notification_pending']);
    }
}
