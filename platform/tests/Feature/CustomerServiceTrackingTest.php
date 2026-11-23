<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\CustomerServiceWorkbench;
use App\Services\CustomerSupportDraftAi;
use App\Services\TrackingSnapshotReader;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use PDO;
use Tests\TestCase;

final class CustomerServiceTrackingTest extends TestCase
{
    private ?string $file=null;

    protected function tearDown(): void
    {
        if ($this->file && file_exists($this->file)) unlink($this->file);
        parent::tearDown();
    }

    private function stage(): string
    {
        $private=storage_path('app/private');
        if (!is_dir($private)) mkdir($private,0700,true);
        $this->file=tempnam($private,'hw24-test-track-');
        $pdo=new PDO('sqlite:'.$this->file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE snapshot_info(id INTEGER PRIMARY KEY, imported_at_utc TEXT, source_rows INTEGER, package_count INTEGER,event_count INTEGER,time_zone_verified INTEGER)');
        $pdo->exec("INSERT INTO snapshot_info VALUES(1,'2026-11-19 12:00:00',7,1,7,0)");
        $pdo->exec('CREATE TABLE packages(package_id INTEGER PRIMARY KEY,shipment_id INTEGER,amazon_order_id TEXT,jtl_order TEXT,delivery_note TEXT,tracking_number TEXT,carrier TEXT)');
        $pdo->exec("INSERT INTO packages VALUES(1775,123,'302-8659283-9232345','AU-20261116-13204','AU-20261116-13204-001','00340434887917016344','DHL Kleinpaket')");
        $pdo->exec('CREATE TABLE tracking_events(state_id INTEGER PRIMARY KEY,package_id INTEGER,event_at_raw TEXT,status_code INTEGER,event_text TEXT,provider_status TEXT,scan_location TEXT,country TEXT)');
        $pdo->exec("INSERT INTO tracking_events VALUES(88,1775,'2026-11-19 10:36:00.000',7,'Die Sendung liegt ab sofort in der Filiale Weldertstr. 1, 65510 Idstein zur Abholung bereit.','standard: LA',NULL,'Deutschland')");
        unset($pdo);
        config()->set('hw24_support.tracking_snapshot',$this->file);
        config()->set('hw24_support.snapshot_max_age_hours',12);
        return $this->file;
    }

    public function test_page_does_not_trigger_ai_or_tracking_on_mount(): void
    {
        config()->set('hw24_support.tracking_snapshot','');
        Http::preventStrayRequests();
        Livewire::test(CustomerServiceWorkbench::class)
            ->assertSee('KI-Kundenservice und Paketverfolgung')
            ->assertSee('Kostenfreie Tracking-Vorlage')
            ->assertDontSee('Weldertstr.');
        Http::assertNothingSent();
    }

    public function test_snapshot_reader_returns_proven_shop_order_package_and_unconverted_scan(): void
    {
        $this->stage();
        $data=app(TrackingSnapshotReader::class)->findOrder('302-8659283-9232345');
        $this->assertTrue($data['ok']);
        $this->assertSame('ready',$data['state']);
        $this->assertTrue($data['stale']);
        $this->assertFalse($data['timezone_verified']);
        $this->assertSame('00340434887917016344',$data['packages'][0]['tracking']);
        $this->assertStringContainsString('Weldertstr. 1',$data['packages'][0]['latest']['text']);
        $this->assertSame('2026-11-19 10:36:00.000',$data['packages'][0]['latest']['at_raw']);
    }

    public function test_preview_does_not_claim_live_dhl_status_or_send(): void
    {
        $this->stage();
        Http::preventStrayRequests();
        Livewire::test(CustomerServiceWorkbench::class)
            ->set('orderNumber','302-8659283-9232345')
            ->call('loadTracking')
            ->assertSet('evidence.state','ready')
            ->call('prepareTemplate')
            ->assertSee('Weldertstr. 1')
            ->assertSee('nicht zwingend der aktuelle Live-Status');
        Http::assertNothingSent();
    }

    public function test_absent_order_is_not_claimed_unshipped(): void
    {
        $this->stage();
        $result=app(TrackingSnapshotReader::class)->findOrder('302-0000000-0000000');
        $this->assertTrue($result['ok']);
        $this->assertSame('order_not_in_snapshot',$result['state']);
        $this->assertSame([], $result['packages']);
    }

    public function test_ai_is_fail_closed_and_no_network_request_occurs(): void
    {
        config()->set('hw24_support.ai_enabled',false);
        config()->set('hw24_support.ai_api_key','');
        Http::preventStrayRequests();
        $result=app(CustomerSupportDraftAi::class)->generate('Wo ist mein Paket?',[
            'order'=>'302-8659283-9232345','packages'=>[],
        ],null);
        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
    }

    public function test_invalid_amazon_order_has_no_snapshot_lookup(): void
    {
        $res=app(TrackingSnapshotReader::class)->findOrder("302-8659283-9232345' OR 1=1");
        $this->assertFalse($res['ok']);
        $this->assertSame('invalid_order',$res['state']);
    }

    public function test_admin_route_is_login_protected(): void
    {
        $this->get('/admin/kundenservice')->assertRedirect('/admin/login');
    }
}
