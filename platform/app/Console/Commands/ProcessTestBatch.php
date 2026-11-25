<?php
declare(strict_types=1);
namespace App\Console\Commands;

use App\Services\HubTestBatchCycle;
use Illuminate\Console\Command;

final class ProcessTestBatch extends Command
{
    protected $signature='hw24:process-test-batch {batch : ID of approved open TEST batch} {--confirm : Confirm draft-only processing} {--research-one : Allow at most one scoped Amazon research job}';
    protected $description='Reuse completed research and prepare TEST drafts; optionally process one scoped queue job. No paid AI or import.';

    public function handle(HubTestBatchCycle $service): int
    {
        $id=filter_var($this->argument('batch'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!$id||!$this->option('confirm')){
            $this->error('Explicit TEST batch ID and --confirm required.');
            return self::FAILURE;
        }
        try{
            $result=$service->run($id,true,(bool)$this->option('research-one'));
            $this->line(json_encode($result,JSON_THROW_ON_ERROR));
            return $result['status']==='ok'?self::SUCCESS:self::FAILURE;
        }catch(\Throwable){
            $this->error('TEST batch cycle not confirmed; inspect queue and draft state before retry.');
            return self::FAILURE;
        }
    }
}
