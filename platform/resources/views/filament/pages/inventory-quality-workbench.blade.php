<x-filament-panels::page>
<div class="hw24-grid">
    @include('filament.pages.reference-grid-styles')
    <p>Gemeinsame Pflichtfeldregeln für den gesamten aktuellen JTL-Spiegel. Fehlende Werte und nicht bestätigte Quellen werden getrennt ausgewiesen. Diese Prüfung schreibt nicht in JTL und startet keine Providerabfragen.</p>
    <div class="hw24-toolbar">
        <a class="hw24-btn" href="{{ \App\Filament\Pages\FieldPolicyWorkbench::getUrl() }}">Pflichtfelder konfigurieren</a>
        <button type="button" class="hw24-btn" wire:click="$refresh">Bestand erneut prüfen</button>
    </div>
    @if(!$ok)
        <p role="alert">Aktueller vollständiger Spiegel, Feldkonfiguration oder benötigte Lesesichten nicht bestätigt. Keine Bewertung verfügbar.</p>
    @else
        <p style="font-size:18px;font-weight:700">{{ number_format($articles,0,',','.') }} Artikel geprüft · {{ number_format($affected,0,',','.') }} mit offenen Pflichtfeldern</p>
        <p>Spiegelstand: {{ $at }} UTC · Pflichtfeldrevision {{ $revision }}</p>
        <details open><summary>Pflichtfeldstatistik · je Feld</summary>
            <div class="hw24-scroll"><table style="width:100%"><thead><tr><th>Pflichtfeld</th><th>Bestätigt / beobachtet</th><th>Fehlend / ungültig</th><th>Quelle / Identität prüfen</th></tr></thead><tbody>
            @foreach($counts as $name=>$c)<tr><td>{{ $name }}</td><td>{{ $c['confirmed'] }}</td><td>{{ $c['missing'] }}</td><td>{{ $c['unverified'] }}</td></tr>@endforeach
            </tbody></table></div>
        </details>
        <div class="hw24-toolbar">
            <label>Artikel suchen<input type="search" wire:model.live.debounce.500ms="search" maxlength="100" placeholder="Artikelnummer, Name, EAN, ASIN, Hersteller …"></label>
            <label>Pflichtfeld<select wire:model.live="field"><option value="">Alle Pflichtfelder</option>@foreach($counts as $name=>$c)<option value="{{ $name }}">{{ $name }}</option>@endforeach</select></label>
            <label>Art der Lücke<select wire:model.live="kind"><option value="all">Alle offenen</option><option value="missing">Fehlend / ungültig</option><option value="unverified">Quelle / Identität prüfen</option></select></label>
            <button type="button" class="hw24-btn" wire:click="clearFilters">Filter zurücksetzen</button>
        </div>
        <p>{{ $total }} passende Artikel · Seite {{ $page }} / {{ max(1,ceil($total/50)) }}</p>
        <div class="hw24-scroll"><table style="width:100%;min-width:950px"><thead><tr>@foreach(['Artikelnummer / ID','Produkt','GTIN / ASIN','Fehlend / ungültig','Nicht bestätigt','Artikelpflege'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
        @forelse($rows as $r)
            <tr wire:key="quality-{{ $r['id'] }}"><td>{{ $r['number'] }}<br><small>ID {{ $r['id'] }}</small></td>
                <td><strong>{{ $r['name']?:'Name fehlt' }}</strong><br>{{ $r['manufacturer']?:'Hersteller nicht belegt' }}</td>
                <td>{{ $r['ean']?:'GTIN fehlt' }}<br>{{ $r['asin']?:'ASIN fehlt' }}
                    @if($r['gtin_state']==='duplicate')<br>GTIN mehrfach vorhanden @elseif($r['gtin_state']==='missing_or_invalid')<br>GTIN prüfen @endif
                </td><td>{{ implode(', ',$r['missing'])?:'—' }}</td><td>{{ implode(', ',$r['unverified'])?:'—' }}</td>
                <td>@if($r['number']!=='')<a class="hw24-btn" href="{{ \App\Filament\Pages\ArticleLookup::getUrl(['q'=>$r['number']]) }}">Artikel pflegen</a>@endif</td>
            </tr>
        @empty<tr><td colspan="6">Keine passenden offenen Pflichtfelder.</td></tr>@endforelse
        </tbody></table></div>
        <div class="hw24-pagebar"><button type="button" class="hw24-btn" wire:click="move(-1)" @disabled($page<=1)>Zurück</button><button type="button" class="hw24-btn" wire:click="move(1)" @disabled($page*50>=$total)>Weiter</button></div>
    @endif
</div>
</x-filament-panels::page>
