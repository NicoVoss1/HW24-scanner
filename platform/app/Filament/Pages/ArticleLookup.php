<?php
declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\LegacyReadBridge;
use App\Services\ScanResearchPresentation;
use App\Services\HubPeerMirror;
use App\Services\HubArticleMirror;
use App\Services\HubResearchEvidence;
use App\Services\HubAiEvidence;
use App\Services\HubSeoDefaults;
use App\Services\HubCategoryPolicy;
use App\Services\ResearchQueueRuntime;
use App\Services\ResearchQueue;
use App\Domain\Jtl\SourceSnapshot;
use App\Domain\Jtl\ArticleCard;
use App\Domain\Jtl\RelatedSourceEvidence;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;

final class ArticleLookup extends Page
{
    protected static ?string $title = 'Artikel & Schnittstellen';
    protected static ?string $navigationLabel = 'Artikelpflege (Bestand)';
    protected static ?string $slug = 'artikel';
    protected string $view = 'filament.pages.article-lookup';

    public string $identifier = '';
    #[Locked] public array $nameMatches=[];
    public string $nameSearchMessage='';
    #[Locked] public array $articleStock=[];
    #[Locked] public array $articleOffers=[];
    #[Locked] public array $manualAiResult=[];
    public bool $confirmArticleAi=false;
    #[Locked] public array $articleCatalog=[];
    #[Locked] public array $updateOptions=[];
    #[Locked] public array $updateIdentity=[];
    #[Locked] public array $updateReview=[];
    public array $updateSelection=[];
    public array $manualFields=[];
    #[Locked] public int $amazonRetryAt=0;
    #[Locked] public array $catalogEvidence=[];
    public bool $confirmUpdateExport=false;
    public string $updateMessage='';
    #[Locked] public array $fieldReview = [];
    #[Locked] public array $referenceReview = [];
    #[Locked] public string $referenceEan = '';
    #[Locked] public array $seoSuggestions = [];
    #[Locked] public array $peerReview = [];
    #[Locked] public array $queueReview = [];
    #[Locked] public array $sharedResearchReview = [];
    #[Locked] public array $categoryProposal = [];
    #[Locked] public string $pflegeMessage = '';

    #[Locked]
    public array $lookup = [];

    #[Locked]
    public array $amazon = [];

    #[Locked]
    public array $ai = [];

    #[Locked]
    public array $existingEvidence = [];

    #[Locked]
    public array $articleRelated = [];

    #[Locked]
    public array $sourceState = [];

    #[Locked]
    public array $sourceRows = [];

    #[Locked]
    public int $sourceNextOffset = 0;

    public string $sourceQuery = '';

    public function searchNameOrAsin(HubArticleMirror $mirror):void
    {
        abort_unless(QueryAuditWorkbench::canAccess(),403);
        $this->reset(['lookup','fieldReview','referenceReview','referenceEan','articleStock','articleOffers','articleCatalog','catalogEvidence','amazonRetryAt','manualAiResult','confirmArticleAi','updateOptions','updateIdentity','updateReview','updateSelection','manualFields','confirmUpdateExport','updateMessage','amazon','ai','existingEvidence','articleRelated','sourceState','sourceRows','sourceNextOffset','sourceQuery','seoSuggestions','peerReview','queueReview','sharedResearchReview','categoryProposal','pflegeMessage']);
        $this->nameMatches=[];$this->nameSearchMessage='';$this->resetErrorBag('identifier');
        try{
            $r=$mirror->searchCandidates($this->identifier);
            $this->nameMatches=$r['rows'];$this->nameSearchMessage=count($this->nameMatches).' Treffer'.($r['limited']?' · bitte Suche eingrenzen':'');
        }catch(\Throwable){$this->nameSearchMessage='Namens-/ASIN-Suche nicht bestätigt. Spiegel prüfen.';}
    }
    public function loadNameMatch(int $index,LegacyReadBridge $bridge,HubArticleMirror $mirror):void
    {
        abort_unless(QueryAuditWorkbench::canAccess(),403);
        $number=$this->nameMatches[$index]['artikelnummer']??null;
        if(!is_string($number)||$number==='')return;
        $this->identifier=$number;$this->nameMatches=[];$this->nameSearchMessage='';
        $this->findArticle($bridge,$mirror);
    }
    public function findArticle(LegacyReadBridge $bridge,HubArticleMirror $mirror): void
    {
        $this->reset(['nameMatches','nameSearchMessage','lookup', 'amazon', 'ai','articleStock','articleOffers','manualAiResult','confirmArticleAi','articleCatalog','catalogEvidence','amazonRetryAt', 'sourceState', 'sourceRows', 'sourceNextOffset', 'sourceQuery', 'existingEvidence', 'articleRelated','fieldReview','referenceReview','referenceEan','seoSuggestions','peerReview','queueReview','sharedResearchReview','categoryProposal','pflegeMessage','updateOptions','updateIdentity','updateReview','updateSelection','manualFields','confirmUpdateExport','updateMessage']);
        $id = trim($this->identifier);
        if (! preg_match('/^[\pL\pN][\pL\pN._\/-]{1,63}$/uD', $id)) {
            $this->addError('identifier', 'Bitte EAN, HAN oder JTL-Artikelnummer eingeben.');
            return;
        }
        $this->resetErrorBag('identifier');

        $result = $mirror->enrich($bridge->fetch('jtl.article', ['q' => $id]));
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];
        $matches = [];
        foreach (array_slice(is_array($raw['matches'] ?? null) ? $raw['matches'] : [], 0, 15) as $row) {
            if (! is_array($row)) continue;
            $matches[] = ArticleCard::fromMatch($row);
        }
        $this->lookup = [
            'ok' => (bool) ($result['ok'] ?? false),
            'state' => (string) ($result['state'] ?? 'unavailable'),
            'http_status' => $result['http_status'] ?? null,
            'jtl_status' => (string) ($raw['jtl_status'] ?? 'unbekannt'),
            'matches' => $matches,
            'ean' => isset($matches[0]) ? $matches[0]['ean'] : null,
        ];
        if (count($matches)===1 && ($this->lookup['jtl_status']??'')==='current_snapshot' && is_array($raw['matches'][0]??null)) {
            $this->articleRelated=RelatedSourceEvidence::present($raw['matches'][0]);
            try{$stockRead=app(\App\Services\HubStockReader::class)->readArticle($matches[0]['jtl_article_id'],(string)($raw['snapshot_at']??''));}catch(\Throwable){$stockRead=['ok'=>false];}
            $this->articleStock=\App\Services\HubStockReader::workspace($stockRead,$this->articleRelated);
        }
        if (($this->lookup['jtl_status']??'')==='current_snapshot' &&
            count($matches)===1 &&
            ($this->verifiedEan()!==null||($matches[0]['ean']??'')==='')) {
            // Only cheap, read-only archival metadata after an explicit user search.
            // No amazon.catalog / amazon.pricing / OpenAI POST is called here.
            $ean=(string)$matches[0]['ean'];
            if($ean!=='')$this->readExistingEvidence($bridge,$ean);
            $this->fieldReview=ScanResearchPresentation::jtlFields($ean,$result,config('hw24_jtl_reference_observations',[]));
            $this->seoSuggestions=HubSeoDefaults::propose(['Artikelname'=>$raw['matches'][0]['artikelname']??'',
                'Titel-Tag (SEO)'=>$raw['matches'][0]['seo_titel']??'',
                'Meta-Description (SEO)'=>$raw['matches'][0]['seo_beschreibung']??'']);
            $this->categoryProposal=HubCategoryPolicy::suggest((string)($raw['matches'][0]['artikelname']??''),(string)($raw['matches'][0]['warengruppe_name']??''))??[];
            if($ean!==''&&config('hw24_research_queue.enabled')===true){
                try{$this->queueReview=ResearchQueueRuntime::queue()->statuses([$ean])[$ean]??[];
                    $this->readSharedResearch($ean);
                }catch(\Throwable){}
            }
        }
        if(QueryAuditWorkbench::canAccess()){
            $referenceEan=count($matches)===1?(string)($matches[0]['ean']??''):$id;
            try{
                ResearchQueue::canonical($referenceEan);
                $this->referenceEan=$referenceEan;
                $this->referenceReview=\App\Services\HubReferenceEvidence::comparison(
                    $this->fieldReview['fields']??[],\App\Services\HubReferenceEvidence::lookup($referenceEan));
            }catch(\Throwable){}
        }
    }

    /** Read current source and existing archives, never start a paid analysis. */
    private function existingUpdateContext(LegacyReadBridge $bridge,HubArticleMirror $mirror,array $identity):array
    {
        $number=(string)($identity['artikelnummer']??'');
        if($number==='')throw new \RuntimeException('Article number missing');
        $response=$mirror->enrich($bridge->fetch('jtl.article',['q'=>$number]));
        $raw=$response['data']??[];
        if(($response['ok']??false)!==true||($raw['jtl_status']??null)!=='current_snapshot'||
            ($raw['limited']??false)===true||count($raw['matches']??[])!==1)throw new \RuntimeException('Unique current article required');
        $article=ArticleCard::fromMatch($raw['matches'][0]);
        if((string)($raw['matches'][0]['artikelnummer']??'')!==$number)throw new \RuntimeException('Exact article number required');
        foreach(['jtl_article_id','artikelnummer','ean'] as $f)
            if(($f!=='ean'&&($article[$f]??'')==='')||$article[$f]!==($identity[$f]??null))throw new \RuntimeException('Identity changed');
        $ean=$article['ean'];if($ean!=='')ResearchQueue::canonical($ean);
        $review=ScanResearchPresentation::jtlFields($ean,$response,config('hw24_jtl_reference_observations',[]));
        if(($review['ok']??false)!==true)throw new \RuntimeException('Field source unavailable');
        $catalog=[];$ai=[];
        if($ean!==''&&config('hw24_research_queue.enabled')===true){
            try{
                $job=ResearchQueueRuntime::queue()->statuses([$ean])[$ean]??[];
                $archive=HubResearchEvidence::archivedCatalog(HubPeerMirror::connect(),$job);
                $catalog=HubResearchEvidence::catalogItem($ean,$job,$archive)??[];
                if($catalog)$ai=app(HubAiEvidence::class)->read($ean,$catalog);
            }catch(\Throwable){}
        }
        if(!$catalog){
            try{
                $search=$ean!==''?$ean:$number;
                $direct=HubResearchEvidence::latestCatalog(HubPeerMirror::connect(),$search);
                if($direct!==null&&($ean!==''||(!empty($article['asin'])&&$article['asin']===$direct['item']['asin']))){
                    $catalog=$direct;
                    if($ean==='')$catalog['verified_missing_gtin']=$search;
                    $ai=app(HubAiEvidence::class)->read($search,$catalog);
                }
            }catch(\Throwable){}
        }
        if($this->articleCatalog){
            try{
                $searchEan=$ean!==''?$ean:$number;
                ResearchQueue::canonical($searchEan);
                $direct=HubResearchEvidence::directCatalog($searchEan,$this->articleCatalog,HubPeerMirror::connect());
                if($direct!==null&&($ean!==''||(!empty($article['asin'])&&$article['asin']===$direct['item']['asin']))){
                    $catalog=$direct;
                    if($ean==='')$catalog['verified_missing_gtin']=$searchEan;
                    $ai=app(HubAiEvidence::class)->read($searchEan,$catalog);
                }
            }catch(\Throwable){}
        }
        $seo=HubSeoDefaults::propose(['Artikelname'=>$raw['matches'][0]['artikelname']??'',
            'Titel-Tag (SEO)'=>$raw['matches'][0]['seo_titel']??'','Meta-Description (SEO)'=>$raw['matches'][0]['seo_beschreibung']??'']);
        $manufacturers=[];
        try{
            $manufacturers=\App\Services\HubManufacturerPolicy::currentReferences(HubPeerMirror::connect(),(string)($raw['snapshot_at']??''));
        }catch(\Throwable){}
        $refs=($ean!==''||isset($catalog['verified_missing_gtin']))?\App\Services\HubReferenceEvidence::lookup($ean!==''?$ean:$catalog['verified_missing_gtin']):[];
        $same=\App\Services\HubReferenceEvidence::existingArticleRows($article['jtl_article_id'],$number);
        if($same)$refs['ameise']=['state'=>'found','rows'=>$same,'archive'=>'Originalexport'];
        $current=$review['fields'];$observations=config('hw24_jtl_reference_observations',[]);
        if(isset($review['gross_calculation']['value'])&&($current['Brutto-VK']['state']??null)!=='value')
            $current['Brutto-VK']=['state'=>'calculated','value'=>$review['gross_calculation']['value'],'source'=>$review['gross_calculation']['source']];
        $options=\App\Services\HubExistingArticleExport::candidates($current,$article,$catalog,$ai,$refs,$seo,$manufacturers);
        $draft=[];
        foreach($current as $f=>$v)if(($v['state']??null)==='value')$draft[$f]=$v['value'];
        $unitId=(int)($raw['matches'][0]['jtl_source_fields']['kMassEinheit']??0);
        if(empty($draft['Maßeinheit']))$draft['Maßeinheit']=$observations['mass_unit_examples'][$unitId]['display_code']??'';
        $template=[];
        try{$template=$mirror->standardProposals(HubPeerMirror::connect(),$draft,$observations);}catch(\Throwable){}
        $extra=\App\Services\HubArticleFieldEditor::templateOptions($current,$template);
        $lists=\App\Services\HubReferenceEvidence::referenceLists();
        $choices=\App\Services\HubArticleFieldEditor::referenceOptions($current,$same,$observations,$manufacturers,$lists);
        foreach([$extra,$choices] as $set)foreach($set as $f=>$values)$options[$f]=($options[$f]??[])+$values;
        return ['options'=>$options,'current'=>$current,'catalog'=>$catalog?['id'=>$catalog['id'],'at'=>$catalog['at'],'item'=>$catalog['item'],'facts'=>\App\Services\HubCatalogFacts::productFields($catalog)]:[]];
    }

    public function prepareExistingUpdate(LegacyReadBridge $bridge,HubArticleMirror $mirror):void
    {
        abort_unless(QueryAuditWorkbench::canAccess(),403);
        $previous=$this->updateIdentity;$oldOptions=$this->updateOptions;$oldSelection=$this->updateSelection;$oldManual=$this->manualFields;$oldReview=$this->updateReview;
        $this->reset(['updateOptions','updateIdentity','updateReview','updateSelection','manualFields','confirmUpdateExport','updateMessage']);
        if(($this->fieldReview['ok']??false)!==true||count($this->lookup['matches']??[])!==1){$this->updateMessage='Bitte zuerst einen eindeutigen Bestandsartikel laden.';return;}
        try{
            $article=$this->lookup['matches'][0];
            $identity=array_intersect_key($article,array_flip(['jtl_article_id','artikelnummer','ean']));
            $context=$this->existingUpdateContext($bridge,$mirror,$identity);
            $this->updateOptions=$context['options'];$this->updateReview=$context['current'];
            $this->updateIdentity=$identity;$this->catalogEvidence=$context['catalog'];
            if($this->catalogEvidence)$this->existingEvidence['amazon']=['verified'=>true,'found'=>true,'snapshot_id'=>$this->catalogEvidence['id'],'at'=>$this->catalogEvidence['at'],'count'=>1];
            if($previous===$identity){
                foreach($oldSelection as $f=>$token)if(is_string($token)&&isset($oldOptions[$f][$token],$this->updateOptions[$f][$token])&&$oldOptions[$f][$token]===$this->updateOptions[$f][$token])$this->updateSelection[$f]=$token;
                foreach($oldManual as $f=>$value)if(($oldReview[$f]??null)===($this->updateReview[$f]??null))$this->manualFields[$f]=$value;
            }
            $this->updateMessage=$this->updateOptions?'Auswahl bereit. Ergänzungen und explizit zu prüfende Textkorrekturen mit Quelle.':'Keine exportierbaren Ergänzungen bestätigt. Recherche oder Feldzuordnung prüfen.';
        }catch(\Throwable){$this->updateMessage='Aktueller Artikel oder Quellen nicht bestätigt. Kein Export.';}
    }

    public function selectUniqueUpdates():void
    {
        abort_unless(QueryAuditWorkbench::canAccess(),403);
        $suggestions=\App\Services\HubFieldDecisions::unique($this->updateOptions);
        foreach($suggestions as $f=>$token)if(trim((string)($this->manualFields[$f]??''))===''&&!isset($this->updateSelection[$f]))$this->updateSelection[$f]=$token;
        $this->confirmUpdateExport=false;
    }

    public function clearExistingSelections():void
    {
        abort_unless(QueryAuditWorkbench::canAccess(),403);
        $this->updateSelection=[];$this->manualFields=[];$this->confirmUpdateExport=false;
    }

    public function updatedUpdateSelection():void{$this->confirmUpdateExport=false;}
    public function updatedManualFields():void{$this->confirmUpdateExport=false;}

    /** One action: reuse sources, queue only missing catalog, show archived offers in place. */
    public function researchAll(LegacyReadBridge $bridge,HubArticleMirror $mirror):void
    {
        abort_unless(QueryAuditWorkbench::canAccess(),403);
        if(count($this->lookup['matches']??[])!==1)return;
        $this->prepareExistingUpdate($bridge,$mirror);
        if(!$this->updateIdentity)return;
        $directAttempt=false;
        if(!$this->catalogEvidence){
            if($this->verifiedEan()!==null&&config('hw24_research_queue.enabled')===true)$this->queueExistingArticle();
            elseif($this->researchEan()!==null){$directAttempt=true;$this->readAmazon($bridge);$this->prepareExistingUpdate($bridge,$mirror);}
        }
        $asin=$this->lookup['matches'][0]['asin']??null;
        if(is_string($asin)&&preg_match('/^[A-Z0-9]{10}$/D',$asin)){
            try{
                $old=\App\Services\HubArticleResearchWorkspace::archivedOffers($asin,$bridge);
                if($old)$this->articleOffers=$old;
                elseif(!$directAttempt&&($this->catalogEvidence['item']['asin']??null)===$asin&&$this->amazonRetryAt<=time())$this->articleOffers=\App\Services\HubArticleResearchWorkspace::offers($asin,$bridge);
            }catch(\Throwable){}
        }
        $this->dispatch('hw24-focus-article-decisions');
    }

    /** Poll only a pending shared job. No repeated live catalog requests. */
    public function pollArticleResearch(LegacyReadBridge $bridge,HubArticleMirror $mirror):void
    {
        if(!QueryAuditWorkbench::canAccess()||!in_array($this->queueReview['status']??null,['queued','leased','retry'],true))return;
        $ean=$this->verifiedEan();if($ean===null)return;
        try{
            $this->queueReview=ResearchQueueRuntime::queue()->statuses([$ean])[$ean]??[];
            if(($this->queueReview['status']??null)==='completed'){
                $this->prepareExistingUpdate($bridge,$mirror);
                $asin=$this->lookup['matches'][0]['asin']??($this->catalogEvidence['item']['asin']??null);
                if(is_string($asin)&&preg_match('/^[A-Z0-9]{10}$/D',$asin)){
                    $old=\App\Services\HubArticleResearchWorkspace::archivedOffers($asin,$bridge);if($old)$this->articleOffers=$old;
                }
            }
        }catch(\Throwable){$this->pflegeMessage='Recherchefortschritt nicht bestätigt.';}
    }

    public function mount():void
    {
        $q=request()->query('q');
        if(is_string($q)&&preg_match('/^[\pL\pN][\pL\pN._\/-]{1,63}$/uD',$q))$this->identifier=$q;
    }

    public function downloadExistingUpdate(LegacyReadBridge $bridge,HubArticleMirror $mirror)
    {
        abort_unless(QueryAuditWorkbench::canAccess(),403);
        if(!$this->confirmUpdateExport||!$this->updateIdentity)return;
        try{
            $context=$this->existingUpdateContext($bridge,$mirror,$this->updateIdentity);
            $fresh=$context['options'];
            foreach($this->updateSelection as $f=>$token){
                if($token==='')continue;
                if(!is_string($token)||!isset($this->updateOptions[$f][$token],$fresh[$f][$token])||
                    $fresh[$f][$token]!==$this->updateOptions[$f][$token])throw new \RuntimeException('Source or field changed');
            }
            $selection=$this->updateSelection;
            try{$manual=\App\Services\HubArticleFieldEditor::manualOptions($context['current'],$this->manualFields);}
            catch(\RuntimeException $e){$this->confirmUpdateExport=false;$this->updateMessage='Manuelle Eingaben prüfen: '.$e->getMessage();return;}
            foreach($manual as $f=>$opts){
                if(($this->updateReview[$f]??null)!==($context['current'][$f]??null))throw new \RuntimeException('Manueller Altwert verändert');
                if(!empty($selection[$f])){$this->confirmUpdateExport=false;$this->updateMessage='Bitte pro Feld entweder Recherchewert oder manuelle Eingabe auswählen: '.$f;return;}
                $fresh[$f]=($fresh[$f]??[])+$opts;$selection[$f]=(string)array_key_first($opts);
            }
            $patch=\App\Services\HubFieldDecisions::resolve($fresh,$selection);
            $missing=\App\Services\HubFieldPolicy::missingAfterUpdate($context['current'],$patch['fields']);
            if($missing){$this->confirmUpdateExport=false;$this->updateMessage='Pflichtfelder offen: '.implode(', ',$missing).'. Kein vollständiger Aktualisierungsexport.';return;}
            $csv=\App\Services\HubExistingArticleExport::csv($this->updateIdentity['artikelnummer'],$selection,$fresh);
        }catch(\Throwable){
            $this->confirmUpdateExport=false;
            $this->updateMessage='Export gestoppt: Auswahl leer, Quelle oder JTL-Feld verändert. Vorschläge erneut laden.';
            return;
        }
        $this->confirmUpdateExport=false;
        $this->updateMessage='Prüf-CSV erstellt. In Ameise ausschließlich vorhandene Artikel aktualisieren; keine Neuanlage.';
        return response()->streamDownload(static function()use($csv):void{echo $csv;},
            'HW24_Ameise_Bestandsartikel_AKTUALISIERUNG.csv',
            ['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'private, no-store']);
    }

    private function readSharedResearch(string $ean): void
    {
        $this->sharedResearchReview=[];
        if(($this->queueReview['status']??null)!=='completed')return;
        try{
            $archive=HubResearchEvidence::archivedCatalog(HubPeerMirror::connect(),$this->queueReview);
            $catalog=HubResearchEvidence::catalogItem($ean,$this->queueReview,$archive);
            if($catalog===null){$this->sharedResearchReview=['state'=>'unavailable'];return;}
            $this->sharedResearchReview=HubResearchEvidence::existingComparison($this->fieldReview,$catalog);
            if($this->sharedResearchReview['state']==='review'){
                try{$this->sharedResearchReview['ai']=app(HubAiEvidence::class)->read($ean,$catalog);}
                catch(\Throwable){$this->sharedResearchReview['ai']=['state'=>'unavailable'];}
                $price=HubResearchEvidence::marketPrice($this->queueReview,$catalog['item']['asin']);
                if($price!==null)$this->sharedResearchReview['market_price']=$price;
            }
        }catch(\Throwable){$this->sharedResearchReview=['state'=>'unavailable'];}
    }

    public function compareExistingArticle(HubPeerMirror $reader): void
    {
        $ean=$this->verifiedEan();
        if($ean===null||($this->fieldReview['ok']??false)!==true)return;
        try{$this->peerReview=$reader->read($ean);}
        catch(\Throwable){$this->peerReview=['ok'=>false,'message'=>'Kein bestätigter Vergleich möglich.'];}
    }

    public function queueExistingArticle(): void
    {
        $ean=$this->verifiedEan();
        if($ean===null||($this->fieldReview['ok']??false)!==true||auth()->id()===null||config('hw24_research_queue.enabled')!==true)return;
        try{
            ResearchQueue::canonical($ean);
            $q=ResearchQueueRuntime::queue();
            $r=$q->enqueue($ean,'filament:'.auth()->id());
            $this->queueReview=$q->statuses([$ean])[$ean]??[];
            $this->readSharedResearch($ean);
            $this->pflegeMessage=$r['created']?'Artikel zur Recherche angemeldet.':'Vorhandener Auftrag wiederverwendet. Kein neuer Provider-Aufruf.';
        }catch(\Throwable){$this->pflegeMessage='Rechercheanmeldung nicht bestätigt.';}
    }

    private function readExistingEvidence(LegacyReadBridge $bridge,string $ean): void
    {
        $ai=$bridge->fetch('ai.summary',['summary'=>1,'eans'=>$ean]);
        $data=is_array($ai['data']??null)?$ai['data']:[];
        $summary=is_array($data['summaries']??null)?($data['summaries'][$ean]??null):null;
        $this->existingEvidence['ai']=[
            'verified'=>($ai['ok']??false)===true && ($data['summary']??false)===true,
            'found'=>($ai['ok']??false)===true && is_array($summary),
            'id'=>is_array($summary)&&is_numeric($summary['review_id']??null)
                ?(int)$summary['review_id']:null,
            'count'=>is_array($summary)&&is_numeric($summary['count']??null)
                ?(int)$summary['count']:null,
            'at'=>is_array($summary)?mb_substr((string)($summary['at_utc']??''),0,40):null,
            'model'=>is_array($summary)?mb_substr((string)($summary['model']??''),0,60):null,
        ];
        $amz=$bridge->fetch('amazon.archive',['ean'=>$ean,'limit'=>1]);
        $raw=is_array($amz['data']??null)?$amz['data']:[];
        $snap=is_array($raw['snapshots']??null)&&is_array($raw['snapshots'][0]??null)
            ?$raw['snapshots'][0]:null;
        $this->existingEvidence['amazon']=[
            'verified'=>($amz['ok']??false)===true &&
                ($raw['scope']??'')==='catalog_ean_not_seller_account',
            'found'=>($amz['ok']??false)===true && is_array($snap),
            'snapshot_id'=>is_array($snap)&&is_numeric($snap['id']??null)
                ?(int)$snap['id']:null,
            'count'=>is_array($snap)&&is_numeric($snap['count']??null)
                ?(int)$snap['count']:null,
            'at'=>is_array($snap)?mb_substr((string)($snap['fetched_at_utc']??''),0,40):null,
        ];
    }

    /** Manually load one page of original JTL source fields, never a bulk DB export. */
    public function readJtlSources(LegacyReadBridge $bridge): void
    {
        $this->reset(['sourceState','sourceRows','sourceNextOffset','sourceQuery']);
        $this->fetchJtlSourcePage($bridge);
    }

    public function loadMoreJtlSources(LegacyReadBridge $bridge): void
    {
        if (($this->sourceState['ok']??false)!==true ||
            ($this->sourceState['has_more']??false)!==true) return;
        $this->fetchJtlSourcePage($bridge);
    }

    private function fetchJtlSourcePage(LegacyReadBridge $bridge): void
    {
        // Multiple matches must be resolved by article number before using
        // any article-specific source rows.
        $matches=$this->lookup['matches']??[];
        if (($this->lookup['jtl_status']??null)!=='current_snapshot' ||
            !is_array($matches)||count($matches)!==1) {
            $this->sourceState=['ok'=>false,'reason'=>'Eindeutigen, aktuellen JTL-Artikel wählen.'];
            return;
        }
        $article=$matches[0];
        $id=(string)($article['jtl_article_id']??'');
        $ean=(string)($article['ean']??'');
        if (!preg_match('/^[0-9]{1,12}$/D',$id) ||
            !preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean)) {
            $this->sourceState=['ok'=>false,'reason'=>'JTL-Artikel-ID oder EAN ungültig.'];
            return;
        }
        $result=$bridge->fetch('jtl.fields',[
            'article_id'=>$id,'ean'=>$ean,'offset'=>$this->sourceNextOffset,'limit'=>80
        ]);
        $raw=is_array($result['data']??null)?$result['data']:[];
        $page=SourceSnapshot::present($raw);
        if(($result['ok']??false)!==true || ($page['ok']??false)!==true ||
            ($page['article_id']??null)!==$id || ($page['ean']??null)!==$ean) {
            $this->sourceState=['ok'=>false,'reason'=>'JTL-Feldquelle nicht verfügbar oder noch nicht bereitgestellt.'];
            return;
        }
        if ($this->sourceNextOffset>0 &&
            ($this->sourceState['snapshot_at']??null)!==$page['snapshot_at']) {
            $this->reset(['sourceRows','sourceNextOffset']);
            $this->sourceState=['ok'=>false,'reason'=>'Neuer JTL-Snapshot erkannt. Quellfelder bitte erneut laden.'];
            return;
        }
        $this->sourceRows=array_merge($this->sourceRows,$page['fields']);
        // Advance by the original API page count, including filtered sensitive
        // fields, or pagination could repeat/skip rows.
        $this->sourceNextOffset+=(int)$page['returned_count'];
        $this->sourceState=[
            'ok'=>true,'snapshot_at'=>$page['snapshot_at'],
            'field_count'=>$page['field_count'],
            'has_more'=>$page['has_more'],
            'prices'=>$page['prices'],
        ];
    }

    public function refreshArticleResearch():void
    {
        $ean=$this->verifiedEan();if($ean===null)return;
        try{
            $this->queueReview=ResearchQueueRuntime::queue()->statuses([$ean])[$ean]??[];
            $this->readSharedResearch($ean);
            if(QueryAuditWorkbench::canAccess())$this->referenceReview=\App\Services\HubReferenceEvidence::comparison($this->fieldReview['fields']??[],\App\Services\HubReferenceEvidence::lookup($ean));
            $this->pflegeMessage='Gespeicherte Quellen aktualisiert. Recherchewerte erneut aufbereiten.';
        }catch(\Throwable){$this->pflegeMessage='Recherchearchive nicht vollständig verfügbar.';}
    }
    public function showArticleOffers(LegacyReadBridge $bridge):void
    {
        abort_unless(QueryAuditWorkbench::canAccess(),403);
        if(count($this->lookup['matches']??[])!==1)return;
        $asin=$this->lookup['matches'][0]['asin']??($this->catalogEvidence['item']['asin']??null);
        if(!is_string($asin)||!preg_match('/^[A-Z0-9]{10}$/D',$asin)){$this->articleOffers=['ok'=>false,'message'=>'Keine bestätigte ASIN. Zuerst Artikel recherchieren.'];$this->dispatch('hw24-article-offers-open');return;}
        try{$this->articleOffers=\App\Services\HubArticleResearchWorkspace::offers($asin,$bridge);}
        catch(\Throwable){$this->articleOffers=['ok'=>false,'message'=>'Amazon-Angebote nicht bestätigt.'];}
        $this->dispatch('hw24-article-offers-open');
    }
    public function runArticleAi(LegacyReadBridge $read,\App\Services\LegacyApprovedActionBridge $write,bool $web=false):void
    {
        abort_unless(QueryAuditWorkbench::canAccess(),403);
        $confirmed=$this->confirmArticleAi;$this->confirmArticleAi=false;$ean=$this->verifiedEan();
        if(!$confirmed||$ean===null||count($this->lookup['matches']??[])!==1){$this->manualAiResult=['ok'=>false,'message'=>'Eindeutiger Artikel und Kostenfreigabe erforderlich.'];return;}
        try{
            $catalog=[];
            if(!$web){
                try{
                    $db=HubPeerMirror::connect();
                    $catalog=HubResearchEvidence::directCatalog($ean,$this->articleCatalog,$db)??[];
                    if(!$catalog&&config('hw24_research_queue.enabled')===true){
                        $job=ResearchQueueRuntime::queue()->statuses([$ean])[$ean]??[];
                        $catalog=HubResearchEvidence::catalogItem($ean,$job,HubResearchEvidence::archivedCatalog($db,$job))??[];
                    }
                }catch(\Throwable){}
            }
            $this->manualAiResult=\App\Services\HubArticleResearchWorkspace::aiOnce($ean,$this->lookup['matches'][0],$read,$write,$web,$catalog);
        }
        catch(\Throwable){$this->manualAiResult=['ok'=>false,'message'=>'KI-Auftrag nicht bestätigt; nicht ungeprüft wiederholen.'];}
        $this->readAiArchive($read);
    }
    public function readAmazonAndPrepare(LegacyReadBridge $bridge,HubArticleMirror $mirror):void
    {
        abort_unless(QueryAuditWorkbench::canAccess(),403);
        $this->readAmazon($bridge);
        $this->prepareExistingUpdate($bridge,$mirror);
    }

    public function readAmazon(LegacyReadBridge $bridge): void
    {
        $ean = $this->researchEan();
        if ($ean === null) return;
        if($this->amazonRetryAt>time()){$this->pflegeMessage='Amazon wartet noch '.($this->amazonRetryAt-time()).' Sekunden. Gespeicherte Quellen bleiben verfügbar.';return;}

        // A live Amazon SP-API lookup is ONLY invoked when the operator clicks.
        $result = $bridge->fetch('amazon.catalog', ['ean' => $ean]);
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];
        if(($result['ok']??false)===true){
            $this->articleCatalog=\App\Services\ScanResearch::fromResponses($ean,[],$result);
            if(($raw['has_next_page']??null)!==false||count($raw['items']??[])!==1)$this->articleCatalog['more']=true;
        }
        if(($result['http_status']??null)===429)$this->amazonRetryAt=time()+max(15,(int)($result['retry_after_seconds']??60));
        $this->amazon = [
            'ok' => (bool) ($result['ok'] ?? false),
            'state' => (string) ($result['state'] ?? 'unavailable'),
            'http_status' => $result['http_status'] ?? null,
            'count' => $raw['count'] ?? null,
            'source' => $raw['source'] ?? null,
            'error' => (string) ($raw['error'] ?? ''),
            'stage' => (string) ($raw['stage'] ?? ''),
            'retry_after_seconds'=>max(0,$this->amazonRetryAt-time()),
        ];
    }

    public function readAiArchive(LegacyReadBridge $bridge): void
    {
        $ean = $this->verifiedEan();
        if ($ean === null) return;

        // GET existing archive only. No OpenAI billable POST and no new analysis.
        $result = $bridge->fetch('ai.history', ['ean' => $ean, 'limit' => 5]);
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];
        $this->ai = [
            'ok' => (bool) ($result['ok'] ?? false),
            'state' => (string) ($result['state'] ?? 'unavailable'),
            'http_status' => $result['http_status'] ?? null,
            'count' => is_array($raw['reviews'] ?? null) ? count($raw['reviews']) : null,
            'error' => (string) ($raw['error'] ?? ''),
        ];
    }

    public function researchEan():?string
    {
        if(($ean=$this->verifiedEan())!==null)return $ean;
        if(($this->lookup['jtl_status']??null)!=='current_snapshot'||count($this->lookup['matches']??[])!==1)return null;
        $a=$this->lookup['matches'][0];$hint=$a['artikelnummer']??'';
        if(($a['ean']??'')!==''||empty($a['asin']))return null;
        try{ResearchQueue::canonical($hint);return $hint;}catch(\Throwable){return null;}
    }
    public function verifiedEan(): ?string
    {
        if(($this->lookup['jtl_status']??null)!=='current_snapshot'||count($this->lookup['matches']??[])!==1)return null;
        $ean=$this->lookup['ean']??null;
        if(!is_string($ean))return null;
        try{ResearchQueue::canonical($ean);return $ean;}catch(\Throwable){return null;}
    }
}
