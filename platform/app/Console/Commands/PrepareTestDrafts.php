<?php
declare(strict_types=1);
namespace App\Console\Commands;

use App\Services\HubAutomaticDrafts;
use Illuminate\Console\Command;

final class PrepareTestDrafts extends Command
{
    protected $signature='hw24:prepare-test-drafts {batch : ID of the approved open TEST scan batch} {--confirm : Approve automatic draft-only preparation}';
    protected $description='Prepare at most three untouched TEST drafts from verified completed research, without provider calls or release';

    public function handle(HubAutomaticDrafts $service): int
    {
        $id=filter_var($this->argument('batch'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if(!$id||!$this->option('confirm')){
            $this->error('Explicit TEST batch ID and --confirm required.');
            return self::FAILURE;
        }
        try{
            $result=$service->run($id,true);
            $this->line(json_encode($result,JSON_THROW_ON_ERROR));
            return $result['status']==='ok'?self::SUCCESS:self::FAILURE;
        }catch(\Throwable){
            $this->error('TEST draft preparation not confirmed; inspect batch before retry.');
            return self::FAILURE;
        }
    }
}
