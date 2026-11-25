<?php
declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\LegacyReadBridge;
use App\Services\LegacyApprovedActionBridge;
use Illuminate\Support\Str;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use App\Services\PreparedScanCsv;
use App\Services\ScanEntryPresentation;
use App\Services\ScanResearch;
use App\Services\ScanResearchPresentation;
use App\Services\HubCategoryPolicy;
use App\Services\HubPeerMirror;
use App\Services\HubTemplateDefaults;
use App\Services\HubArticleMirror;
use App\Services\HubSeoDefaults;
use App\Services\ScanQueueIntake;

/**
 * First functional migration step: show the actual old scan batches read-only.
 * Scanner writes and JTL Ameise CSV remain in the existing, working LEGACY app.
 * The old API owns row authorization via PHP_AUTH_USER (same pinned Basic actor).
 */
final class ScanWorkbench extends Page
{
    use WithFileUploads;

    public $csvFile = null;
    public bool $confirmCsvImport = false;
    #[Locked] public array $csvRows = [];
    #[Locked] public array $csvState = [];
    #[Locked] public int $csvOffset = 0;
    #[Locked] public ?int $csvBatchId = null;

    public function updatedCsvFile(): void
    {
        $this->resetValidation('csvFile');
        $this->reset(['csvRows','csvState','csvOffset','csvBatchId','confirmCsvImport']);
    }

    public function clearCsvFile(): void
    {
        $this->reset(['csvFile','csvRows','csvState','csvOffset','csvBatchId','confirmCsvImport']);
        $this->resetValidation('csvFile');
    }

    public function previewCsv(): void
    {
        $this->resetValidation('csvFile');
        $this->reset(['csvRows','csvState','csvOffset','csvBatchId','confirmCsvImport']);
        if($this->selectedBatchId===null){
            $this->csvState=['ok'=>false,'message'=>'Bitte zuerst einen TEST-Stapel auswählen.'];
            return;
        }
        try {
            $this->validate(['csvFile'=>'required|file|max:1024'],[
                'csvFile.required'=>'Bitte zuerst eine CSV-Datei auswählen.',
                'csvFile.file'=>'Die ausgewählte Datei konnte nicht geladen werden.',
                'csvFile.max'=>'Die CSV-Datei darf maximal 1 MB groß sein.',
            ]);
            if(strtolower((string)$this->csvFile->getClientOriginalExtension())!=='csv')
                throw new \InvalidArgumentException('Bitte vorbereitete UTF-8-CSV auswählen. XLSX zuerst als CSV speichern.');
            $text=file_get_contents($this->csvFile->getRealPath(),false,null,0,1048577);
            if(!is_string($text))throw new \InvalidArgumentException('Datei konnte nicht gelesen werden.');
            $parsed=PreparedScanCsv::parse($text);
            $this->csvRows=$parsed['rows'];
            $this->csvBatchId=$this->selectedBatchId;
            $this->csvState=['ok'=>true,'message'=>'Vorschau fertig. Noch keine Daten gespeichert.',
                'count'=>count($this->csvRows),'duplicates'=>$parsed['duplicates'],
                'inserted'=>0,'existed'=>0,'restored'=>0];
        } catch (\InvalidArgumentException $e) {
            $this->csvState=['ok'=>false,'message'=>$e->getMessage()];
        }
    }

    /** One acknowledged chunk per request. No provider call or JTL write. */
    public function importCsvChunk(LegacyApprovedActionBridge $bridge): bool
    {
        $batch=$this->selectedBatchId;
        $label='';
        foreach($this->batches as $b)if($b['id']===$batch)$label=$b['label'];
        if(!$this->canWriteScan()||!$this->confirmCsvImport||
           $batch===null||$batch!==$this->csvBatchId||
           !preg_match('/^TEST(?:\\b|[-_])/iu',$label)||
           ($this->csvState['ok']??false)!==true){
            $this->csvState['message']='Import blockiert: TEST-Stapel, private Schreibfreigabe und ausdrückliche Bestätigung erforderlich.';
            return false;
        }
        $rows=array_slice($this->csvRows,$this->csvOffset,25);
        if(!$rows)return false;
        // Bridge and legacy API cap the JSON payload. Preserve all metadata.
        while(count($rows)>1&&strlen(json_encode(['op'=>'import_rows','batch_id'=>$batch,'rows'=>$rows],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))>22000)
            array_pop($rows);
        $r=$bridge->post('scan.import',['op'=>'import_rows','batch_id'=>$batch,'rows'=>$rows]);
        $data=$r['data']??[];
        if(($r['ok']??false)!==true||!is_array($data)||(int)($data['received']??-1)!==count($rows)||
           (int)($data['inserted']??-1)+(int)($data['existed']??-1)+(int)($data['restored']??-1)!==count($rows)){
            $this->csvState['message']='Import gestoppt: Speicherbestätigung fehlt. Fortschritt bleibt erhalten; bitte Stapel prüfen. Erneutes Einlesen zählt keine Stücke doppelt.';
            $this->confirmCsvImport=false;
            return false;
        }
        foreach(['inserted','existed','restored'] as $k)$this->csvState[$k]+=(int)$data[$k];
        $this->csvOffset+=count($rows);
        $more=$this->csvOffset<count($this->csvRows);
        $this->csvState['message']=$more?'Teilimport bestätigt.':'CSV-Import abgeschlossen. Gespeicherte Positionen erneut laden.';
        if($this->autoQueueResearch()){
            $msg=$this->enqueueConfirmedResearch(array_column($rows,'ean'));
            $this->csvState['message'].=$msg;
            if(str_contains($msg,'nicht vollständig bestätigt'))$this->csvState['research_warning']=true;
        }
        if($this->csvState['research_warning']??false)$this->csvState['message'].=' Mindestens eine Recherchezuordnung bitte nach dem Import prüfen.';
        if(!$more)$this->confirmCsvImport=false;
        return $more;
    }

    protected static ?string $title = 'Scanliste & Sammelimport';
    protected static ?string $navigationLabel = 'Scanliste';
    protected static ?string $slug = 'scanliste';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.scan-workbench';

    public string $newBatchLabel = '';
    public string $scanEan = '';
    public bool $confirmScanWrite = false;

    #[Locked] public array $actionState = [];
    #[Locked] public array $exportState = [];
    #[Locked] public array $batchAiMarkers = [];
    #[Locked] public array $batchAiStatus = [];

    #[Locked]
    public array $batches = [];

    #[Locked]
    public array $items = [];

    #[Locked]
    public ?int $selectedBatchId = null;

    #[Locked]
    public array $diagnostic = [];


    #[Locked] public array $persistentResearch = [];
    #[Locked] public string $persistentResearchMessage = '';
    public array $selectedEans = [];
    public bool $confirmResearch = false;
    #[Locked] public array $researchQueue = [];
    #[Locked] public array $researchResults = [];
    #[Locked] public int $researchOffset = 0;
    #[Locked] public string $researchMessage = '';
    #[Locked] public ?string $workingEan = null;
    #[Locked] public int $workflowStep = 1;
    #[Locked] public array $workingPrice = [];
    #[Locked] public array $jtlFieldReview = [];
    #[Locked] public array $peerReview = [];
    #[Locked] public array $workingAi = [];
    public bool $confirmSingleAi = false;
    public function loadWorkingAi(LegacyReadBridge $bridge): void
    {
        $ean=$this->workingEan;
        if($ean===null||$this->rowFor($ean)===null)return;
        $r=$bridge->fetch('ai.history',['ean'=>$ean,'limit'=>1]);
        $raw=$r['data']??[];
        $ok=($r['ok']??false)===true&&is_array($raw['reviews']??null);
        $row=$ok?($raw['reviews'][0]??null):null;
        $body=is_array($row['review']??null)?$row['review']:[];
        $facts=is_array($body['productFacts']??null)?$body['productFacts']:[];
        $this->workingAi=['ok'=>$ok,'http'=>$r['http_status']??null,'found'=>is_array($row),
            'id'=>(int)($row['id']??0),'scope'=>($raw['scope']??'')==='shared_workspace'?'Gemeinsames Archiv':'Legacy-Konto',
            'title'=>mb_substr((string)($facts['suggestedTitle']??''),0,500),
            'manufacturer'=>mb_substr((string)($facts['manufacturer']??''),0,180),
            'group'=>mb_substr((string)($body['suggestedWarengruppe']??''),0,180),
            'notes'=>mb_substr((string)($facts['notes']??''),0,1200),
            'reason'=>mb_substr((string)($body['reason']??''),0,1200)];
    }

    public function useAiSuggestion(): void
    {
        if($this->workingEan===null||($this->workingAi['ok']??false)!==true||($this->workingAi['found']??false)!==true)return;
        foreach(['Artikelname'=>'title','Hersteller'=>'manufacturer','Warengruppe'=>'group'] as $field=>$key){
            if(trim((string)($this->draftFields[$field]??''))==='')$this->draftFields[$field]=$this->workingAi[$key]??'';
        }
        $this->draftMessage='KI-Vorschläge in leere Felder übernommen; fachlich prüfen, noch nicht gespeichert.';
    }

    public function runWorkingAi(LegacyApprovedActionBridge $bridge,LegacyReadBridge $read): void
    {
        $ean=$this->workingEan;$row=$ean!==null?$this->rowFor($ean):null;
        $confirmed=$this->confirmSingleAi;$this->confirmSingleAi=false;
        if(!$row||!$confirmed||config('hw24_legacy.ai_paid_enabled')!==true){
            $this->draftMessage='Kostenpflichtige Einzel-KI nicht freigegeben.';return;
        }
        $name=mb_substr(trim((string)($this->draftFields['Artikelname']??'')),0,500);
        if($name===''){$this->draftMessage='Zuerst belegten Produktnamen eingeben.';return;}
        $this->loadWorkingAi($read);
        if(($this->workingAi['ok']??false)!==true||($this->workingAi['found']??false)===true){
            $this->draftMessage='Archiv nicht lesbar oder Bericht bereits vorhanden. Keine neue kostenpflichtige Abfrage.';return;
        }
        // On older actor-only API we cannot rule out an existing shared report.
        if(($this->workingAi['scope']??'')!=='Gemeinsames Archiv'){
            $this->draftMessage='Gemeinsames KI-Archiv noch nicht bestätigt. Keine kostenpflichtige Sammel-/Einzelabfrage aus dieser Ansicht.';return;
        }
        $budget=$read->fetch('ai.budget');$b=$budget['data']??[];
        if(($budget['ok']??false)!==true||($b['enabled']??false)!==true||($b['archive_ready']??false)!==true||
            !is_numeric($b['usd_month']??null)||!is_numeric($b['usd_budget']??null)||(float)$b['usd_month']>=(float)$b['usd_budget']){
            $this->draftMessage='KI-Budget oder Archivfreigabe nicht bestätigt. Keine neue Abfrage.';return;
        }
        $candidates=[];
        foreach(array_slice($this->researchResults[$ean]['items']??[],0,8) as $c){
            $candidates[]=['asin'=>$c['asin'],'title'=>$c['title'],'brand'=>$c['brand'],'eanConfirmed'=>$c['gtin_match'],'packKind'=>$c['pack_kind']??'unknown','packUnits'=>$c['pack_units']??null];
        }
        $r=$bridge->post('ai.review',['ean'=>$ean,'articleName'=>$name,
            'brand'=>mb_substr((string)($this->draftFields['Hersteller']??''),0,180),
            'warengruppe'=>mb_substr((string)($this->draftFields['Warengruppe']??''),0,150),
            'category'=>'','unit'=>'','jtl_status'=>$row['resolution']==='existing'?'current_snapshot':'unverified','candidates'=>$candidates]);
        $this->draftMessage=($r['ok']??false)===true?'KI-Antwort erhalten; Archiv erneut geladen.':'KI-Auftrag nicht eindeutig bestätigt. Vor erneuter Abfrage Archiv und Kosten prüfen.';
        $this->loadWorkingAi($read);
    }

    public array $draftFields = [];
    public string $templateNumber = '';
    #[Locked] public array $templateReview = [];
    #[Locked] public array $templateCandidates = [];
    #[Locked] public array $draftProvenance = [];
    public array $selectedExportFields = ScanResearch::FIELDS;

    public function allExportFields(): void
    {
        $this->selectedExportFields=ScanResearch::FIELDS;
        $this->exportState=[];
    }

    public function updatedSelectedExportFields(): void
    {
        $this->exportState=[];
    }

    public function missingDraftFields(): array
    {
        return array_values(array_filter(ScanResearch::REQUIRED,fn($f)=>trim((string)($this->draftFields[$f]??''))===''));
    }

    public bool $confirmDraft = false;
    #[Locked] public string $draftMessage = '';

    private function rowFor(string $ean): ?array
    {
        foreach($this->items as $row)if($row['ean']===$ean && self::validGtin($ean) && $row['review']!=='removed')return $row;
        return null;
    }

    private function isTestResearchBatch(): bool
    {
        foreach($this->batches as $batch)
            if($batch['id']===$this->selectedBatchId && preg_match('/^TEST(?:\\b|[-_])/iu',$batch['label']))return true;
        return false;
    }

    public function canQueueResearch(): bool
    {
        return $this->isTestResearchBatch()&&app(ScanQueueIntake::class)->enabled();
    }

    public function autoQueueResearch(): bool
    {
        return $this->canQueueResearch()&&app(ScanQueueIntake::class)->automatic();
    }

    /** Only accepted scan rows or explicitly acknowledged import chunk. */
    private function enqueueConfirmedResearch(array $eans): string
    {
        try {
            $r=app(ScanQueueIntake::class)->enqueue($eans);
            return ' Recherche: '.$r['new'].' neue Aufträge, '.($r['total']-$r['new']).' bereits vorhanden.';
        }catch(\Throwable){
            return ' Recherchezuordnung nicht vollständig bestätigt. Gespeicherte Positionen neu laden und zur Recherche anmelden; nicht erneut scannen.';
        }
    }

    public function enqueueSelectedResearch(): void
    {
        if(!$this->canQueueResearch()||!$this->confirmResearch)return;
        $eans=[];
        foreach($this->items as $row){
            if($row['review']!=='removed'&&self::validGtin($row['ean'])&&in_array($row['ean'],$this->selectedEans,true))$eans[]=$row['ean'];
        }
        if(!$eans){$this->persistentResearchMessage='Keine gültigen markierten Positionen.';return;}
        $this->persistentResearchMessage=trim($this->enqueueConfirmedResearch($eans));
        $this->confirmResearch=false;
        $this->refreshPersistentResearch();
    }

    public function refreshPersistentResearch(): void
    {
        $this->persistentResearch=[];
        if(!$this->canQueueResearch())return;
        $eans=[];
        foreach($this->items as $row)if($row['review']!=='removed'&&self::validGtin($row['ean']))$eans[]=$row['ean'];
        try{$this->persistentResearch=app(ScanQueueIntake::class)->statuses(array_values(array_unique($eans)));}
        catch(\Throwable){$this->persistentResearchMessage.=' Warteschlangenstatus momentan nicht verfügbar.';}
    }

    public function selectAllResearch(): void
    {
        $this->selectedEans=array_values(array_map(static fn($r)=>$r['ean'],
            array_filter($this->items,static fn($r)=>$r['review']!=='removed'&&self::validGtin($r['ean']))));
    }

    public function prepareResearch(): void
    {
        if(!$this->confirmResearch||$this->selectedBatchId===null)return;
        $this->researchQueue=[];
        foreach(array_unique(array_filter($this->selectedEans,'is_string')) as $ean)
            if(self::validGtin($ean)&&$this->rowFor($ean)!==null&&($this->researchResults[$ean]['amazon_ok']??false)!==true)$this->researchQueue[]=$ean;
        $this->researchOffset=0;
        $this->researchMessage=count($this->researchQueue).' Positionen vorgemerkt. Browser geöffnet lassen.';
    }

    /** Browser-driven sequence; no hidden paid calls, no automatic retries. */
    public function researchNext(LegacyReadBridge $bridge): bool
    {
        if(!$this->confirmResearch||!isset($this->researchQueue[$this->researchOffset]))return false;
        $ean=$this->researchQueue[$this->researchOffset];
        if($this->rowFor($ean)===null)return false;
        $result=ScanResearch::research($ean,$bridge);
        $this->researchResults[$ean]=$result;
        if(!$result['amazon_ok']){
            $this->confirmResearch=false;
            $this->researchMessage='Amazon-Antwort nicht erfolgreich (HTTP '.($result['http']??'unbekannt').'). Warteschlange angehalten; bitte Fehler prüfen.';
            return false;
        }
        $this->researchOffset++;
        $more=$this->researchOffset<count($this->researchQueue);
        $this->researchMessage=$more?'Recherche läuft.':'Ausgewählte Positionen recherchiert. Produktzuordnung und Entwürfe prüfen.';
        if(!$more)$this->confirmResearch=false;
        return $more;
    }

    public function showWorkflowStep(int $step): void
    {
        if(in_array($step,[1,2,3],true))$this->workflowStep=$step;
    }

    public function editableEans(): array
    {
        return array_values(array_unique(array_column(array_filter($this->items,fn($row)=>
            ($row['review']??null)!=='removed'&&self::validGtin((string)($row['ean']??''))),'ean')));
    }

    public function moveDraft(int $direction): void
    {
        if(!in_array($direction,[-1,1],true))return;
        $eans=$this->editableEans();$i=array_search($this->workingEan,$eans,true);
        if($i===false||!isset($eans[$i+$direction]))return;
        $this->openDraft($eans[$i+$direction]);
    }

    public function openDraft(string $ean): void
    {
        $row=$this->rowFor($ean);
        if($row===null)return;
        $this->reset(['draftFields','templateNumber','templateReview','templateCandidates','draftProvenance','confirmDraft','draftMessage','workingPrice','jtlFieldReview','peerReview','workingAi','confirmSingleAi']);
        $this->workingEan=$ean;
        $this->workflowStep=2;
        foreach(ScanResearch::FIELDS as $field){
            $v=$row['draft'][$field]??'';
            $this->draftFields[$field]=is_scalar($v)?(string)$v:'';
        }
        $this->draftFields['GTIN']=$ean;
        if($this->draftFields['Artikelname']==='')$this->draftFields['Artikelname']=$row['name'];
    }

    public function findMatchingTemplates(HubArticleMirror $mirror): void
    {
        $row=$this->workingEan!==null?$this->rowFor($this->workingEan):null;
        if(!$row||$row['resolution']!=='new_candidate')return;
        $this->templateReview=[];
        $this->templateCandidates=$mirror->templates($this->draftFields,config('hw24_jtl_reference_observations',[]));
    }

    public function chooseMatchingTemplate(int $index,LegacyReadBridge $bridge): void
    {
        if(($this->templateCandidates['ok']??false)!==true)return;
        $number=$this->templateCandidates['items'][$index]['number']??null;
        if(!is_string($number))return;
        $this->templateNumber=$number;
        $this->loadTemplate($bridge);
    }

    public function fillSeoDefaults(): void
    {
        $row=$this->workingEan!==null?$this->rowFor($this->workingEan):null;
        if(!$row||$row['resolution']!=='new_candidate')return;
        foreach(HubSeoDefaults::propose($this->draftFields) as $field=>$value){
            $this->draftFields[$field]=$value;
            $this->draftProvenance[$field]=['value'=>$value,'source'=>'HW24-Textregel: eigener belegter Artikelname (kein KI-Aufruf)'];
        }
        $this->confirmDraft=false;
    }

    /** Only a manually selected, exact JTL template number; no paid/provider calls. */
    public function loadTemplate(LegacyReadBridge $bridge): void
    {
        $this->templateReview=[];
        $ean=$this->workingEan;$row=$ean!==null?$this->rowFor($ean):null;
        if(!$row||$row['resolution']!=='new_candidate')return;
        $number=trim($this->templateNumber);
        if(!preg_match('/^[\\p{L}\\p{N}][\\p{L}\\p{N}._\\/\\-]{1,63}$/uD',$number)){
            $this->templateReview=['ok'=>false,'message'=>'Bitte genaue JTL-Vorlagenartikelnummer eingeben.'];return;
        }
        $this->templateReview=HubTemplateDefaults::propose($number,$ean,$this->draftFields,
            $bridge->fetch('jtl.article',['q'=>$number]),config('hw24_jtl_reference_observations',[]));
    }

    public function applyTemplate(LegacyReadBridge $bridge): void
    {
        if(($this->templateReview['ok']??false)!==true||
            ($this->templateReview['number']??null)!==trim($this->templateNumber))return;
        $previous=$this->templateReview;
        $this->loadTemplate($bridge);
        if(($this->templateReview['ok']??false)!==true||$previous!==$this->templateReview){
            $this->draftMessage='Vorlagenstand hat sich geändert. Bitte Vorschlag erneut prüfen.';return;
        }
        foreach($this->templateReview['fields'] as $field=>$value){
            if(trim((string)($this->draftFields[$field]??''))!=='')continue;
            $this->draftFields[$field]=$value;
            $this->draftProvenance[$field]=['value'=>$value,'source'=>'JTL-Vorlage kArtikel '.$this->templateReview['id'].' (manuell ausgewählt)'];
        }
        $this->confirmDraft=false;
        $this->draftMessage='Nur leere Standardfelder ergänzt. Noch nicht gespeichert oder freigegeben.';
    }

    public function fillEmptyTexts(): void
    {
        $ean=$this->workingEan;$row=$ean!==null?$this->rowFor($ean):null;
        if(!$row||$row['resolution']!=='new_candidate')return;
        foreach(HubTemplateDefaults::emptyTexts($this->draftFields) as $field=>$value){
            $this->draftFields[$field]=$value;
            $this->draftProvenance[$field]=['value'=>$value,'source'=>'HW24-Regel: eigener Artikelname als leerer Basistext'];
        }
        $this->confirmDraft=false;
    }

    /** Explicit peer read; no external API or automatic acceptance. */
    public function loadPeerReview(HubPeerMirror $reader): void
    {
        $this->peerReview=[];
        $ean=$this->workingEan;
        if($ean===null||$this->rowFor($ean)===null)return;
        try{$this->peerReview=$reader->read($ean);}
        catch(\Throwable){$this->peerReview=['ok'=>false,'message'=>'Vergleich nicht verfügbar: aktueller eindeutiger JTL-Spiegel und passende Referenzgruppe erforderlich.'];}
    }

    public function categoryProposal(): ?array
    {
        return HubCategoryPolicy::suggest((string)($this->draftFields['Artikelname']??''),(string)($this->draftFields['Warengruppe']??''));
    }

    public function applyCategoryProposal(): void
    {
        if($this->workingEan===null||$this->rowFor($this->workingEan)===null)return;
        $p=$this->categoryProposal();
        if($p===null){$this->draftMessage='Kategorie nicht eindeutig. Bitte manuell entscheiden.';return;}
        foreach($p as $field=>$value)$this->draftFields[$field]=$value;
        $this->confirmDraft=false;
        $this->draftMessage='Kategorie-Vorschlag übernommen; noch nicht gespeichert oder freigegeben.';
    }

    /** Explicit, read-only field assessment, never a marketplace/AI request. */
    public function loadJtlFieldReview(LegacyReadBridge $bridge,HubArticleMirror $mirror): void
    {
        $this->jtlFieldReview=[];
        $ean=$this->workingEan;
        if($ean===null||$this->rowFor($ean)===null)return;
        $this->jtlFieldReview=ScanResearchPresentation::jtlFields($ean,$mirror->enrich($bridge->fetch('jtl.article',['q'=>$ean])),config('hw24_jtl_reference_observations',[]));
    }

    public function researchOne(string $ean,LegacyReadBridge $bridge): void
    {
        if($this->rowFor($ean)===null||!self::validGtin($ean))return;
        $this->workingPrice=[];
        $this->researchResults[$ean]=ScanResearch::research($ean,$bridge);
    }

    public function useCatalogSuggestion(string $asin): void
    {
        $ean=$this->workingEan;
        if($ean===null||$this->rowFor($ean)===null)return;
        foreach($this->researchResults[$ean]['items']??[] as $candidate){
            if($candidate['asin']!==$asin||!$candidate['gtin_match']||($candidate['pack_kind']??'')==='conflict')continue;
            foreach(['ASIN'=>'asin','Artikelname'=>'title','Hersteller'=>'manufacturer'] as $field=>$key)
                if(trim((string)($this->draftFields[$field]??''))==='')$this->draftFields[$field]=$candidate[$key];
            $this->draftMessage='Amazon-Vorschlag in leere Felder übernommen. Packungsgröße und Identität manuell prüfen; noch nicht gespeichert.';
            return;
        }
    }

    public function priceCandidate(string $asin,LegacyReadBridge $bridge): void
    {
        $ean=$this->workingEan;
        if($ean===null||$this->rowFor($ean)===null)return;
        foreach($this->researchResults[$ean]['items']??[] as $candidate){
            if($candidate['asin']===$asin&&$candidate['gtin_match']&&($candidate['pack_kind']??'')!=='conflict'){
                $this->workingPrice=ScanResearch::pricing($asin,$bridge);
                return;
            }
        }
    }

    public function saveDraft(bool $ready,LegacyApprovedActionBridge $bridge): void
    {
        $ean=$this->workingEan;$row=$ean!==null?$this->rowFor($ean):null;
        if(!$row||!$this->confirmDraft||!$this->canWriteScan()||$row['id']<1||
           $row['resolution']!=='new_candidate'){
            $this->draftMessage='Nur bestätigte Neuanlage-Kandidaten mit Schreibfreigabe und Bestätigung können hier gespeichert werden.';
            return;
        }
        if($ready&&HubCategoryPolicy::conflict((string)($this->draftFields['Artikelname']??''),
            (string)($this->draftFields['Warengruppe']??''),(string)($this->draftFields['Kategorie Ebene 1']??''),
            (string)($this->draftFields['Kategorie Ebene 2']??''))){
            $this->draftMessage='Kategorie widerspricht der geprüften HW24-Produktregel. Bitte vor Freigabe korrigieren.';
            return;
        }
        $fields=[];$sources=[];
        foreach(ScanResearch::FIELDS as $field){
            $value=$this->draftFields[$field]??'';
            if(!is_string($value))return;
            // Patch only changed fields; never send all empty fields as replacements.
            if($value!== (string)($row['draft'][$field]??'')||$field==='GTIN'){
                $fields[$field]=$field==='GTIN'?$ean:$value;
                $p=$this->draftProvenance[$field]??[];
                $sources[$field]=(($p['value']??null)===$value&&is_string($p['source']??null))?$p['source']:'Manuell geprüfter HW24-Entwurf';
            }
        }
        if(strlen(json_encode($fields,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR))>18000){
            $this->draftMessage='Entwurf zu groß. Bitte Texte in kleineren Bearbeitungsschritten speichern.';return;
        }
        $r=$bridge->post('scan.edit',['op'=>'edit','batch_id'=>$this->selectedBatchId,'item_id'=>$row['id'],
            'expected_revision'=>$row['revision'],'fields'=>$fields,'field_sources'=>$sources,'mark_ready'=>$ready]);
        if(($r['ok']??false)!==true){
            $this->draftMessage='Speicherung/Freigabe abgelehnt: '.mb_substr((string)($r['data']['error']??'Antwort nicht bestätigt'),0,100).'. Bitte aktuellen Stapel prüfen.';return;
        }
        $data=$r['data']??[];
        if(!is_array($data['draft']??null)||!is_numeric($data['revision']??null)||
           !in_array($data['review_state']??null,['draft','ready'],true)){
            $this->confirmDraft=false;
            $this->draftMessage='Speicherung gemeldet; Antwort nicht vollständig. Bitte Stapel neu laden und vor erneutem Speichern prüfen.';
            return;
        }
        foreach($this->items as &$stored){
            if($stored['ean']!==$ean)continue;
            $stored['revision']=(int)$data['revision'];
            $stored['draft']=array_intersect_key($data['draft'],array_flip(ScanResearch::FIELDS));
            $stored['review']=$data['review_state'];
            $stored['review_label']=$data['review_state']==='ready'?'Für Ameise-Prüfexport freigegeben':'Entwurf · in Bearbeitung';
            // No server update timestamp in this response: clear stale display.
            $stored['updated_at']=null;
            break;
        }
        unset($stored);
        $this->confirmDraft=false;
        $this->exportState=[];
        $this->draftMessage=$data['review_state']==='ready'?'Für Ameise-Prüfexport freigegeben.':'Entwurf zentral gespeichert.';
    }

    public function loadBatches(LegacyReadBridge $bridge): void
    {
        $this->reset(['items', 'batches', 'selectedBatchId', 'diagnostic', 'exportState', 'batchAiMarkers', 'batchAiStatus','selectedEans','confirmResearch','persistentResearch','persistentResearchMessage','researchQueue','researchResults','researchOffset','researchMessage','workingEan','workingPrice','jtlFieldReview','peerReview','workingAi','confirmSingleAi','draftFields','templateNumber','templateReview','templateCandidates','draftProvenance','confirmDraft','draftMessage']);

        $result = $bridge->fetch('scan.batches', ['op' => 'batches']);
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];

        if (($result['ok'] ?? false) !== true ||
            !is_array($raw['batches'] ?? null)) {
            $this->diagnostic = [
                'ok' => false,
                'http' => $result['http_status'] ?? null,
                'message' => 'Die vorhandene Scanlisten-API ist nicht erreichbar oder dem angemeldeten Nutzer nicht zugeordnet. Bestehende Daten wurden nicht verändert.',
            ];
            return;
        }

        foreach (array_slice($raw['batches'], 0, 50) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $id = filter_var($entry['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) {
                continue;
            }

            $this->batches[] = [
                'id' => $id,
                'label' => mb_substr((string) ($entry['label'] ?? 'Ohne Bezeichnung'), 0, 160),
                'created' => (string) ($entry['created_at_utc'] ?? ''),
                'updated' => (string) ($entry['updated_at_utc'] ?? ''),
            ];
        }

        $this->diagnostic = [
            'ok' => true,
            'http' => $result['http_status'] ?? null,
            'message' => 'Scan-Stapel aus der bestehenden HW24-MariaDB geladen. Kein neuer Stapel angelegt.',
        ];
    }

    public function loadBatch(int $batchId, LegacyReadBridge $bridge): void
    {
        $this->reset(['items', 'selectedBatchId', 'exportState', 'batchAiMarkers', 'batchAiStatus','selectedEans','confirmResearch','persistentResearch','persistentResearchMessage','researchQueue','researchResults','researchOffset','researchMessage','workingEan','workingPrice','jtlFieldReview','peerReview','workingAi','confirmSingleAi','draftFields','templateNumber','templateReview','templateCandidates','draftProvenance','confirmDraft','draftMessage']);
        // Never let the browser request arbitrary batch IDs.
        $allowed = array_column($this->batches, 'id');
        if (!in_array($batchId, $allowed, true)) {
            $this->diagnostic = [
                'ok' => false,
                'http' => null,
                'message' => 'Bitte zunächst einen verfügbaren eigenen Scan-Stapel auswählen.',
            ];
            return;
        }
        $result = $bridge->fetch('scan.items', ['op' => 'items', 'batch_id' => $batchId]);
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];

        if (($result['ok'] ?? false) !== true || !is_array($raw['items'] ?? null)) {
            $this->diagnostic = [
                'ok' => false,
                'http' => $result['http_status'] ?? null,
                'message' => 'Die Positionen dieses Stapels konnten nicht gelesen werden. Der bisherige Bestand bleibt unverändert.',
            ];
            return;
        }
        $this->selectedBatchId = $batchId;
        foreach (array_slice($raw['items'], 0, 3000) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $this->items[] = [
                'id'=>(int)($item['id']??0),
                'revision'=>(int)($item['revision']??0),
                'draft'=>array_intersect_key(is_array($item['draft']??null)?$item['draft']:[],array_flip(ScanResearch::FIELDS)),
                'ean' => mb_substr((string) ($item['ean'] ?? ''), 0, 14),
                'name' => (string) ($item['artikelname'] ?? ''),
                'article_number' => mb_substr((string) ($item['artikelnummer'] ?? ''), 0, 100),
                'scans' => max(0, (int) ($item['scan_count'] ?? 0)),
                'resolution' => mb_substr((string) ($item['resolution'] ?? 'unverified'), 0, 40),
                'review' => mb_substr((string) ($item['review_state'] ?? ''), 0, 40),
                ...ScanEntryPresentation::present($item),
            ];
        }
        $this->diagnostic = [
            'ok' => true,
            'http' => $result['http_status'] ?? null,
            'message' => 'Gespeicherte Positionen geladen. Recherche und Entwurf sind getrennte, ausdrücklich ausgelöste Aktionen.',
        ];
        $this->refreshPersistentResearch();
    }

    /**
     * Reuse the original legacy scanlist's actor-scoped batch AI markers.
     * Explicit single historical GET for max. 150 EANs, no OpenAI call.
     */
    public function loadBatchAiMarkers(LegacyReadBridge $bridge): void
    {
        $this->reset(['batchAiMarkers','batchAiStatus']);
        if($this->selectedBatchId===null || !in_array($this->selectedBatchId,array_column($this->batches,'id'),true)) {
            $this->batchAiStatus=['ok'=>false,'message'=>'Bitte zuerst einen eigenen Scan-Stapel auswählen.'];
            return;
        }
        $eans=[];
        foreach($this->items as $item){
            if($this->selectedEans!==[]&&!in_array($item['ean'],$this->selectedEans,true))continue;
            $ean=(string)($item['ean']??'');
            if(preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean))
                $eans[$ean]=true;
            if(count($eans)>=150)break;
        }
        if($eans===[]){
            $this->batchAiStatus=['ok'=>false,'message'=>'Keine gültigen EANs zum Prüfen geladen.'];
            return;
        }
        $r=$bridge->fetch('ai.summary',['summary'=>1,'eans'=>implode(',',array_keys($eans))]);
        $raw=is_array($r['data']??null)?$r['data']:[];
        $summaries=is_array($raw['summaries']??null)?$raw['summaries']:[];
        if(($r['ok']??false)!==true || ($raw['summary']??false)!==true){
            $this->batchAiStatus=['ok'=>false,'message'=>'Vorhandener benutzerbezogener KI-Archivstatus konnte nicht bestätigt werden.'];
            return;
        }
        foreach($summaries as $ean=>$summary){
            if(!isset($eans[$ean])||!is_array($summary))continue;
            $id=filter_var($summary['review_id']??null,FILTER_VALIDATE_INT);
            if($id===false||$id<1)continue;
            $this->batchAiMarkers[$ean]=[
                'found'=>true,
                'id'=>$id,
                'count'=>max(1,(int)($summary['count']??1)),
                'at'=>mb_substr((string)($summary['at_utc']??''),0,40),
            ];
        }
        $found=count($this->batchAiMarkers);
        foreach(array_keys($eans) as $checkedEan){
            if(!isset($this->batchAiMarkers[$checkedEan])){
                $this->batchAiMarkers[$checkedEan]=[
                    'found'=>false,'id'=>null,'count'=>0,'at'=>null,
                ];
            }
        }
        $this->batchAiStatus=['ok'=>true,'checked'=>count($eans),
            'found'=>$found,
            'checked_eans'=>array_keys($eans),
            'partial'=>count($this->items)>150,
            'message'=>'Vorhandenes KI-Archiv kostenlos geprüft. Keine neue KI-Analyse ausgelöst.'];
    }

    public function canWriteScan(): bool
    {
        return config('hw24_legacy.scan_writes_enabled') === true;
    }

    /** A new scan batch is created only on an operator click and double opt-in. */
    public function createBatch(LegacyApprovedActionBridge $bridge, LegacyReadBridge $read): void
    {
        $this->actionState = [];
        if (!$this->canWriteScan() || !$this->confirmScanWrite) {
            $this->actionState=['ok'=>false,'message'=>'Private Scanner-Freigabe und ausdrückliche Bestätigung erforderlich. Keine Änderung durchgeführt.'];
            return;
        }
        $label=trim($this->newBatchLabel);
        if ($label==='' || mb_strlen($label)>160) {
            $this->actionState=['ok'=>false,'message'=>'Stapelname muss zwischen 1 und 160 Zeichen enthalten.'];
            return;
        }
        $r=$bridge->post('scan.create',['op'=>'create','label'=>$label]);
        if (($r['ok']??false)!==true) {
            $this->actionState=['ok'=>false,'message'=>'Stapelanlage nicht eindeutig bestätigt. Bitte vor Wiederholung vorhandene Stapel lesen.',
                'http'=>$r['http_status']??null];
            return;
        }
        $this->newBatchLabel='';
        $this->confirmScanWrite=false;
        $this->loadBatches($read);
        $this->actionState=['ok'=>true,'message'=>'Scan-Stapel in bestehender HW24-MariaDB erstellt; Stapelliste aktualisiert.'];
    }

    /** Idempotent scan-event-key recognized by the existing legacy API. */
    public function scanEanIntoBatch(LegacyApprovedActionBridge $bridge, LegacyReadBridge $read): void
    {
        $this->actionState = [];
        $batch=$this->selectedBatchId;
        $allowed=array_column($this->batches,'id');
        $ean=trim($this->scanEan);
        if (!$this->canWriteScan() || !$this->confirmScanWrite ||
            $batch===null || !in_array($batch,$allowed,true) || !self::validGtin($ean)) {
            $this->actionState=['ok'=>false,
                'message'=>'Aktiven eigenen Stapel, gültige GTIN und ausdrückliche Scanner-Freigabe prüfen. Kein Scan gespeichert.'];
            return;
        }
        $payload=['op'=>'scan','batch_id'=>$batch,'ean'=>$ean,'event_key'=>(string)Str::uuid()];
        $r=$bridge->post('scan.scan',$payload);
        if (($r['ok']??false)!==true) {
            $this->actionState=['ok'=>false,'http'=>$r['http_status']??null,
                'message'=>'Speicherantwort nicht eindeutig. Bitte Liste aktualisieren, BEVOR ein Scan erneut erfasst wird.'];
            return;
        }
        $this->scanEan='';
        $this->confirmScanWrite=false;
        $this->loadBatch($batch,$read);
        $message='EAN in der bestehenden Scanliste gespeichert. Keine JTL-Lagerbuchung durchgeführt.';
        if($this->autoQueueResearch()){
            if($this->rowFor($ean)!==null)$message.=$this->enqueueConfirmedResearch([$ean]);
            else $message.=' Recherche noch nicht angemeldet: gespeicherte Position bitte neu laden und markieren.';
            $this->refreshPersistentResearch();
        }
        $this->actionState=['ok'=>true,'message'=>$message];
    }

    private static function validGtin(string $ean): bool
    {
        if (!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean)
            || preg_match('/^0+$/D',$ean)) return false;
        $sum=0; $factor=3;
        for ($i=strlen($ean)-2; $i>=0; $i--) {
            $sum+=(int)$ean[$i]*$factor;
            $factor=$factor===3?1:3;
        }
        return (10-$sum%10)%10===(int)$ean[strlen($ean)-1];
    }

    private function exportRows(LegacyReadBridge $bridge): ?array
    {
        $batch=$this->selectedBatchId;
        if ($batch===null || !in_array($batch,array_column($this->batches,'id'),true)) return null;
        $r=$bridge->fetch('scan.export',['op'=>'export','batch_id'=>$batch]);
        $raw=is_array($r['data']??null)?$r['data']:[];
        if (($r['ok']??false)!==true || !is_array($raw['fields']??null) ||
            count($raw['fields'])<1 || count($raw['fields'])>200 ||
            count($raw['fields'])!==(int)($raw['count']??-1)) return null;
        foreach($raw['fields'] as $entry) {
            if (!is_array($entry) || !self::validGtin((string)($entry['GTIN']??'')))return null;
        }
        return $raw['fields'];
    }

    /** Check readiness; the legacy API enforces snapshot freshness and duplicates. */
    public function prepareAmeiseExport(LegacyReadBridge $bridge): void
    {
        $this->exportState=[];
        $rows=$this->exportRows($bridge);
        if ($rows===null) {
            $this->exportState=['ok'=>false,
                'message'=>'Ameise-Sammelimport noch nicht freigegeben. Nur neue, eindeutig bestätigte Artikel mit frischem JTL-Snapshot und vollständig geprüften Pflichtfeldern sind zulässig.'];
            return;
        }
        $this->exportState=['ok'=>true,'count'=>count($rows),'batch_id'=>$this->selectedBatchId,
            'message'=>'Serverseitige Ameise-Prüfung bestanden. Nur ein CSV-Prüfexport, kein direkter JTL-Import.'];
    }

    /** Re-check upstream approval at download time; no persistence or JTL write. */
    public function downloadAmeiseCsv(LegacyReadBridge $bridge)
    {
        $batch=$this->selectedBatchId;
        if (($this->exportState['ok']??false)!==true ||
            ($this->exportState['batch_id']??null)!==$batch) return;
        $rows=$this->exportRows($bridge);
        if ($rows===null)return;
        // Only known original import fields are allowed into a local CSV draft.
        $whitelist=ScanResearch::FIELDS;
        $columns=[];
        foreach ($whitelist as $field) {
            if($field!=='Artikelnummer'&&!in_array($field,ScanResearch::REQUIRED,true)&&!in_array($field,$this->selectedExportFields,true))continue;
            if ($field==='Artikelnummer'||in_array($field,ScanResearch::REQUIRED,true)||
                array_any($rows,static fn($r) => array_key_exists($field,$r) && trim((string)$r[$field])!=='')) {
                $columns[]=$field;
            }
        }
        if (!in_array('Artikelname',$columns,true) || !in_array('Steuerklasse',$columns,true))return;
        $lines=[];
        $esc=static function($v): string {
            $v=trim(preg_replace('/[\r\n\x00-\x1f\x7f]/u',' ',(string)($v??''))??'');
            if ($v!=='' && preg_match('/^[=+\-@]/u',$v)) {
                // Do not silently prefix/modify import values; refuse spreadsheet formulas.
                throw new \UnexpectedValueException('Potential CSV formula');
            }
            return '"'.str_replace('"','""',$v).'"';
        };
        try {
            $lines[]=implode(';',array_map($esc,$columns));
            foreach($rows as $row){
                $lines[]=implode(';',array_map(static fn($col)=>$esc($row[$col]??''),$columns));
            }
        } catch (\UnexpectedValueException) {
            $this->exportState=['ok'=>false,'message'=>'Unsicherer Tabellenkalkulationswert in einem bestätigten Feld. Export verweigert.'];
            return;
        }
        $csv="\xEF\xBB\xBF".implode("\r\n",$lines)."\r\n";
        $this->exportState=[];
        return response()->streamDownload(static function()use($csv):void { echo $csv; },
            'HW24_Ameise_ScanStapel_'.$batch.'_PRUEFEXPORT.csv',
            ['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'private, no-store']);
    }

}
