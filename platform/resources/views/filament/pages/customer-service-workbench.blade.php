<x-filament-panels::page>
<div class="hw24-support">
<style>
.hw24-support{--sedge:#dbe5ef;--sink:#1b3045;--ssub:#53677b;color:var(--sink);font-size:13px}
.hw24-support *{box-sizing:border-box}
.hw24-support .hero{background:linear-gradient(115deg,#112c45,#16618b);color:#fff;border-radius:14px;padding:24px}
.hw24-support .hero h2{font-size:22px;font-weight:800;margin:7px 0}
.hw24-support .hero p{color:#e4f0f8;line-height:1.7}
.hw24-support .section{background:#fff;border:1px solid var(--sedge);border-radius:12px;padding:19px;margin-top:15px}
.hw24-support .section h3{font-size:16px;font-weight:800;margin-bottom:12px}
.hw24-support .text{width:100%;border:1px solid #b7c8d9;border-radius:8px;padding:10px 12px;background:#fff;color:#132f43}
.hw24-support .btn{display:inline-block;background:#175982;border:0;border-radius:8px;color:#fff;font-weight:750;padding:11px 17px;cursor:pointer}
.hw24-support .btn.sec{background:#e8f2fa;border:1px solid #cadce8;color:#15547b}
.hw24-support .btn:disabled{opacity:.55;cursor:wait}
.hw24-support .row{display:flex;align-items:end;gap:12px;flex-wrap:wrap}
.hw24-support .field{flex:1;min-width:240px;display:grid;gap:6px;font-weight:700}
.hw24-support .muted{color:var(--ssub);font-size:12px;line-height:1.7}
.hw24-support .notice{padding:10px 13px;border:1px solid #f0d5a6;background:#fff9ed;border-radius:8px;color:#75480c;margin:12px 0}
.hw24-support .notice.ok{border-color:#b8dece;background:#effaf4;color:#145538}
.hw24-support .card{border:1px solid #cddce7;border-radius:9px;padding:14px;margin-top:12px}
.hw24-support .event{padding:10px 0;border-top:1px solid #e5edf4}
.hw24-support .event:first-child{border-top:0}
.hw24-support .badge{background:#edf4fa;border-radius:100px;padding:3px 8px;font-size:11px;font-weight:700}
.hw24-support :is(button,a,input,textarea):focus-visible{outline:3px solid #71b1e1;outline-offset:2px}
@media(max-width:720px){.hw24-support .hero{padding:18px}.hw24-support .section{padding:14px}}
html.dark .hw24-support{--sedge:#36495a;--sink:#f3f6fa;--ssub:#c1ccda}
html.dark .hw24-support .section,html.dark .hw24-support .card{background:#172536}
html.dark .hw24-support .text{background:#253448;color:#f6faff;border-color:#52657c}
</style>
<header class="hero">
 <div style="font-size:11px;font-weight:800;letter-spacing:.09em">HAIRWORLD24 · ISOLIERTE TESTFUNKTION</div>
 <h2>KI-Kundenservice und Paketverfolgung</h2>
 <p>Amazon-Bestellung zuordnen, echte JTL-/DHL-Scans ansehen und eine editierbare Antwort vorbereiten. Keine automatischen Amazon-Nachrichten und kein unbestätigter Live-Status.</p>
</header>
<section class="section">
 <h3>01 · Bestellung aus dem JTL-Track-&-Trace-Snapshot</h3>
 <form wire:submit="loadTracking" class="row">
  <label class="field">Amazon-Bestellnummer
   <input class="text" wire:model="orderNumber" maxlength="19" autocomplete="off" placeholder="302-8659283-9232345" required>
  </label>
  <button class="btn" type="submit" wire:loading.attr="disabled" wire:target="loadTracking">Tracking-Daten laden</button>
 </form>
 @if($feedback!==[])
  <div role="status" class="notice {{ ($feedback['ok']??false)?'ok':'' }}">{{ $feedback['message'] }}</div>
 @endif
 @if(($evidence['ok']??false)===true)
  <p class="muted">Quelle: {{ $evidence['source'] }} · Snapshot importiert am {{ $evidence['snapshot_at_utc'] }} UTC.
   @if($evidence['stale']) <strong>Älterer Snapshot! Bitte aktuellen Status prüfen.</strong> @endif
   Rohscan-Zeitzone: {{ ($evidence['timezone_verified']??false)?'bestätigt':'nicht bestätigt' }}.
  </p>
  @forelse($evidence['packages'] as $package)
  <div class="card">
   <strong>{{ $package['carrier'] }} · {{ $package['tracking'] }}</strong>
   <p class="muted">JTL-Auftrag {{ $package['jtl_order'] }} · Lieferschein {{ $package['delivery_note'] }} · Paket {{ $package['package_id'] }}</p>
   @forelse($package['events'] as $event)
    <div class="event">
     <span class="badge">{{ $event['at_raw'] }} (JTL-Rohzeit)</span>
     <div style="margin-top:6px">{{ $event['text']?:'Ohne Ereignisbeschreibung' }}</div>
     @if($event['location']) <small class="muted">Scanort: {{ $event['location'] }}</small> @endif
    </div>
   @empty
    <p class="muted">Noch keine Scanereignisse im bereitgestellten Snapshot.</p>
   @endforelse
  </div>
  @empty
   <p class="notice">Kein eindeutiger Auftrag im bereitgestellten Snapshot. Das ist kein Nachweis für eine fehlende Sendung.</p>
  @endforelse
 @endif
</section>
<section class="section">
 <h3>02 · Anfrage und Screenshot</h3>
 <label class="field" for="hw24-support-request">Kundennachricht (optional, z. B. aus Seller Central kopiert)</label>
 <textarea id="hw24-support-request" class="text" rows="4" wire:model="customerMessage" maxlength="4500" placeholder="Wo ist mein Paket?"></textarea>
 <div style="margin-top:12px">
  <label class="field">Screenshot für die optionale KI-Auswertung (PNG/JPG/WebP, maximal 2 MB)
   <input class="text" type="file" wire:model="screenshot" accept="image/jpeg,image/png,image/webp">
  </label>
  <div class="muted" tabindex="0" style="border:1px dashed #a1b8cf;border-radius:9px;padding:12px;margin-top:9px"
   x-data
   x-on:paste="const img=Array.from($event.clipboardData?.files||[]).find(f=>['image/png','image/jpeg','image/webp'].includes(f.type));if(img){$wire.upload('screenshot',img);$event.preventDefault()}">
   Alternativ: Hier klicken und einen Screenshot aus der Zwischenablage mit Strg+V einfügen. Der Screenshot wird erst nach ausdrücklicher Freigabe für die externe KI verwendet.
  </div>
  @error('screenshot')<p class="notice">{{ $message }}</p>@enderror
  @error('customerMessage')<p class="notice">{{ $message }}</p>@enderror
 </div>
</section>
<section class="section">
 <h3>03 · Antwortentwurf (niemals automatisch versendet)</h3>
 <div class="row" style="align-items:center;margin-bottom:14px">
  <button class="btn sec" type="button" wire:click="prepareTemplate" wire:loading.attr="disabled">Kostenfreie Tracking-Vorlage</button>
  <button class="btn" type="button" wire:click="generateAiDraft" wire:loading.attr="disabled" wire:target="generateAiDraft">
   KI-Entwurf jetzt erstellen
  </button>
 </div>
 <label style="display:flex;gap:9px;align-items:start;margin-bottom:12px">
  <input type="checkbox" wire:model="approveExternalAi">
  <span>Ich gebe die angegebenen Kundendaten, DHL-Ereignisse und ggf. den Screenshot bewusst zur einmaligen externen KI-Verarbeitung frei. Dieser Vorgang kann Kosten verursachen.</span>
 </label>
 <p class="muted">KI ist standardmäßig abgeschaltet und benötigt einen privaten API-Schlüssel. Die API versendet keine Amazon-Nachrichten. Jede Aussage vor Verwendung prüfen.</p>
 <label class="field">Editierbare Antwort</label>
 <textarea id="hw24-support-draft" class="text" wire:model="draft" rows="12" placeholder="Nach dem Laden der Sendung eine Vorlage oder KI-Antwort erzeugen."></textarea>
 <p class="muted">{{ $draftSource?:'Noch kein Antwortentwurf erstellt' }}</p>
 <div class="row">
   <button type="button" class="btn sec" x-data x-on:click="navigator.clipboard.writeText(document.getElementById('hw24-support-draft').value)">Antwort kopieren</button>
   <span class="muted">Versand ausschließlich manuell über den dafür zulässigen Amazon-Kanal; automatische Messaging-API nicht freigeschaltet.</span>
 </div>
</section>
</div>
</x-filament-panels::page>
