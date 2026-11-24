<?php
declare(strict_types=1);
namespace Tests\Feature;

use App\Models\User;
use App\Services\LegacyReadBridge;
use App\Services\LegacyApprovedActionBridge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class BridgeQueryAuditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('hw24_query_audit.enabled', true);
        config()->set('hw24_legacy.base_url', 'https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user', 'shared-technical-account');
        config()->set('hw24_legacy.password', 'test-password');
    }

    public function test_two_users_read_same_research_but_have_separate_audit_actors(): void
    {
        $a=User::factory()->create();
        $b=User::factory()->create();
        Http::fake(['*'=>Http::response([
            'ok'=>true,'scope'=>'shared_workspace',
            'reviews'=>[['id'=>42,'ean'=>'3474630337398']],
        ],200)]);
        Http::preventStrayRequests();
        $this->actingAs($a);
        $first=app(LegacyReadBridge::class)->fetch('ai.history',['ean'=>'3474630337398']);
        $this->actingAs($b);
        $second=app(LegacyReadBridge::class)->fetch('ai.history',['ean'=>'3474630337398']);
        $this->assertSame($first['data'],$second['data']);
        $this->assertSame('recorded',$first['audit_state']);
        $this->assertSame('recorded',$second['audit_state']);
        $this->assertDatabaseCount('query_audit_events',2);
        foreach ([$a,$b] as $user) {
            $this->assertDatabaseHas('query_audit_events',[
                'actor_ref'=>(string)$user->id,'source_system'=>'ai',
                'operation'=>'ai.history','identifier'=>'3474630337398','result'=>'success',
            ]);
        }
        Http::assertSentCount(2);
        Http::assertSent(fn($request)=>$request->method()==='GET');
    }

    public function test_throttling_is_recorded_without_payload_or_query_secret(): void
    {
        $user=User::factory()->create();
        $this->actingAs($user);
        Http::fake(['*'=>Http::response(['ok'=>false,'error'=>'rate_limit'],429)]);
        $r=app(LegacyReadBridge::class)->fetch('amazon.keywords',['q'=>'untrusted keyword secret']);
        $this->assertSame(429,$r['http_status']);
        $this->assertSame('recorded',$r['audit_state']);
        $this->assertDatabaseHas('query_audit_events',[
            'actor_ref'=>(string)$user->id,'operation'=>'amazon.keywords',
            'http_status'=>429,'result'=>'throttled','identifier'=>null,
        ]);
    }

    public function test_disabled_paid_request_is_audited_without_external_call(): void
    {
        $this->actingAs(User::factory()->create());
        config()->set('hw24_legacy.ai_paid_enabled',false);
        Http::fake();
        $r=app(LegacyApprovedActionBridge::class)->post('ai.review',['ean'=>'3474630337398']);
        $this->assertSame('disabled_by_operator',$r['state']);
        $this->assertSame('recorded',$r['audit_state']);
        $this->assertDatabaseHas('query_audit_events',['operation'=>'ai.review','result'=>'failure']);
        Http::assertNothingSent();
    }

    public function test_disabled_audit_does_not_write_or_change_result(): void
    {
        config()->set('hw24_query_audit.enabled',false);
        Http::fake(['*'=>Http::response(['ok'=>true,'count'=>1],200)]);
        $r=app(LegacyReadBridge::class)->fetch('amazon.catalog',['ean'=>'3474630337398']);
        $this->assertTrue($r['ok']);
        $this->assertSame('disabled',$r['audit_state']);
        $this->assertDatabaseCount('query_audit_events',0);
    }
    public function test_admin_can_read_audit_and_non_admin_cannot_access_page(): void
    {
        $admin=User::factory()->create(['name'=>'HW24 Test Admin']);
        $other=User::factory()->create(['name'=>'HW24 Test Staff']);
        config()->set('hw24_legacy.admin_email',$admin->email);
        $this->actingAs($other);
        app(\App\Services\QueryAuditRecorder::class)->record(
            'ai','ai.history','manual','ean','3474630337398',200,19,'success'
        );
        $this->assertFalse(\App\Filament\Pages\QueryAuditWorkbench::canAccess());
        $this->get('/admin/abfrageprotokoll')->assertForbidden();
        $this->actingAs($admin);
        $this->assertTrue(\App\Filament\Pages\QueryAuditWorkbench::canAccess());
        $this->get('/admin/abfrageprotokoll')->assertOk()
            ->assertSee('HW24 Test Staff')->assertSee('3474630337398');
    }

}
