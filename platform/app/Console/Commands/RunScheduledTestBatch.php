<?php
declare(strict_types=1);
namespace App\Console\Commands;

use App\Services\HubBatchSchedule;
use App\Services\HubTestBatchCycle;
use Illuminate\Console\Command;
use RuntimeException;

final class RunScheduledTestBatch extends Command
{
    protected $signature='hw24:scheduled-test-batch {batch} {--daily-limit=12} {--all-test : Explicitly enable all authorized open TEST batches} {--confirm}';
    protected $description='Run the approved TEST batch under a durable daily attempt limit; no paid AI or JTL import';

    private static function persist($handle,array $state): void
    {
        $data=json_encode($state,JSON_THROW_ON_ERROR);
        if(!rewind($handle)||!ftruncate($handle,0)||fwrite($handle,$data)!==strlen($data)||!fflush($handle))
            throw new RuntimeException('Schedule state not persisted');
    }

    public function handle(HubTestBatchCycle $cycle): int
    {
        $batch=filter_var($this->argument('batch'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        $limit=filter_var($this->option('daily-limit'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>100]]);
        if(!$batch||!$limit||!$this->option('confirm')||config('hw24_legacy.openai_auto_calls')!==false||
            config('hw24_legacy.amazon_auto_calls')!==false)return self::FAILURE;
        $path=dirname(base_path(),2).'/batch-cycle/state.json';
        if(!is_file($path)||is_link($path)||(fileperms($path)&0077)!==0)return self::FAILURE;
        $handle=fopen($path,'r+');
        if(!$handle)return self::FAILURE;
        try{
            if(!flock($handle,LOCK_EX|LOCK_NB))return self::SUCCESS;
            $state=json_decode((string)stream_get_contents($handle),true,16,JSON_THROW_ON_ERROR);
            if(!is_array($state))throw new RuntimeException('Invalid schedule state');
            $target=$batch;
            if($this->option('all-test')){
                $target=HubTestBatchCycle::nextBatch($cycle->discover(),(int)($state['last_batch']??0));
                if($target===null){$this->line('No authorized open TEST batches');return self::SUCCESS;}
            }
            [$state,$allow]=HubBatchSchedule::reserve($state,$batch,$limit,gmdate('Y-m-d'));
            $state['last_started_utc']=gmdate('c');
            self::persist($handle,$state);
            $priorCursor=$state['cursor'];
            $cursor=$this->option('all-test')?(int)($state['batch_cursors'][$target]??0):$priorCursor;
            $r=$cycle->run($target,true,$allow,$cursor);
            $state=HubBatchSchedule::finish($state,$allow,$r);
            if($this->option('all-test')){
                $state['batch_cursors'][$target]=$state['cursor'];
                $state['cursor']=$priorCursor;
                $state['last_batch']=$target;
            }
            $state['last_finished_utc']=gmdate('c');
            self::persist($handle,$state);
            $this->line(json_encode($state,JSON_THROW_ON_ERROR));
            return $state['paused']?self::FAILURE:self::SUCCESS;
        }catch(\Throwable){
            $this->error('Scheduled TEST cycle stopped or not confirmed. Inspect private schedule state before retry.');
            return self::FAILURE;
        }finally{flock($handle,LOCK_UN);fclose($handle);}
    }
}
