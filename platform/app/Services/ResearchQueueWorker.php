<?php
declare(strict_types=1);
namespace App\Services;

use Throwable;

/** Server-side single bounded job, never a browser-supplied ACK. */
final class ResearchQueueWorker
{
    public function __construct(private ResearchQueue $queue,private ResearchJobProcessor $processor){}
    public function runOne(string $actor,?array $allowedIds=null): ?array
    {
        $lease=$this->queue->claim($actor,$allowedIds);
        if($lease===null)return null;
        try{$result=$this->processor->process($lease['ean']);}
        catch(Throwable){$result=['outcome'=>'archive_unavailable','retry_after'=>0,'evidence'=>[]];}
        $status=$this->queue->finish($lease['id'],$lease['token'],$actor,$result['outcome'],$result['retry_after'],$result['evidence']);
        return ['id'=>$lease['id'],'status'=>$status,'outcome'=>$result['outcome'],'evidence'=>$result['evidence']];
    }
}
