<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Services\HubManualAiResearch;
use PHPUnit\Framework\TestCase;
final class HubManualAiResearchTest extends TestCase
{
    public function test_web_research_needs_advertised_capability_and_rest_budget(): void
    {
        $r=['ok'=>true,'data'=>['enabled'=>true,'archive_ready'=>true,'usd_month'=>1,'usd_budget'=>5,
            'daily_calls'=>1,'daily_limit'=>25]];
        self::assertNull(HubManualAiResearch::budgetBlock($r,false));
        self::assertNotNull(HubManualAiResearch::budgetBlock($r,true));
        $r['data']+=['review_versions'=>[1,2,3],'web_research_enabled'=>true,'web_reserve_usd'=>0.15];
        self::assertNull(HubManualAiResearch::budgetBlock($r,true));
        $r['data']['usd_month']=4.9;
        self::assertNotNull(HubManualAiResearch::budgetBlock($r,true));
        $r['data']['daily_calls']=25;
        self::assertNotNull(HubManualAiResearch::budgetBlock($r,false));
    }
    public function test_sources_only_use_valid_https_urls_without_credentials(): void
    {
        $r=HubManualAiResearch::sources(['webSources'=>[
            ['url'=>'https://example.org/product','title'=>'Example'],
            ['url'=>'https://example.org/product','title'=>'Example'],
            ['url'=>'javascript:alert(1)'],['url'=>'https://x:y@example.org/'],['url'=>'http://example.org/']
        ]]);
        self::assertSame([['url'=>'https://example.org/product','title'=>'Example']],$r);
    }
}
