<?php
declare(strict_types=1);
namespace Tests\Unit;
use App\Services\HubFieldPolicy as P;
use App\Services\ScanResearch;
use PHPUnit\Framework\TestCase;

final class HubFieldPolicyTest extends TestCase
{
    public function test_private_policy_revision_and_shared_mandatory_checks():void
    {
        $dir=sys_get_temp_dir().'/hw24-policy-'.bin2hex(random_bytes(6));mkdir($dir,0700);$path=$dir.'/policy.json';
        try{
            self::assertSame(ScanResearch::REQUIRED,P::read($path)['required']);
            $p=P::save(['HAN','ASIN'],0,'7',$path);
            self::assertSame(1,$p['revision']);self::assertSame(0600,fileperms($path)&0777);
            self::assertContains('Hersteller',$p['required']);
            self::assertContains('HAN',$p['required']);
            self::assertSame(['HAN'],P::missing([],['HAN']));
            self::assertSame(['Hersteller'],P::missingAfterUpdate(['HAN'=>['state'=>'value','value'=>'00012']],[],['HAN','Hersteller']));
            self::assertSame([],P::missingAfterUpdate(['HAN'=>['state'=>'value','value'=>'00012']],['Hersteller'=>'Revlon'],['HAN','Hersteller']));
            self::assertCount(41,P::review([],[],$p['required']));
            try{P::save([],0,'7',$path);self::fail('Stale policy accepted');}catch(\RuntimeException){}
            self::assertSame($p,P::read($path));
        }finally{foreach(glob($dir.'/*')?:[] as $f)unlink($f);rmdir($dir);}
    }
    public function test_reserved_number_cannot_be_a_new_article_mandatory_value():void
    {
        $this->expectException(\RuntimeException::class);P::normalize(['Artikelnummer']);
    }
}
