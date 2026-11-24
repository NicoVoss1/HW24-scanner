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
        $this->reset(['csvRows','csvState','csvOffset','csvBatchId','confirmCsvImport']);
    }

    public function previewCsv(): void
    {
        $this->reset(['csvRows','csvState','csvOffset','csvBatchId','confirmCsvImport']);
        if($this->selectedBatchId===null){
            $this->csvState=['ok'=>false,'message'=>'Bitte zuerst einen TEST-Stapel auswählen.'];
            return;
        }
        try {
            $this->validate(['csvFile'=>'required|file|max:1024']);
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

    public function loadBatches(LegacyReadBridge $bridge): void
    {
        $this->reset(['items', 'batches', 'selectedBatchId', 'diagnostic', 'exportState', 'batchAiMarkers', 'batchAiStatus']);

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
        $this->reset(['items', 'selectedBatchId', 'exportState', 'batchAiMarkers', 'batchAiStatus']);
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
                'ean' => mb_substr((string) ($item['ean'] ?? ''), 0, 14),
                'name' => mb_substr((string) ($item['artikelname'] ?? ''), 0, 180),
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
            'message' => 'Gespeicherte Scan-Positionen nur gelesen. Erfassen, Entwurf, Import und Export verbleiben vorerst im bisherigen HW24-Arbeitsbereich.',
        ];
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
        $this->actionState=['ok'=>true,'message'=>'EAN in der bestehenden Scanliste gespeichert. Keine JTL-Lagerbuchung durchgeführt.'];
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
        $whitelist=[
            'Artikelnummer','GTIN','HAN','Artikelname','Hersteller','Warengruppe','Steuerklasse',
            'Kosten','Netto-VK','Brutto-VK','Inhalt/Menge','Maßeinheit','Kurzbeschreibung','Beschreibung',
            'Artikel aktiv','Bestandsführung aktiv',
        ];
        $columns=[];
        foreach ($whitelist as $field) {
            if ($field==='Artikelnummer'||$field==='GTIN'||
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
