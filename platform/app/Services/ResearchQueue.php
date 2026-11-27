<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Durable central job lifecycle. No provider requests, paid AI or product writes.
 * MariaDB row lock serializes all queue state transitions, including claims.
 * SQLite is allowed solely for isolated unit tests; real concurrency needs MariaDB.
 * Trusted server callers only: do not expose lease/finish directly to browsers.
 */
final class ResearchQueue
{
    private const MAX_ATTEMPTS=4;
    private const LEASE_SECONDS=180;
    private const GAP_SECONDS=15;
    public function __construct(private PDO $db)
    {
        if(!in_array($db->getAttribute(PDO::ATTR_DRIVER_NAME),['mysql','sqlite'],true)
            ||$db->getAttribute(PDO::ATTR_ERRMODE)!==PDO::ERRMODE_EXCEPTION)
            throw new InvalidArgumentException('Queue requires supported PDO with exceptions');
    }
    private function q(string $sql,array $args=[]): \PDOStatement
    {
        $q=$this->db->prepare($sql);$q->execute($args);return $q;
    }
    private function lockSuffix(): string
    {
        return $this->db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':'';
    }
    private function transaction(callable $fn): mixed
    {
        $this->db->beginTransaction();
        try {
            $gate=$this->q("SELECT * FROM hw24_research_gate WHERE scope_key='shared_workspace'".$this->lockSuffix())->fetch(PDO::FETCH_ASSOC);
            if(!$gate)throw new RuntimeException('Queue gate missing');
            // Database UTC clock in production avoids skew between workers.
            $now=$this->db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'
                ?(string)$this->q('SELECT UTC_TIMESTAMP()')->fetchColumn():gmdate('Y-m-d H:i:s');
            $v=$fn($gate,$now);
            $this->db->commit();return $v;
        }catch(Throwable $e){if($this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    private static function add(string $now,int $seconds): string
    {
        return gmdate('Y-m-d H:i:s',strtotime($now.' UTC')+$seconds);
    }
    private static function actor(string $actor): void
    {
        if(trim($actor)===''||strlen($actor)>180||preg_match('/[\x00-\x1f\x7f]/',$actor))
            throw new InvalidArgumentException('Invalid queue actor');
    }
    public static function canonical(string $ean): string
    {
        if(!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean)||preg_match('/^0+$/D',$ean))
            throw new InvalidArgumentException('Invalid GTIN');
        $sum=0;$w=3;
        for($i=strlen($ean)-2;$i>=0;$i--){$sum+=(int)$ean[$i]*$w;$w=$w===3?1:3;}
        if((10-$sum%10)%10!==(int)$ean[strlen($ean)-1])throw new InvalidArgumentException('Invalid GTIN check digit');
        return str_pad($ean,14,'0',STR_PAD_LEFT);
    }
    private function event(int $id,string $actor,string $type,?string $detail,string $now): void
    {
        $this->q('INSERT INTO hw24_research_events(job_id,actor,event_type,detail_code,occurred_at_utc) VALUES(?,?,?,?,?)',
            [$id,$actor,$type,$detail,$now]);
    }
    public function enqueue(string $ean,string $actor): array
    {
        return $this->enqueueMany([$ean],$actor)[0];
    }
    /** All accepted identifiers in a chunk are linked atomically; no provider calls. */
    public function enqueueMany(array $eans,string $actor): array
    {
        self::actor($actor);
        if(count($eans)<1||count($eans)>1000)throw new InvalidArgumentException('Invalid enqueue batch size');
        $keys=[];
        foreach($eans as $ean){
            if(!is_string($ean))throw new InvalidArgumentException('Invalid GTIN type');
            $keys[$ean]=self::canonical($ean);
        }
        return $this->transaction(function($gate,$now)use($keys,$actor){
            $out=[];
            foreach($keys as $original=>$key){
                $ean=(string)$original;
                $r=$this->q('SELECT id,status FROM hw24_research_jobs WHERE gtin_key=?',[$key])->fetch(PDO::FETCH_ASSOC);
                if($r){
                    $this->event((int)$r['id'],$actor,'linked',null,$now);
                    $out[]=['id'=>(int)$r['id'],'status'=>$r['status'],'created'=>false];
                    continue;
                }
                $this->q("INSERT INTO hw24_research_jobs(gtin_key,ean,status,requested_at_utc) VALUES(?,?,'queued',?)",[$key,$ean,$now]);
                $id=(int)$this->db->lastInsertId();
                $this->event($id,$actor,'queued',null,$now);
                $out[]=['id'=>$id,'status'=>'queued','created'=>true];
            }
            return $out;
        });
    }
    /** Read status for a bounded authorized scan selection, without provider calls. */
    public function statuses(array $eans): array
    {
        if(count($eans)>3000)throw new InvalidArgumentException('Too many identifiers');
        $keys=[];
        foreach($eans as $ean){
            if(!is_string($ean))throw new InvalidArgumentException('Invalid identifier');
            $keys[$ean]=self::canonical($ean);
        }
        $byKey=[];
        foreach(array_chunk(array_values(array_unique($keys)),500) as $chunk){
            $sql='SELECT id,gtin_key,status,attempt_count,completed_at_utc,last_error_code,evidence_json FROM hw24_research_jobs WHERE gtin_key IN ('.implode(',',array_fill(0,count($chunk),'?')).')';
            foreach($this->q($sql,$chunk)->fetchAll(PDO::FETCH_ASSOC) as $row){
                $key=$row['gtin_key'];unset($row['gtin_key']);
                $row['evidence']=json_decode($row['evidence_json']??'{}',true,16,JSON_THROW_ON_ERROR);
                unset($row['evidence_json']);$byKey[$key]=$row;
            }
        }
        $out=[];
        foreach($keys as $ean=>$key)if(isset($byKey[$key]))$out[$ean]=$byKey[$key];
        return $out;
    }
    /** One active lease across ALL trusted queue workers. */
    public function claim(string $actor,?array $allowedIds=null): ?array
    {
        self::actor($actor);
        if($allowedIds!==null){
            if(count($allowedIds)>3000)throw new InvalidArgumentException('Too many allowed jobs');
            foreach($allowedIds as $id)if(!is_int($id)||$id<1)throw new InvalidArgumentException('Invalid job ID');
            $allowedIds=array_values(array_unique($allowedIds));
            if(!$allowedIds)return null;
        }
        return $this->transaction(function($gate,$now)use($actor,$allowedIds){
            if($gate['active_job_id']!==null){
                $r=$this->q('SELECT * FROM hw24_research_jobs WHERE id=?',[$gate['active_job_id']])->fetch(PDO::FETCH_ASSOC);
                if(!$r||$r['status']!=='leased'||$r['lease_until_utc']===null)throw new RuntimeException('Inconsistent active lease');
                if($r['lease_until_utc']>$now)return null;
                $status=(int)$r['attempt_count']>=self::MAX_ATTEMPTS?'blocked':'retry';
                $this->q('UPDATE hw24_research_jobs SET status=?,next_attempt_utc=?,lease_token=NULL,lease_until_utc=NULL,leased_by=NULL,last_error_code=? WHERE id=?',
                    [$status,$status==='retry'?self::add($now,90):null,'lease_expired',$r['id']]);
                $this->event((int)$r['id'],$actor,$status,'lease_expired',$now);
                $this->q("UPDATE hw24_research_gate SET active_job_id=NULL,next_allowed_utc=? WHERE scope_key='shared_workspace'",
                    [max($gate['next_allowed_utc'],self::add($now,self::GAP_SECONDS))]);
                return null;
            }
            if($gate['next_allowed_utc']>$now)return null;
            $scope=$allowedIds===null?'':' AND id IN ('.implode(',',array_fill(0,count($allowedIds),'?')).')';
            $r=$this->q("SELECT id,ean,attempt_count FROM hw24_research_jobs WHERE status IN ('queued','retry')
                AND attempt_count<? AND (next_attempt_utc IS NULL OR next_attempt_utc<=?)".$scope.
                " ORDER BY requested_at_utc,id LIMIT 1",[self::MAX_ATTEMPTS,$now,...($allowedIds??[])])->fetch(PDO::FETCH_ASSOC);
            if(!$r)return null;
            $id=(int)$r['id'];$token=bin2hex(random_bytes(16));$until=self::add($now,self::LEASE_SECONDS);
            $this->q("UPDATE hw24_research_jobs SET status='leased',attempt_count=attempt_count+1,lease_token=?,lease_until_utc=?,leased_by=?,next_attempt_utc=NULL WHERE id=?",
                [$token,$until,$actor,$id]);
            $this->q("UPDATE hw24_research_gate SET active_job_id=? WHERE scope_key='shared_workspace'",[$id]);
            $this->event($id,$actor,'leased',null,$now);
            return ['id'=>$id,'ean'=>$r['ean'],'token'=>$token,'lease_until_utc'=>$until,'attempt'=>(int)$r['attempt_count']+1];
        });
    }
    /** Completion is a trusted worker decision; provider evidence check belongs to next phase. */
    public function finish(int $id,string $token,string $actor,string $outcome,int $retryAfter=0,array $evidence=[]): string
    {
        self::actor($actor);
        if($id<1||!preg_match('/^[a-f0-9]{32}$/D',$token)||
            !in_array($outcome,['completed','rate_limited','network_error','provider_error','identity_conflict','archive_unavailable','no_catalog_match'],true)
            ||$retryAfter<0||$retryAfter>86400)throw new InvalidArgumentException('Invalid queue result');
        // Only bounded internal references/status metadata, never provider payloads.
        $allowed=['jtl_state','history_state','history_count','ai_state','ai_scope','ai_count','catalog_state','catalog_snapshot_id','price_state','price_snapshot_id','seller_state','seller_count'];
        foreach($evidence as $k=>$v){
            if(!in_array($k,$allowed,true)||(!is_int($v)&&(!is_string($v)||!preg_match('/^[a-zA-Z0-9_]{1,40}$/D',$v))))
                throw new InvalidArgumentException('Invalid evidence metadata');
        }
        $json=json_encode($evidence,JSON_THROW_ON_ERROR);
        return $this->transaction(function($gate,$now)use($id,$token,$actor,$outcome,$retryAfter,$json){
            $r=$this->q('SELECT * FROM hw24_research_jobs WHERE id=?',[$id])->fetch(PDO::FETCH_ASSOC);
            if(!$r||(int)$gate['active_job_id']!==$id||$r['status']!=='leased'||$r['leased_by']!==$actor||
                !is_string($r['lease_token'])||!hash_equals($r['lease_token'],$token)||$r['lease_until_utc']<=$now)
                throw new RuntimeException('Lease not current');
            $attempt=(int)$r['attempt_count'];$status='completed';$delay=0;
            if($outcome!=='completed'){
                $status=in_array($outcome,['identity_conflict','no_catalog_match'],true)||$attempt>=self::MAX_ATTEMPTS?'blocked':'retry';
                $delay=max($retryAfter,$outcome==='rate_limited'?60:min(1800,90*(2**($attempt-1))));
            }
            $this->q('UPDATE hw24_research_jobs SET status=?,next_attempt_utc=?,completed_at_utc=?,last_error_code=?,evidence_json=?,lease_token=NULL,lease_until_utc=NULL,leased_by=NULL WHERE id=?',
                [$status,$status==='retry'?self::add($now,$delay):null,$status==='completed'?$now:null,$outcome==='completed'?null:$outcome,$json,$id]);
            $pause=$outcome==='rate_limited'?max(self::GAP_SECONDS,$delay):self::GAP_SECONDS;
            $this->q("UPDATE hw24_research_gate SET active_job_id=NULL,next_allowed_utc=? WHERE scope_key='shared_workspace'",
                [max($gate['next_allowed_utc'],self::add($now,$pause))]);
            $this->event($id,$actor,$status,$outcome==='completed'?null:$outcome,$now);
            return $status;
        });
    }
    /** Explicit refresh; completed/blocked jobs never re-run merely on another scan. */
    public function requeue(int $id,string $actor): void
    {
        self::actor($actor);
        $this->transaction(function($gate,$now)use($id,$actor){
            $r=$this->q('SELECT status FROM hw24_research_jobs WHERE id=?',[$id])->fetchColumn();
            if(!in_array($r,['completed','blocked'],true))throw new RuntimeException('Job not eligible for explicit refresh');
            $this->q("UPDATE hw24_research_jobs SET status='queued',attempt_count=0,requested_at_utc=?,next_attempt_utc=NULL,completed_at_utc=NULL,last_error_code=NULL,evidence_json=NULL WHERE id=?",[$now,$id]);
            $this->event($id,$actor,'requeued',null,$now);
        });
    }
    /** Shared status view excludes operator identities and lease tokens. */
    public function status(int $id): ?array
    {
        $r=$this->q('SELECT id,ean,status,attempt_count,requested_at_utc,next_attempt_utc,last_error_code,completed_at_utc,evidence_json FROM hw24_research_jobs WHERE id=?',[$id])->fetch(PDO::FETCH_ASSOC);
        return $r?:null;
    }
}
