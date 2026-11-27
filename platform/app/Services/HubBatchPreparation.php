<?php
declare(strict_types=1);
namespace App\Services;

/** Build a proposal from a verified archived item; no provider calls and no existing value replacement. */
final class HubBatchPreparation
{
    /** One bounded read-only batch; reuse existing archive, never query Amazon here. */
    public function prepare(array $rows,array $jobs,bool $allowOnePaidReview=false): array
    {
        $db=HubPeerMirror::connect();
        $out=[];$aiReads=0;$paidAttempted=false;
        foreach($rows as $row){
            $ean=(string)$row['ean'];$job=$jobs[$ean]??[];
            if(($row['resolution']??null)!=='new_candidate'||in_array($row['review']??null,['removed','ready'],true))continue;
            $archive=HubResearchEvidence::archivedCatalog($db,$job);
            $p=self::proposal($row,$job,$archive);
            $catalog=HubResearchEvidence::catalogItem($ean,$job,$archive);
            $p=HubReferenceEvidence::merge($p,$row['draft']??[],$catalog??[],HubReferenceEvidence::lookup($ean));
            $p=HubReferenceEvidence::hanFallback($p,$ean);
            if(in_array($p['state'],['review','missing'],true)){
                $ai=['state'=>'not_checked'];
                if($catalog!==null&&$aiReads<3){
                    $aiReads++;
                    try{$ai=app(HubAiEvidence::class)->read($ean,$catalog);}
                    catch(\Throwable){$ai=['state'=>'unavailable'];}
                }
                if($catalog!==null&&$allowOnePaidReview&&!$paidAttempted&&$ai['state']==='missing'){
                    $paidAttempted=true;
                    try{$ai=app(HubAiEvidence::class)->analyze($ean,$catalog,$p['draft'],app(LegacyApprovedActionBridge::class));}
                    catch(\Throwable){$ai=['state'=>'paid_not_confirmed'];}
                }
                $p['ai']=$ai;
                $p=HubAiContent::merge($p,$ai,$row['draft']??[]);
                if(in_array($ai['state'],['identity_conflict','review','stale','paid_not_confirmed'],true)){
                    $p['state']='waiting';$p['fields']=[];$p['sources']=[];
                    $p['reason']='KI-Archiv erfordert Prüfung; keine automatische Übernahme';
                }
            }
            if(in_array($p['state'],['review','missing'],true)){
                try {
                    $template=(new HubArticleMirror())->standardProposals($db,$p['draft'],config('hw24_jtl_reference_observations',[]),HubAiEvidence::groupHint($p['draft'],$p['ai']??[]));
                    $p=self::mergeTemplate($p,$template);
                    // Only normalize the explicitly known catalog alias with a matching JTL template.
                    $hint=$template['manufacturer_hint']??null;
                    $name=(string)($p['draft']['Hersteller']??'');
                    if(is_string($hint)&&$hint!==$name&&$name===($catalog['item']['manufacturer']??null)&&
                        HubManufacturerPolicy::equivalent($name,$hint)){
                        $p['draft']['Hersteller']=$hint;$p['fields']['Hersteller']=$hint;
                        $p['sources']['Hersteller']='Bestätigte Herstellerzuordnung: '.$name.' → '.$hint.'; passende JTL-Vorlage';
                    }
                }catch(\Throwable){$p['template_reason']='Vorlagenquelle derzeit nicht bestätigt';}
            }
            if(in_array($p['state'],['review','missing'],true)){
                $price=HubResearchEvidence::marketPrice($job,(string)($p['draft']['ASIN']??''));
                if($price!==null)$p['market_price']=$price;
            }
            $p['coverage']=self::coverage($p,$row['draft']??[]);
            $out[$ean]=$p+['revision'=>$row['revision'],'item_id'=>$row['id']];
        }
        return $out;
    }

    /** Every supported export field, including optional and deliberately unfilled fields. */
    public static function coverage(array $p,array $original=[]):array
    {
        $draft=$p['draft']??$original;
        $safe=in_array($p['state']??null,['review','missing'],true);
        $fields=[];
        foreach(ScanResearch::FIELDS as $field){
            $old=trim((string)($original[$field]??''));
            $value=trim((string)($draft[$field]??''));
            if($field==='Artikelnummer'){$state='reserved';$source='JTL-Nummernkreis bei Neuanlage';}
            elseif($old!==''){$state='existing';$source='Vorhandener Entwurf';}
            elseif($safe&&$value!==''&&isset($p['fields'][$field])){$state='proposed';$source=$p['sources'][$field]??'Quelle prüfen';}
            else{
                $state='missing';
                $source=match($field){
                    'Netto-VK','Brutto-VK','Kosten','Netto-EK in Standardwährung'=>'Eigene Kalkulation / belegter Einkauf, kein fremder Marktpreis',
                    'Lieferant','Artikelnummer (Lieferant)'=>'Tatsächliche Lieferantenbeziehung erforderlich',
                    'Versandgewicht'=>'Separat belegtes Versandgewicht erforderlich',
                    default=>$safe?'Keine eindeutige freigegebene Feldquelle':'Produktidentität oder Recherche noch offen',
                };
            }
            $fields[$field]=['state'=>$state,'source'=>$source,'required'=>in_array($field,ScanResearch::REQUIRED,true)];
        }
        return $fields;
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
        if(isset($template['manufacturer_hint']))$p['manufacturer_hint']=$template['manufacturer_hint'];
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
            !HubManufacturerPolicy::equivalent((string)$draft['Hersteller'],$p['manufacturer']))
            return $out+['reason'=>'Herstellerkonflikt im Entwurf'];
        $add('GTIN',$ean,'Scan');
        foreach(HubCatalogFacts::productFields($catalog) as $f=>$fact)$add($f,$fact['value'],$source.$fact['suffix']);
        $attributes=$catalog['attributes'];
        $volume=HubCatalogFacts::volume($p['title'],$attributes,$draft);
        if($volume['state']==='conflict'){
            $out['fields']=[];$out['sources']=[];$out['state']='waiting';
            return $out+['reason'=>'Widersprüchliche Füllmenge oder Maßeinheit','draft'=>$row['draft']??[]];
        }
        foreach($volume['fields'] as $f=>$v)$add($f,$v,$source.' '.$volume['source']);
        $weight=HubCatalogFacts::itemWeight($attributes);
        if($weight['state']==='conflict')$out['weight_review']='Widersprüchliche Artikelgewichte im Katalog, keine Übernahme.';
        foreach(HubCategoryPolicy::suggest((string)($draft['Artikelname']??''),(string)($draft['Warengruppe']??''))??[] as $f=>$v)
            $add($f,$v,'HW24-Kategorieregel');
        foreach(HubTemplateDefaults::emptyTexts($draft) as $f=>$v)$add($f,$v,'HW24-Regel: eigener Artikelname');
        foreach(HubSeoDefaults::propose($draft) as $f=>$v)$add($f,$v,'HW24-SEO-Basisregel (kein KI-Aufruf)');
        $out['missing']=array_values(array_filter(ScanResearch::REQUIRED,fn($f)=>trim((string)($draft[$f]??''))===''));
        $out['state']=$out['missing']===[]?'review':'missing';
        return $out+['reason'=>'Vorschlag, keine Freigabe','draft'=>$draft];
    }
}
