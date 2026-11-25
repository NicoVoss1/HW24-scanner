<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubTestBatchCycle;
use App\Services\ResearchQueue;
use PDO;
use PHPUnit\Framework\TestCase;

final class HubTestBatchCycleTest extends TestCase
{
    public function test_intake_deduplicates_gtin_and_excludes_removed_or_unresolved_rows(): void
    {
        $r=['ean'=>'4006381333931','resolution'=>'new_candidate','review_state'=>'scan_only','scan_count'=>5];
        self::assertSame(['4006381333931'],HubTestBatchCycle::intake([
            $r,array_replace($r,['ean'=>'04006381333931']),
            array_replace($r,['ean'=>'5901234123457','review_state'=>'removed']),
            array_replace($r,['ean'=>'036000291452','resolution'=>'multiple']),
            array_replace($r,['ean'=>'4006381333932']),
        ]));
        self::assertSame([2],HubTestBatchCycle::scope([
            ['id'=>1,'status'=>'completed'],['id'=>2,'status'=>'queued'],
            ['id'=>2,'status'=>'retry'],['id'=>3,'status'=>'blocked'],
        ]));
    }

    public function test_atomic_claim_never_selects_earlier_unapproved_job(): void
    {
        $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $db->exec("CREATE TABLE hw24_research_jobs(
            id INTEGER PRIMARY KEY AUTOINCREMENT,gtin_key TEXT UNIQUE,ean TEXT,status TEXT,
            attempt_count INTEGER DEFAULT 0,requested_at_utc TEXT,next_attempt_utc TEXT,lease_token TEXT,
            lease_until_utc TEXT,leased_by TEXT,last_error_code TEXT,completed_at_utc TEXT,evidence_json TEXT);
            CREATE TABLE hw24_research_gate(scope_key TEXT PRIMARY KEY,next_allowed_utc TEXT,active_job_id INTEGER);
            INSERT INTO hw24_research_gate VALUES('shared_workspace','2000-01-01 00:00:00',NULL);
            CREATE TABLE hw24_research_events(id INTEGER PRIMARY KEY AUTOINCREMENT,job_id INTEGER,actor TEXT,event_type TEXT,detail_code TEXT,occurred_at_utc TEXT);");
        $q=new ResearchQueue($db);
        $a=$q->enqueue('4006381333931','test');$b=$q->enqueue('5901234123457','test');
        self::assertNull($q->claim('test',[]));
        self::assertNull($q->claim('test',[999]));
        $lease=$q->claim('test',[$b['id']]);
        self::assertSame($b['id'],$lease['id']);
        self::assertSame('queued',$q->status($a['id'])['status']);
        self::assertNull($q->claim('another',[$a['id']]));
        self::assertSame('completed',$q->finish($lease['id'],$lease['token'],'test','completed'));
        self::assertNull($q->claim('test',[$a['id']]));
    }
}
