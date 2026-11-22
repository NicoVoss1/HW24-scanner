<?php
declare(strict_types=1);

namespace Tests\Feature;

use App\Services\LegacyReadBridge;
use RuntimeException;
use Tests\TestCase;

final class IntelligenceConsoleSafetyTest extends TestCase
{
    public function test_legacy_bridge_refuses_unconfigured_credentials_without_http_request(): void
    {
        config()->set('hw24_legacy.base_url', 'https://artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user', '');
        config()->set('hw24_legacy.password', '');
        $response = app(LegacyReadBridge::class)->fetch('jtl.article', ['q' => '3474630337398']);
        $this->assertFalse($response['ok']);
        $this->assertSame('unconfigured', $response['state']);
    }

    public function test_legacy_bridge_refuses_older_test_host_even_with_credentials(): void
    {
        config()->set('hw24_legacy.base_url', 'https://test-artikelmanager.hairworld24.de');
        config()->set('hw24_legacy.user', 'dummy-operator');
        config()->set('hw24_legacy.password', 'dummy-password');
        $response = app(LegacyReadBridge::class)->fetch('jtl.article', ['q' => '3474630337398']);
        $this->assertFalse($response['ok']);
        $this->assertSame('unconfigured', $response['state']);
    }

    public function test_legacy_bridge_denies_non_whitelisted_service(): void
    {
        $this->expectException(RuntimeException::class);
        app(LegacyReadBridge::class)->fetch('jtl.write', ['q' => '3474630337398']);
    }

    public function test_guest_cannot_access_article_console(): void
    {
        $this->get('/admin/artikel')->assertRedirect('/admin/login');
    }
}
