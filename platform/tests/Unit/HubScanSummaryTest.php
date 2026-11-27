<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\HubScanSummary;
use PHPUnit\Framework\TestCase;

final class HubScanSummaryTest extends TestCase
{
    public function test_quantity_and_scan_events_are_not_identical_and_removed_rows_are_excluded(): void
    {
        $s=HubScanSummary::fromRows([
            ['scans'=>5,'scan_events'=>5,'review'=>'scan_only','resolution'=>'existing'],
            ['scans'=>1,'scan_events'=>0,'review'=>'draft','resolution'=>'new_candidate'],
            ['scans'=>2,'scan_events'=>4,'review'=>'draft','resolution'=>'multiple'],
            ['scans'=>0,'scan_events'=>9,'review'=>'removed','resolution'=>'existing'],
        ]);
        self::assertSame(3,$s['positions']);
        self::assertSame(8,$s['quantity']);
        self::assertSame(9,$s['scan_events']);
        self::assertTrue($s['events_complete']);
        self::assertSame(1,$s['removed']);
        self::assertSame(1,$s['existing']);
        self::assertSame(1,$s['new']);
        self::assertSame(1,$s['unresolved']);
    }
    public function test_unknown_event_count_not_reported_as_confirmed_zero(): void
    {
        $s=HubScanSummary::fromRows([['scans'=>5,'review'=>'scan_only']]);
        self::assertFalse($s['events_complete']);
        self::assertSame(5,$s['quantity']);
        self::assertFalse($s['possibly_limited']);
        self::assertTrue(HubScanSummary::fromRows(array_fill(0,3000,['scans'=>1]))['possibly_limited']);
    }
}
