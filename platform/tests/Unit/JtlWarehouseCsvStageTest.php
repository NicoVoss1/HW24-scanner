<?php
declare(strict_types=1);

namespace Tests\Unit;

use PDO;
use Symfony\Component\Process\Process;
use Tests\TestCase;

final class JtlWarehouseCsvStageTest extends TestCase
{
    private string $dir;
    private string $input;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/hw24_stock_' . bin2hex(random_bytes(6));
        mkdir($this->dir,0700);
        $this->input = $this->dir . '/warehouse.csv';
        $this->writeInput();
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        rmdir($this->dir);
        parent::tearDown();
    }

    private function writeInput(bool $duplicateKey=false, bool $missingRowversion=false): void
    {
        $rows = [
            [
                'jtl_article_id','jtl_warehouse_id','jtl_stock_raw',
                'jtl_available_blocked_raw','jtl_shipping_blocked_raw',
                'jtl_rowversion_hex',
            ],
            ['3','1','5.500000','1.000000','0','0x0000000004C0BE42'],
            ['3','2','-0.125','0','0',$missingRowversion?'':'0x0000000004C0BE43'],
            [$duplicateKey?'3':'4',$duplicateKey?'1':'1','0.000000','0','0','0x0000000004C0BE44'],
        ];
        $f=fopen($this->input,'wb');
        foreach ($rows as $row) {
            fputcsv($f,$row,';','"','');
        }
        fclose($f);
    }

    private function callScript(array $args): Process
    {
        $process=new Process(array_merge(
            [PHP_BINARY,__DIR__.'/../../ops/jtl-warehouse-csv-stage.php'],
            $args
        ));
        $process->run();
        return $process;
    }

    public function test_read_only_validation_exposes_counts_not_rows(): void
    {
        $r=$this->callScript(['--input='.$this->input]);
        $this->assertTrue($r->isSuccessful(),$r->getErrorOutput());
        $data=json_decode($r->getOutput(),true,512,JSON_THROW_ON_ERROR);
        $this->assertSame('validate_only',$data['mode']);
        $this->assertSame(3,$data['rows']);
        $this->assertSame(2,$data['quality']['nonzero_raw_stock_rows']);
        $this->assertSame(1,$data['quality']['positive_raw_stock_rows']);
        $this->assertSame(1,$data['quality']['negative_raw_stock_rows']);
        $this->assertSame(2,$data['quality']['distinct_articles']);
        $this->assertSame(2,$data['quality']['distinct_warehouses']);
        $this->assertFalse($data['sync_enabled']);
        $this->assertFileDoesNotExist($this->dir.'/new.sqlite');
    }

    public function test_explicit_new_private_stage_is_checksum_gated_and_never_overwrites(): void
    {
        $dest=$this->dir.'/new.sqlite';
        $bad=$this->callScript([
            '--input='.$this->input,'--apply','--db='.$dest,
            '--expected-count=3','--sha256='.str_repeat('0',64),
        ]);
        $this->assertFalse($bad->isSuccessful());
        $this->assertFileDoesNotExist($dest);

        $good=$this->callScript([
            '--input='.$this->input,'--apply','--db='.$dest,
            '--expected-count=3','--sha256='.hash_file('sha256',$this->input),
        ]);
        $this->assertTrue($good->isSuccessful(),$good->getErrorOutput());
        $db=new PDO('sqlite:'.$dest);
        $this->assertSame(3,(int)$db->query('SELECT COUNT(*) FROM jtl_warehouse_stock_stage')->fetchColumn());
        $this->assertSame('-0.125',$db->query('SELECT jtl_stock_raw FROM jtl_warehouse_stock_stage WHERE jtl_article_id=3 AND jtl_warehouse_id=2')->fetchColumn());
        $this->assertSame(0,(int)$db->query('SELECT is_consistent_cross_table_snapshot FROM stage_manifest WHERE id=1')->fetchColumn());
        unset($db);
        $again=$this->callScript([
            '--input='.$this->input,'--apply','--db='.$dest,
            '--expected-count=3','--sha256='.hash_file('sha256',$this->input),
        ]);
        $this->assertFalse($again->isSuccessful());
    }

    public function test_missing_source_rowversion_can_validate_but_cannot_apply(): void
    {
        $this->writeInput(false,true);
        $dry=$this->callScript(['--input='.$this->input]);
        $this->assertTrue($dry->isSuccessful());
        $apply=$this->callScript([
            '--input='.$this->input,'--apply','--db='.$this->dir.'/new.sqlite',
            '--expected-count=3','--sha256='.hash_file('sha256',$this->input),
        ]);
        $this->assertFalse($apply->isSuccessful());
        $this->assertFileDoesNotExist($this->dir.'/new.sqlite');
    }

    public function test_composite_warehouse_key_must_be_unique(): void
    {
        $this->writeInput(true);
        $dry=$this->callScript(['--input='.$this->input]);
        $this->assertFalse($dry->isSuccessful());
        $this->assertStringContainsString('duplicate composite',$dry->getErrorOutput());
    }
}
