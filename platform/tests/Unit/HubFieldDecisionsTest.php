<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Services\HubFieldDecisions as D;
use PHPUnit\Framework\TestCase;

final class HubFieldDecisionsTest extends TestCase
{
    public function test_explicit_decisions_only_and_same_source_contract():void
    {
        $options=D::options(['state'=>'review','fields'=>['HAN'=>'000123','Beschreibung'=>'Text','Unbekannt'=>'X'],
            'sources'=>['HAN'=>'Archiv #1','Beschreibung'=>'Amazon #2','Unbekannt'=>'X']]);
        self::assertCount(2,$options);
        self::assertSame(['fields'=>[],'sources'=>[]],D::resolve($options,[]));
        $selected=D::unique($options);
        $selected['Beschreibung']='';
        self::assertSame(['fields'=>['HAN'=>'000123'],'sources'=>['HAN'=>'Archiv #1']],D::resolve($options,$selected));
        $options['HAN']['alternative']=['value'=>'000456','source'=>'Andere Quelle'];
        self::assertArrayNotHasKey('HAN',D::unique($options));
    }

    public function test_unverified_proposal_has_no_selectable_values():void
    {
        self::assertSame([],D::options(['state'=>'waiting','fields'=>['HAN'=>'000123'],'sources'=>['HAN'=>'Archiv']]));
        self::assertSame([],D::options(['state'=>'review','fields'=>['HAN'=>'000123'],'sources'=>[]]));
    }

    public function test_forged_decision_is_rejected():void
    {
        $this->expectException(\RuntimeException::class);
        D::resolve(['HAN'=>['valid'=>['value'=>'000123','source'=>'Archiv']]],['HAN'=>'forged']);
    }
}
