<?php
declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\CustomerSupportDraftAi;
use App\Services\TrackingSnapshotReader;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;

/**
 * Isolierter Pilot: Amazon-Bestellnummer -> JTL-Tracking-Snapshot -> Entwurf.
 * Nie automatischer Versand; vorhandene JTL/Shop-APIs bleiben unangetastet.
 */
final class CustomerServiceWorkbench extends Page
{
    use WithFileUploads;

    protected static ?string $title='KI-Kundenservice';
    protected static ?string $navigationLabel='Kundenservice';
    protected static ?string $slug='kundenservice';
    protected static ?int $navigationSort=5;
    protected string $view='filament.pages.customer-service-workbench';

    public string $orderNumber='';
    public string $customerMessage='';
    public string $draft='';
    public bool $approveExternalAi=false;
    public $screenshot=null;

    #[Locked] public array $evidence=[];
    #[Locked] public array $feedback=[];
    #[Locked] public string $draftSource='';

    public function mount(): void
    {
        $order=trim((string)request()->query('order',''));
        if (preg_match('/^[0-9]{3}-[0-9]{7}-[0-9]{7}$/D',$order)) $this->orderNumber=$order;
        // Keine Netzwerkaufrufe, KI-Kosten oder PII-Abfragen beim Anzeigen.
    }

    public function updatedOrderNumber(): void
    {
        $this->evidence=[];
        $this->draft='';
        $this->draftSource='';
        $this->approveExternalAi=false;
    }

    public function loadTracking(TrackingSnapshotReader $reader): void
    {
        $this->evidence=[];
        $this->feedback=[];
        $this->draft='';
        $this->draftSource='';
        $this->approveExternalAi=false;
        $this->orderNumber=trim($this->orderNumber);
        $result=$reader->findOrder($this->orderNumber);
        $this->feedback=['ok'=>$result['ok']??false,'message'=>$result['message']??'Fehler'];
        if (($result['ok']??false)===true) $this->evidence=$result;
    }

    /**
     * Kostenlose, deterministische Textvorlage; ausdrücklich keine KI.
     * Zeitstempel im JTL-Rohformat, ohne unbestätigte Zeitzonenumrechnung.
     */
    public function prepareTemplate(): void
    {
        if (($this->evidence['state']??'')!=='ready') {
            $this->feedback=['ok'=>false,'message'=>'Zuerst eine Bestellung mit bestätigtem Snapshot laden.'];
            return;
        }
        $segments=[];
        foreach (array_slice($this->evidence['packages']??[],0,5) as $package) {
            $latest=$package['latest']??null;
            $tracking=(string)($package['tracking']??'');
            if (!$latest) {
                $segments[]="Zur Sendungsnummer ".$tracking." liegen in unserem aktuellen Datenspiegel noch keine DHL-Ereignisse vor.";
                continue;
            }
            $date=substr((string)($latest['at_raw']??''),0,10);
            $sentence=trim((string)($latest['text']??''));
            $segments[]="Sendungsnummer ".$tracking.": Laut dem letzten in JTL gespeicherten DHL-Status vom ".$date." lautet die Meldung: ".$sentence;
        }
        $intro="Guten Tag,\n\nvielen Dank für Deine Nachricht.\n\n";
        $reason="Ich habe die verfügbaren Sendungsinformationen zu Deiner Bestellung geprüft.\n\n";
        $note="\n\nBitte beachte, dass dies der zuletzt in unserer Warenwirtschaft gespeicherte Status ist und nicht zwingend der aktuelle Live-Status bei DHL. ";
        if ($this->evidence['stale']??true)
            $note.="Da die Datenaktualisierung länger zurückliegt, empfehle ich eine erneute Prüfung direkt bei DHL.";
        else $note.="Sollte sich der Status inzwischen geändert haben, hilft die DHL-Sendungsverfolgung weiter.";
        $this->draft=$intro.$reason.implode("\n\n",$segments).$note
            ."\n\nVielen Dank für Deine Geduld und Dein Verständnis.\n\n"
            ."Mit freundlichen Grüßen\nNico\nDein persönlicher Ansprechpartner\nFriseurbedarf24.shop";
        $this->draftSource='Regelbasierte Vorlage, keine KI und kein Live-Tracking';
        $this->feedback=['ok'=>true,'message'=>'Editierbare Vorlage erstellt. Kein Versand ausgelöst.'];
    }

    public function generateAiDraft(CustomerSupportDraftAi $ai): void
    {
        if (($this->evidence['state']??'')!=='ready') {
            $this->feedback=['ok'=>false,'message'=>'Bitte zuerst eine eindeutig zugeordnete Bestellung laden.'];
            return;
        }
        if (!$this->approveExternalAi) {
            $this->feedback=['ok'=>false,'message'=>'Vor dem externen KI-Aufruf musst Du die bewusste Datenfreigabe bestätigen.'];
            return;
        }
        $this->validate([
            'customerMessage'=>['nullable','string','max:4500'],
            'screenshot'=>['nullable','image','mimes:jpeg,jpg,png,webp','max:2048'],
        ]);
        $result=$ai->generate($this->customerMessage,$this->evidence,$this->screenshot);
        $this->feedback=['ok'=>$result['ok']??false,'message'=>$result['ok']??false
            ?'KI-Entwurf erstellt. Prüfe jede Aussage vor dem Kopieren.'
            :($result['message']??'KI-Dienst nicht verfügbar.')];
        if (($result['ok']??false)===true) {
            $this->draft=$result['draft'];
            $this->draftSource='KI-Entwurf ('.($result['model']??'Modell unbekannt').'), nicht versendet';
        }
        $this->approveExternalAi=false;
    }
}
