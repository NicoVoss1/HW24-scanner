<?php
declare(strict_types=1);
namespace App\Services;

use RuntimeException;

/** Durable daily attempt cap for the approved TEST batch. No paid OpenAI path. */
final class HubBatchSchedule
{
    public static function reserve(array $state,int $batch,int $limit,string $day): array
    {
        if(($state['version']??null)!==1||($state['batch']??null)!==$batch||
            ($state['limit']??null)!==$limit||$limit<1||$limit>100||
            !is_string($state['day']??null)||!preg_match('/^\d{4}-\d{2}-\d{2}$/D',$state['day'])||
            $state['day']>$day||!is_int($state['used']??null)||$state['used']<0||$state['used']>$limit||
            !is_int($state['cursor']??null)||$state['cursor']<0||!is_bool($state['paused']??null)||
            !is_bool($state['inflight']??null))
            throw new RuntimeException('Invalid schedule state');
        // Unknown previous process result: stop until inspected, even on the next day.
        if($state['paused']||$state['inflight'])throw new RuntimeException('Schedule paused or previous cycle unconfirmed');
        if($state['day']!==$day){$state['day']=$day;$state['used']=0;}
        $allow=$state['used']<$limit;
        if($allow)$state['used']++;
        $state['inflight']=true;
        return [$state,$allow];
    }

    public static function finish(array $state,bool $reserved,array $result): array
    {
        if(($result['status']??null)!=='ok'){
            $state['paused']=true;$state['inflight']=false;
            $state['last_status']='cycle_not_confirmed';return $state;
        }
        if($reserved&&array_key_exists('research',$result)&&$result['research']===null)$state['used']--;
        if(is_int($result['drafts']['cursor']??null)&&$result['drafts']['cursor']>=0)
            $state['cursor']=$result['drafts']['cursor'];
        $state['inflight']=false;
        $state['last_status']='ok';
        $state['last_result']=[
            'research_status'=>$result['research']['status']??null,
            'research_outcome'=>$result['research']['outcome']??null,
            'saved'=>(int)($result['drafts']['saved']??0),
            'waiting'=>(int)($result['drafts']['waiting']??0),
            'exceptions'=>(int)($result['drafts']['exceptions']??0),
        ];
        return $state;
    }
}
