<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubBatchSchedule as S;
use App\Services\HubAutomaticDrafts;
use PHPUnit\Framework\TestCase;

final class HubBatchScheduleTest extends TestCase
{
    private function state(): array
    {
        return ['version'=>1,'batch'=>4,'limit'=>2,'day'=>'2026-11-26','used'=>0,
            'cursor'=>0,'paused'=>false,'inflight'=>false];
    }

    public function test_idle_cycle_does_not_consume_budget_and_real_attempt_does(): void
    {
        [$s,$allow]=S::reserve($this->state(),4,2,'2026-11-26');
        self::assertTrue($allow);self::assertSame(1,$s['used']);self::assertTrue($s['inflight']);
        $s=S::finish($s,$allow,['status'=>'ok','research'=>null,'drafts'=>['cursor'=>45,'saved'=>1]]);
        self::assertSame(0,$s['used']);self::assertSame(45,$s['cursor']);
        for($i=0;$i<2;$i++){
            [$s,$allow]=S::reserve($s,4,2,'2026-11-26');
            self::assertTrue($allow);
            $s=S::finish($s,$allow,['status'=>'ok','research'=>['status'=>'retry'],'drafts'=>[]]);
        }
        [$s,$allow]=S::reserve($s,4,2,'2026-11-26');
        self::assertFalse($allow);self::assertSame(2,$s['used']);
        $s=S::finish($s,$allow,['status'=>'ok','research'=>null,'drafts'=>[]]);
        [$s,$allow]=S::reserve($s,4,2,'2026-11-27');
        self::assertTrue($allow);self::assertSame(1,$s['used']);
    }
    public function test_uncertain_previous_run_does_not_restart_even_next_day(): void
    {
        [$s,$allow]=S::reserve($this->state(),4,2,'2026-11-26');
        $this->expectException(\RuntimeException::class);
        S::reserve($s,4,2,'2026-11-27');
    }
    public function test_failed_cycle_pauses_and_does_not_refund(): void
    {
        [$s,$allow]=S::reserve($this->state(),4,2,'2026-11-26');
        $s=S::finish($s,$allow,['status'=>'draft_not_confirmed']);
        self::assertTrue($s['paused']);self::assertSame(1,$s['used']);
        $this->expectException(\RuntimeException::class);
        S::reserve($s,4,2,'2026-11-26');
    }
    public function test_cursor_wraps_without_starving_other_rows(): void
    {
        self::assertSame([45,50,4,10],array_column(HubAutomaticDrafts::rotate([['id'=>50],['id'=>4],['id'=>45],['id'=>10]],10),'id'));
    }
}
