<?php
declare(strict_types=1);
namespace App\Services;

use PDO;

/** Historical sources are evidence, not live JTL state. Never copy stock, prices or SKU. */
final class HubReferenceEvidence
{
    private static function open(string $source): ?PDO
    {
        $dir=dirname(base_path(),2).'/'.$source.'-reference';
        $path=$dir.'/reference.sqlite';
        if(!is_dir($dir)||is_link($dir)||(fileperms($dir)&0077)!==0||!is_file($path)||is_link($path)||(fileperms($path)&0077)!==0)return null;
        $db=new PDO('sqlite:'.$path,null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        $db->exec('PRAGMA query_only=ON; PRAGMA busy_timeout=3000');
        return $db;
    }

    private static function canonical(mixed $v): ?string
    {
        if(!is_string($v))return null;
        try{return ResearchQueue::canonical(trim($v));}catch(\Throwable){return null;}
    }

    public static function lookup(string $ean): array
    {
        $out=[];
        foreach(['ameise','historical'] as $source){
            try{
                $db=self::open($source);
                $out[$source]=$db?self::read($db,$source,$ean):['state'=>'unavailable','rows'=>[]];
            }catch(\Throwable){$out[$source]=['state'=>'unavailable','rows'=>[]];}
        }
        return $out;
    }

    /** Exact internal ID plus article number: works even if the archived GTIN is empty. */
    public static function existingArticleRows(string $id,string $number):array
    {
        if(!ctype_digit($id)||(int)$id<1||$number==='')return [];
        try{
            $db=self::open('ameise');if(!$db)return [];
            $meta=$db->query('SELECT id FROM ameise_exports ORDER BY export_date DESC,id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            if(!$meta)return [];
            $q=$db->prepare('SELECT row_number,values_json FROM ameise_rows WHERE export_id=? AND article_id=? AND article_number=? LIMIT 51');
            $q->execute([$meta['id'],$id,$number]);$rows=$q->fetchAll(PDO::FETCH_ASSOC);
            if(count($rows)>50)return [];
            $q=$db->prepare('SELECT position,name FROM ameise_columns WHERE export_id=?');$q->execute([$meta['id']]);
            $cols=$q->fetchAll(PDO::FETCH_ASSOC);$counts=array_count_values(array_column($cols,'name'));$out=[];
            foreach($rows as $r){
                $raw=json_decode($r['values_json'],true,32,JSON_THROW_ON_ERROR);$fields=[];
                foreach($cols as $c)if(($counts[$c['name']]??0)===1&&in_array($c['name'],ScanResearch::FIELDS,true)&&is_string($raw[(string)$c['position']]??null)&&trim($raw[(string)$c['position']])!=='')
                    $fields[$c['name']]=$raw[(string)$c['position']];
                $out[]=['row'=>(int)$r['row_number'],'article_id'=>$id,'fields'=>$fields];
            }
            return $out;
        }catch(\Throwable){return [];}
    }

    /** Existing exact original field values for explicit reference dropdowns.
     * These are archived choices, not live master-data validation. */
    public static function referenceLists():array
    {
        try{
            $db=self::open('ameise');if(!$db)return [];
            $id=$db->query('SELECT id FROM ameise_exports ORDER BY export_date DESC,id DESC LIMIT 1')->fetchColumn();
            if(!$id)return [];
            $out=[];
            foreach(['Artikeltyp','Währung','Warengruppe','Versandklasse','Kategorie Ebene 1','Kategorie Ebene 2','Kategorie Ebene 3'] as $f){
                $q=$db->prepare('SELECT position FROM ameise_columns WHERE export_id=? AND name=? LIMIT 2');$q->execute([$id,$f]);
                $positions=$q->fetchAll(PDO::FETCH_COLUMN);if(count($positions)!==1)continue;
                $p='$."'.(int)$positions[0].'"';
                $q=$db->prepare("SELECT DISTINCT CAST(json_extract(values_json,?) AS TEXT) AS v FROM ameise_rows WHERE export_id=? AND json_type(values_json,?)='text' AND TRIM(CAST(json_extract(values_json,?) AS TEXT))<>'' AND length(CAST(json_extract(values_json,?) AS TEXT))<=300 ORDER BY v LIMIT 201");
                $q->execute([$p,$id,$p,$p,$p]);$values=$q->fetchAll(PDO::FETCH_COLUMN);
                if(count($values)<=200)$out[$f]=$values;
            }
            return $out;
        }catch(\Throwable){return [];}
    }

    public static function read(PDO $db,string $source,string $ean): array
    {
        $canonical=ResearchQueue::canonical($ean);$key=ltrim($canonical,'0');
        if($source==='ameise'){
            $meta=$db->query('SELECT id,export_date FROM ameise_exports ORDER BY export_date DESC,id DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
            if(!$meta)return ['state'=>'missing','rows'=>[]];
            $q=$db->prepare("SELECT row_number,article_id,article_number,gtin,values_json FROM ameise_rows WHERE export_id=? AND ltrim(gtin,'0')=? LIMIT 51");
            $q->execute([$meta['id'],$key]);
            $cols=$db->prepare('SELECT position,name FROM ameise_columns WHERE export_id=?');$cols->execute([$meta['id']]);
            $map=[];foreach($cols as $c)if(in_array($c['name'],ScanResearch::FIELDS,true))$map[(string)$c['position']]=$c['name'];
        }elseif($source==='historical'){
            $meta=$db->query("SELECT s.id,s.workbook_id FROM workbook_sheets s WHERE s.name='JTL-Import aus AB 1Produkte_ALL' ORDER BY s.workbook_id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if(!$meta)return ['state'=>'missing','rows'=>[]];
            $projection=HubHistoricalEdits::projection($db);
            $q=$db->prepare("SELECT row_number,values_json FROM $projection WHERE sheet_id=? AND (ltrim(CAST(json_extract(values_json,'$.\"5\"') AS TEXT),'0')=? OR ltrim(CAST(json_extract(values_json,'$.\"4\"') AS TEXT),'0')=?) LIMIT 51");
            $q->execute([$meta['id'],$key,$key]);
            $map=['5'=>'GTIN','6'=>'ASIN','8'=>'Artikelname','9'=>'Kurzbeschreibung','10'=>'Beschreibung','49'=>'Hersteller','56'=>'HAN'];
            // Additional exact original header matches are review evidence only.
            // Ignore duplicate headings and do not infer semantic equivalence.
            $columns=$db->prepare('SELECT position,name FROM workbook_columns WHERE sheet_id=?');$columns->execute([$meta['id']]);
            $headers=$columns->fetchAll(PDO::FETCH_ASSOC);
            $counts=array_count_values(array_column($headers,'name'));
            foreach($headers as $c){
                if(($counts[$c['name']]??0)===1&&in_array($c['name'],ScanResearch::FIELDS,true)&&
                   !isset($map[(string)$c['position']])&&!in_array($c['name'],$map,true))$map[(string)$c['position']]=$c['name'];
            }
        }else throw new \InvalidArgumentException('Unknown reference source');
        $rows=[];$count=0;
        foreach($q as $r){
            if(++$count>50)return ['state'=>'ambiguous','rows'=>[]];
            $raw=json_decode($r['values_json'],true,32,JSON_THROW_ON_ERROR);
            $primary=$source==='ameise'?self::canonical($r['gtin']):self::canonical($raw['5']??null);
            $secondary=$source==='historical'?self::canonical($raw['4']??null):null;
            if($primary!==$canonical&&$secondary!==$canonical)continue;
            if($primary!==null&&$secondary!==null&&$primary!==$secondary)return ['state'=>'ambiguous','rows'=>[]];
            $fields=[];
            foreach($map as $pos=>$label){
                $v=$raw[$pos]??null;
                if(is_string($v)&&trim($v)!=='')$fields[$label]=$v;
            }
            $rows[]=['row'=>(int)$r['row_number'],'article_id'=>$r['article_id']??null,'fields'=>$fields,
                'populated_count'=>count(array_filter($raw,fn($v)=>is_string($v)&&trim($v)!=='')),
                'requires_review'=>$source==='historical'&&(
                    trim((string)($raw['35']??''))!==''||in_array($raw['11']??'', ['bearbeiten'],true)||
                    in_array($raw['12']??'', ['bearbeiten'],true))];
        }
        return ['state'=>$rows?'found':'missing','rows'=>$rows,'archive'=>(int)$meta['id'],'date'=>$meta['export_date']??null];
    }

    /** Display archival field differences as review evidence, never as current values. */
    public static function comparison(array $current,array $references):array
    {
        $out=[];
        foreach($references as $source=>$ref){
            $rows=[];
            foreach(array_slice($ref['rows']??[],0,5) as $r){
                $fields=[];
                foreach($r['fields']??[] as $name=>$value){
                    $live=$current[$name]??[];
                    if(($live['state']??null)==='value'&&trim((string)($live['value']??''))===trim($value))continue;
                    $fields[$name]=['current'=>(string)($live['value']??''),'state'=>$live['state']??'unmapped','reference'=>mb_substr($value,0,500)];
                }
                $rows[]=['row'=>$r['row'],'populated_count'=>$r['populated_count']??0,'fields'=>$fields,'requires_review'=>$r['requires_review']??false];
            }
            $out[$source]=['state'=>$ref['state'],'archive'=>$ref['archive']??null,'date'=>$ref['date']??null,'total'=>count($ref['rows']??[]),'rows'=>$rows];
        }
        return $out;
    }

    /** Only fill historical text and HAN if one clean row and its stored ASIN
     * AND manufacturer agree with current verified single-pack catalog evidence.
     * A historical JTL hit remains a review exception, never a new auto-draft.
     */
    public static function merge(array $proposal,array $original,array $catalog,array $references): array
    {
        $proposal['references']=array_map(fn($r)=>['state'=>$r['state'],'count'=>count($r['rows']??[]),'archive'=>$r['archive']??null,
            'populated'=>max([0,...array_column($r['rows']??[],'populated_count')])],$references);
        // Even when Amazon has no verified single-pack match, expose historical
        // product facts as a human review aid, never as an automatically saved draft.
        $historical=$references['historical']??[];
        if(($historical['state']??null)==='found'&&count($historical['rows']??[])===1){
            $row=$historical['rows'][0];
            $hints=[];
            foreach(['GTIN','ASIN','Artikelname','Hersteller','HAN','Kurzbeschreibung','Beschreibung'] as $name){
                if(is_string($row['fields'][$name]??null))$hints[$name]=mb_substr($row['fields'][$name],0,500);
            }
            $proposal['reference_hints']=['row'=>$row['row'],'fields'=>$hints,'requires_review'=>$row['requires_review']??true];
        }
        if(!in_array($proposal['state']??null,['review','missing'],true))return $proposal;
        if(in_array($references['ameise']['state']??null,['found','ambiguous'],true)){
            $proposal['state']='waiting';$proposal['fields']=[];$proposal['sources']=[];$proposal['draft']=$original;
            $proposal['reason']='Historischer JTL-Treffer trotz aktuellem fehlendem Spiegelartikel: Wiederanlage prüfen.';
            return $proposal;
        }
        $r=$references['historical']??[];$rows=$r['rows']??[];
        if(($r['state']??null)==='found'&&count($rows)===1&&!$rows[0]['requires_review']){
            $h=$rows[0]['fields'];$p=$catalog['item']??[];
            if(preg_match('/^[A-Z0-9]{10}$/D',(string)($h['ASIN']??''))&&($h['ASIN']??null)===($p['asin']??null)&&
                !empty($h['Hersteller'])&&!empty($p['manufacturer'])&&HubManufacturerPolicy::equivalent($h['Hersteller'],$p['manufacturer'])){
                foreach(['HAN','Kurzbeschreibung','Beschreibung'] as $f){
                    $value=$h[$f]??'';
                    if(!is_string($value)||trim($value)===''||mb_strlen($value)>($f==='HAN'?140:30000)||trim((string)($original[$f]??''))!=='')continue;
                    $source=$proposal['sources'][$f]??'';
                    if(isset($proposal['draft'][$f])&&!str_starts_with($source,'HW24-Regel:')&&!str_starts_with($source,'HW24-HAN-Ersatz:'))continue;
                    $proposal['draft'][$f]=$value;$proposal['fields'][$f]=$value;
                    $proposal['sources'][$f]='Historische Artikeldaten #'.$r['archive'].' · Zeile '.$rows[0]['row'].' · ASIN/Hersteller aktuell abgeglichen';
                }
            }
        }
        return $proposal;
    }

    /** Operator-approved replacement only if no authoritative HAN is available. */
    public static function hanFallback(array $p,string $ean): array
    {
        if(in_array($p['state']??null,['review','missing'],true)&&trim((string)($p['draft']['HAN']??''))===''){
            ResearchQueue::canonical($ean);
            $p['draft']['HAN']=$ean;$p['fields']['HAN']=$ean;$p['sources']['HAN']='HW24-HAN-Ersatz: GTIN; keine Herstellerartikelnummer belegt';
        }
        return $p;
    }
}
