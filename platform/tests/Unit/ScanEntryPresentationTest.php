<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\ScanEntryPresentation;
use PHPUnit\Framework\TestCase;

final class ScanEntryPresentationTest extends TestCase
{
    public function test_old_import_metadata_does_not_invent_import_time(): void
    {
        $r=ScanEntryPresentation::present([
            'first_scanned_utc'=>'2026-11-24 19:20:30.123456',
            'scan_count'=>1,'csv_meta'=>['origin'=>'CSV-Import'],
            'review_state'=>'scan_only','resolution'=>'new_candidate',
        ]);
        self::assertSame('24.11.2026 · 20:20:30 MEZ',$r['entry_at']);
        self::assertSame('CSV-Import nachgewiesen',$r['origin_label']);
        self::assertSame('Importzeit nicht separat gespeichert',$r['import_label']);
        self::assertNull($r['last_scan_at']);
        self::assertSame('Erfasst · Prüfung offen',$r['review_label']);
        self::assertSame('Neuanlage-Kandidat',$r['resolution_label']);
    }
    public function test_real_scanner_events_and_csv_are_distinct_evidence(): void
    {
        $r=ScanEntryPresentation::present([
            'scan_event_count'=>2,'last_scanned_utc'=>'2026-07-01 08:00:00',
            'csv_meta'=>['origin'=>'CSV-Import','imported_at_utc'=>'2026-06-30 20:00:00'],
            'updated_at_utc'=>'2026-07-01 08:05:00','review_state'=>'ready',
        ]);
        self::assertSame('CSV-Import und Scan',$r['origin_label']);
        self::assertSame('01.07.2026 · 10:00:00 MESZ',$r['last_scan_at']);
        self::assertSame('Angelegt per CSV: 30.06.2026 · 22:00:00 MESZ',$r['import_label']);
        self::assertSame('01.07.2026 · 10:05:00 MESZ',$r['updated_at']);
        self::assertSame('Für Ameise-Prüfexport freigegeben',$r['review_label']);
    }
    public function test_quantity_alone_is_not_scanner_proof(): void
    {
        $r=ScanEntryPresentation::present(['scan_count'=>9,'last_scanned_utc'=>'2026-11-24 19:00:00']);
        self::assertSame('Erfassungsart nicht belegt',$r['origin_label']);
        self::assertNull($r['last_scan_at']);
        self::assertNull($r['import_label']);
    }
    public function test_later_import_confirmation_is_not_creation_time(): void
    {
        $r=ScanEntryPresentation::present([
            'csv_meta'=>['origin'=>'CSV-Import','confirmed_at_utc'=>'2026-11-24 19:00:00','created_by_import'=>false],
            'scan_event_count'=>1,
        ]);
        self::assertSame('CSV-Import und Scan',$r['origin_label']);
        self::assertSame('CSV bestätigt: 24.11.2026 · 20:00:00 MEZ',$r['import_label']);
    }
    public function test_invalid_and_missing_times_are_not_displayed(): void
    {
        self::assertNull(ScanEntryPresentation::germanDate(null));
        self::assertNull(ScanEntryPresentation::germanDate('yesterday'));
        self::assertNull(ScanEntryPresentation::germanDate('2026-02-30 10:00:00'));
    }
}
