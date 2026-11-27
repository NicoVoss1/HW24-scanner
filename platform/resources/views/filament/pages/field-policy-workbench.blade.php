<x-filament-panels::page>
<div class="hw24-grid">
    @include('filament.pages.reference-grid-styles')
    <h2 style="font-size:20px;font-weight:700">Verbindliche Pflichtfelder für Artikelpflege und Neuanlage</h2>
    <p>Einmal zentral festlegen. Grundpflichtfelder bleiben aktiv; die Artikelnummer vergibt JTL bei Neuanlage. Eine neue Pflicht kann einen Export blockieren, bis der Wert belegt ist.</p>
    <div class="hw24-toolbar"><label>Felder suchen<input type="search" wire:model.live.debounce.300ms="search" placeholder="Hersteller, ASIN, Gewicht …"></label><button class="hw24-btn" wire:click="reloadPolicy">Neu laden</button><button class="hw24-btn" wire:click="savePolicy">Pflichtfelder speichern</button></div>
    @if($message!=='')<p role="status">{{ $message }}</p>@endif
    <p>Revision {{ $revision }} · {{ count($selected) }} ausgewählt · 41 derzeit unterstützte Entwurfsfelder</p>
    <table style="width:100%"><thead><tr><th>Feld</th><th>Verbindlich</th><th>Umfang</th></tr></thead><tbody>
    @foreach($fields as $f)
        <tr wire:key="policy-{{ sha1($f) }}"><td>{{ $f }}</td><td>
        @if(in_array($f,$core,true)) Ja · Grundpflicht
        @elseif($f==='Artikelnummer') JTL-Nummernkreis / Bestandszuordnung
        @else <label class="hw24-check"><input type="checkbox" wire:model.live="selected" value="{{ $f }}"> Pflichtfeld</label>
        @endif</td><td>{{ in_array($f,\App\Services\HubExistingArticleExport::FIELDS,true)?'Neuanlage und Bestands-CSV':'Neuanlage; Bestandszuordnung prüfen' }}</td></tr>
    @endforeach
    </tbody></table>
    <details style="margin-top:20px"><summary>Original-Ameise-Feldkatalog · {{ $columnCount }} Spalten</summary>
        <p>Vollständiger Originalbestand inklusive leerer Spalten. Noch nicht zugeordnete Felder können erst nach Prüfung der Importsemantik verbindlich aktiviert werden.</p>
        @if(!$archiveAvailable)<p>Originalarchiv nicht verfügbar.</p>@endif
        <table style="width:100%"><thead><tr><th>Position</th><th>Originalfeld</th><th>Befüllte Zeilen</th><th>Zuordnung</th></tr></thead><tbody>
        @foreach($columns as $c)<tr><td>{{ $c['position'] }}</td><td>{{ $c['name']?:'(ohne Überschrift)' }}</td><td>{{ $c['filled_count'] }}</td><td>{{ in_array($c['name'],\App\Services\ScanResearch::FIELDS,true)?'Entwurfsfeld vorhanden':'Mapping offen' }}</td></tr>@endforeach
        </tbody></table>
    </details>
</div>
</x-filament-panels::page>
