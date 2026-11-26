<?php
declare(strict_types=1);
namespace Tests\Unit;

use PDO;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__,2).'/ops/hw24-capture-article-history.php';

final class ArticleHistoryCaptureTest extends TestCase
{
    private function row(string $ean,string $name): array
    {
        return array_replace(array_fill_keys(array_keys(\Hw24ArticleHistoryCapture::FIELDS),null),
            ['ean'=>$ean,'artikelname'=>$name,'artikelnummer'=>'00123']);
    }
    private function meta(string $time,int $count,string $source='a'): array
    {
        return ['source_id'=>$source,'last_completed_utc'=>$time,'last_row_count'=>$count];
    }
    public function test_baseline_changes_new_missing_and_leading_zero(): void
    {
        $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $rows=[1=>$this->row('0615908418057','Alt'),2=>$this->row('0400000000000','Weg')];
        $m=$this->meta('2026-11-26 20:00:00',2);
        self::assertSame('baseline',\Hw24ArticleHistoryCapture::capture($db,$m,$rows)['status']);
        self::assertSame('already_captured',\Hw24ArticleHistoryCapture::capture($db,$m,$rows)['status']);
        self::assertSame(0,(int)$db->query('SELECT COUNT(*) FROM history_events')->fetchColumn());
        $rows=[1=>$this->row('0615908418057','Neu'),3=>$this->row('0000030163393','Neuanlage')];
        $r=\Hw24ArticleHistoryCapture::capture($db,$this->meta('2026-11-26 20:15:00',2),$rows);
        self::assertSame([1,1,1,1],[$r['new'],$r['changed'],$r['fields'],$r['missing']]);
        $e=$db->query("SELECT * FROM history_events WHERE kind='changed'")->fetch(PDO::FETCH_ASSOC);
        self::assertSame('0615908418057',$e['ean']);
        self::assertSame('00123',$e['article_number']);
        self::assertSame('Alt',$e['old_value']);self::assertSame('Neu',$e['new_value']);
        self::assertSame(5,(int)$db->query('SELECT COUNT(*) FROM history_versions')->fetchColumn());
        $r=\Hw24ArticleHistoryCapture::capture($db,$this->meta('2026-11-26 20:30:00',2,'b'),$rows);
        self::assertSame('baseline',$r['status']);
        self::assertSame(3,(int)$db->query('SELECT COUNT(*) FROM history_events')->fetchColumn());
    }
    public function test_timestamp_content_conflict_rolls_back(): void
    {
        $db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $m=$this->meta('2026-11-26 20:00:00',1);
        \Hw24ArticleHistoryCapture::capture($db,$m,[1=>$this->row('0615908418057','Alt')]);
        try{\Hw24ArticleHistoryCapture::capture($db,$m,[1=>$this->row('0615908418057','Neu')]);self::fail('must reject reused timestamp');}
        catch(\RuntimeException){}
        self::assertSame(1,(int)$db->query('SELECT COUNT(*) FROM history_runs')->fetchColumn());
        self::assertSame(0,(int)$db->query('SELECT COUNT(*) FROM history_events')->fetchColumn());
    }
}
