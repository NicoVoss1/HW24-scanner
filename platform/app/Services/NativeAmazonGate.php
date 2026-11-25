<?php
declare(strict_types=1);
namespace App\Services;

use Closure;
use RuntimeException;
use Throwable;

/** Same-host native bridge gate, shared by PHP-FPM and CLI.
 * Direct legacy requests outside this bridge are NOT covered.
 * Existing private 0600 state file required, with no automatic fallback.
 */
final class NativeAmazonGate
{
    public static function run(string $service,Closure $send): array
    {
        if(config('hw24_research_queue.native_amazon_gate')!==true ||
            !in_array($service,['amazon.catalog','amazon.pricing','amazon.keywords'],true))return $send();
        return self::withFile(dirname(base_path(),2).'/hw24-native-amazon-gate.state',$service,$send);
    }

    public static function withFile(string $path,string $service,Closure $send): array
    {
        $unavailable=['ok'=>false,'service'=>$service,'state'=>'provider_gate_unavailable','http_status'=>503,'data'=>null];
        if(!is_file($path)||is_link($path)||!is_readable($path)||!is_writable($path)||(fileperms($path)&0077)!==0)return $unavailable;
        $f=@fopen($path,'r+');
        if($f===false)return $unavailable;
        try{
            if(!flock($f,LOCK_EX|LOCK_NB))return self::blocked($service,15);
            $raw=stream_get_contents($f,64);
            if(!is_string($raw)||!preg_match('/^[0-9]{1,12}\n?$/D',$raw))return $unavailable;
            $until=(int)trim($raw);$now=time();
            if($until>$now)return self::blocked($service,min(86400,$until-$now));
            // Crash/transport reserve: upstream may continue after client timeout.
            self::write($f,$now+90);
            try{$r=$send();}catch(Throwable){return $unavailable;}
            $d=is_array($r['data']??null)?$r['data']:[];
            $limited=($r['http_status']??null)===429||($d['upstream_status']??null)===429;
            $delay=$limited?max(60,min(86400,max((int)($r['retry_after_seconds']??0),(int)($d['retry_after_seconds']??0)))):
                (($r['http_status']??null)===null?90:15);
            self::write($f,time()+$delay);
            return $r;
        }catch(Throwable){return $unavailable;}
        finally{flock($f,LOCK_UN);fclose($f);}
    }

    private static function blocked(string $service,int $delay): array
    {
        return ['ok'=>false,'service'=>$service,'state'=>'native_amazon_gate_busy','http_status'=>429,
            'retry_after_seconds'=>$delay,'data'=>['ok'=>false,'error'=>'native_amazon_gate_busy']];
    }

    private static function write($f,int $until): void
    {
        $s=(string)$until."\n";
        if(!rewind($f)||!ftruncate($f,0)||fwrite($f,$s)!==strlen($s)||!fflush($f))
            throw new RuntimeException('Cannot persist provider gate');
    }
}
