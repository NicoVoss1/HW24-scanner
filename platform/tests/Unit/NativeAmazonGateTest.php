<?php
declare(strict_types=1);
namespace Tests\Unit;

use App\Services\NativeAmazonGate;
use PHPUnit\Framework\TestCase;

final class NativeAmazonGateTest extends TestCase
{
    public function test_shared_gate_denies_concurrent_and_rate_limited_requests(): void
    {
        $p=tempnam(sys_get_temp_dir(),'hw24-native-gate-');
        self::assertIsString($p);
        chmod($p,0600);file_put_contents($p,"0\n");
        $calls=0;
        $send=function()use(&$calls){$calls++;return ['ok'=>true,'http_status'=>200];};
        try{
            $held=fopen($p,'r+');
            self::assertTrue(flock($held,LOCK_EX));
            try{
                self::assertSame(429,NativeAmazonGate::withFile($p,'amazon.catalog',$send)['http_status']);
            }finally{flock($held,LOCK_UN);fclose($held);}
            self::assertSame(0,$calls);
            self::assertTrue(NativeAmazonGate::withFile($p,'amazon.catalog',$send)['ok']);
            self::assertSame(429,NativeAmazonGate::withFile($p,'amazon.pricing',$send)['http_status']);
            self::assertSame(1,$calls);
            file_put_contents($p,"0\n");
            $r=NativeAmazonGate::withFile($p,'amazon.catalog',fn()=>['ok'=>false,'http_status'=>502,
                'retry_after_seconds'=>180,'data'=>['upstream_status'=>429]]);
            self::assertSame(502,$r['http_status']);
            self::assertGreaterThanOrEqual(time()+175,(int)trim(file_get_contents($p)));
            self::assertSame(429,NativeAmazonGate::withFile($p,'amazon.keywords',$send)['http_status']);
            self::assertSame(1,$calls);
            file_put_contents($p,'invalid');
            self::assertSame(503,NativeAmazonGate::withFile($p,'amazon.catalog',$send)['http_status']);
            self::assertSame(1,$calls);
        }finally{unlink($p);}
    }
}
