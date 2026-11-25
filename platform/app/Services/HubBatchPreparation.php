<?php
declare(strict_types=1);
namespace App\Services;

/** Build a proposal from a verified archived item; no provider calls and no existing value replacement. */
final class HubBatchPreparation
{
    /** One bounded read-only batch; reuse existing archive, never query Amazon here. */
    public function prepare(array $rows,array $jobs): array
    {
        $db=HubPeerMirror::connect();
        $out=[];$aiReads=0;
        foreach($rows as $row){
            $ean=(string)$row['ean'];$job=$jobs[$ean]??[];
            if(($row['resolution']??null)!=='new_candidate'||in_array($row['review']??null,['removed','ready'],true))continue;
            $archive=HubResearchEvidence::archivedCatalog($db,$job);
            $p=self::proposal($row,$job,$archive);
            if(in_array($p['state'],['review','missing'],true)){
                $catalog=HubResearchEvidence::catalogItem($ean,$job,$archive);
                $ai=['state'=>'not_checked'];
                if($catalog!==null&&$aiReads<3){
                    $aiReads++;
                    try{$ai=app(HubAiEvidence::class)->read($ean,$catalog);}
                    catch(\Throwable){$ai=['state'=>'unavailable'];}
                }
                $p['ai']=$ai;
                if(in_array($ai['state'],['identity_conflict','review','stale'],true)){
                    $p['state']='waiting';$p['fields']=[];$p['sources']=[];
                    $p['reason']='KI-Archiv erfordert Prüfung; keine automatische Übernahme';
                }
            }
            if(in_array($p['state'],['review','missing'],true)){
                try {
                    $template=(new HubArticleMirror())->standardProposals($db,$p['draft'],config('hw24_jtl_reference_observations',[]),HubAiEvidence::groupHint($p['draft'],$p['ai']??[]));
                    $p=self::mergeTemplate($p,$template);
                }catch(\Throwable){$p['template_reason']='Vorlagenquelle derzeit nicht bestätigt';}
            }
            if(in_array($p['state'],['review','missing'],true)){
                $price=HubResearchEvidence::marketPrice($job,(string)($p['draft']['ASIN']??''));
                if($price!==null)$p['market_price']=$price;
            }
            $out[$ean]=$p+['revision'=>$row['revision'],'item_id'=>$row['id']];
        }
        return $out;
    }

    public static function mergeTemplate(array $p,array $template): array
    {
        if(!in_array($p['state']??null,['review','missing'],true))return $p;
        foreach($template['fields']??[] as $field=>$v){
            if(!in_array($field,ScanResearch::FIELDS,true)||trim((string)($p['draft'][$field]??''))!==''||!is_string($v)||$v==='')continue;
            $p['draft'][$field]=$v;$p['fields'][$field]=$v;$p['sources'][$field]=$template['sources'][$field];
        }
        $p['missing']=array_values(array_filter(ScanResearch::REQUIRED,fn($f)=>trim((string)($p['draft'][$f]??''))===''));
        $p['state']=$p['missing']===[]?'review':'missing';
        $p['template_count']=$template['count']??0;$p['template_reason']=$template['reason']??'';
        return $p;
    }

    public static function proposal(array $row,array $job,array $archive): array
    {
        $ean=(string)($row['ean']??'');$e=$job['evidence']??[];
        $draft=is_array($row['draft']??null)?$row['draft']:[];
        $out=['fields'=>[],'sources'=>[],'missing'=>[],'state'=>'waiting'];
        if(($row['resolution']??null)!=='new_candidate'||in_array($row['review']??null,['removed','ready'],true))return $out+['reason'=>'Keine offene Neuanlage'];
        if(($job['status']??null)!=='completed'||($e['jtl_state']??null)!=='new_candidate'||
            !in_array($e['catalog_state']??null,['live','cache'],true)||
            (int)($e['catalog_snapshot_id']??0)<1||
            (int)($archive['snapshot_id']??0)!==(int)$e['catalog_snapshot_id'])
            return $out+['reason'=>'Bestätigter Rechercheauftrag mit Katalogarchiv erforderlich'];
        $catalog=HubResearchEvidence::catalogItem($ean,$job,$archive);
        if($catalog===null)return $out+['reason'=>'Produktidentität, Archiv oder Packungsgröße offen'];
        $p=$catalog['item'];
        $source='Amazon-Katalogarchiv #'.(int)$archive['snapshot_id'];
        $add=static function(string $f,mixed $v,string $origin)use(&$draft,&$out):void{
            if(!is_string($v)||trim($v)===''||trim((string)($draft[$f]??''))!=='')return;
            $draft[$f]=$v;$out['fields'][$f]=$v;$out['sources'][$f]=$origin;
        };
        // Never combine metadata from competing products.
        if(trim((string)($draft['ASIN']??''))!==''&&strtoupper(trim((string)$draft['ASIN']))!==$p['asin'])
            return $out+['reason'=>'ASIN-Konflikt im Entwurf'];
        if(trim((string)($draft['Hersteller']??''))!==''&&$p['manufacturer']!==''&&
            mb_strtolower(trim((string)$draft['Hersteller']))!==mb_strtolower($p['manufacturer']))
            return $out+['reason'=>'Herstellerkonflikt im Entwurf'];
        $add('GTIN',$ean,'Scan');
        $add('ASIN',$p['asin'],$source);
        $add('Artikelname',$p['title'],$source);
        $add('Hersteller',$p['manufacturer'],$source);
        $attributes=$catalog['attributes'];
        // Exact distinct part-number value, not a guessed model number.
        $parts=[];
        foreach(is_array($attributes['part_number']??null)?$attributes['part_number']:[] as $a){
            if(is_array($a)&&is_string($a['value']??null)&&trim($a['value'])!==''&&mb_strlen(trim($a['value']))<=140)$parts[trim($a['value'])]=true;
        }
        if(count($parts)===1)$add('HAN',(string)array_key_first($parts),$source.' part_number');
        // Explicit single volume in title, no weights or inferred pack quantities.
        if(preg_match_all('/\b(\d+(?:[,.]\d+)?)\s*ml\b/iu',$p['title'],$m)===1){
            $amount=str_replace(',','.',$m[1][0]);
            if((float)$amount>0&&(trim((string)($draft['Maßeinheit']??''))===''||mb_strtolower(trim((string)$draft['Maßeinheit']))==='ml')){
                $add('Inhalt/Menge',$amount,$source.' Titelangabe');
                $add('Maßeinheit','ml',$source.' Titelangabe');
            }
        }
        foreach(HubCategoryPolicy::suggest((string)($draft['Artikelname']??''),(string)($draft['Warengruppe']??''))??[] as $f=>$v)
            $add($f,$v,'HW24-Kategorieregel');
        foreach(HubTemplateDefaults::emptyTexts($draft) as $f=>$v)$add($f,$v,'HW24-Regel: eigener Artikelname');
        foreach(HubSeoDefaults::propose($draft) as $f=>$v)$add($f,$v,'HW24-SEO-Basisregel (kein KI-Aufruf)');
        $out['missing']=array_values(array_filter(ScanResearch::REQUIRED,fn($f)=>trim((string)($draft[$f]??''))===''));
        $out['state']=$out['missing']===[]?'review':'missing';
        return $out+['reason'=>'Vorschlag, keine Freigabe','draft'=>$draft];
    }
}
