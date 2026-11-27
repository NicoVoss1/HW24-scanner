<x-filament-panels::page>
    <div class="hw24-grid" x-data>
        @include('filament.pages.reference-grid-styles')
        <div style="background:#dceeff;color:#143d62;padding:16px;border-radius:8px">
            <h2 style="font-size:20px;font-weight:700">{{ $ameise?'JTL-Ameise-Stammdaten':'Historische Artikeldaten' }}</h2>
            <p>Originale Spaltenreihenfolge · {{ $ameise?'Unveränderlicher JTL-Export':'Historische Artikelpflege mit Änderungsprotokoll' }} · keine direkte Änderung in JTL.</p>
        </div>
        @if($editMessage!=='')<p role="status" style="padding:12px;border:1px solid #829bb2;border-radius:8px">{{ $editMessage }}</p>@endif
        @if(!$ameise && $editRow>0)
            <section id="historical-row-editor" style="padding:16px;border:1px solid #829bb2;border-radius:8px">
                <h3 style="font-size:18px;font-weight:700">Historische Artikelpflege · Zeile {{ $editRow }} · Revision {{ $editRevision }}</h3>
                <p>Ausschließlich vorhandene Auswahlwerte. Standardfelder verwenden die vorhandenen Listenwerte; produktbezogene Felder nur Werte desselben Artikels. GTIN und Formelzellen bleiben geschützt. Fehlt ein geeigneter Wert, ist Recherche erforderlich.</p>
                <form wire:submit="saveEdit">
                    @include('filament.partials.hub-field-decisions',[
                        'decisionOptions'=>$editOptions,'decisionModel'=>'editValues','decisionKey'=>'historical-'.$editSheet.'-'.$editRow,
                        'decisionCurrent'=>$editCurrent,'decisionLabels'=>collect($editColumns)->mapWithKeys(fn($c)=>[(string)$c['position']=>\App\Filament\Pages\ReferenceTableWorkbench::excelColumn((int)$c['position']).' · '.($c['name']?:'(ohne Überschrift)')])->all(),
                    ])
                    <p>Felder ohne weitere Auswahl bleiben unverändert. Neue Produktwerte müssen zuerst recherchiert werden.</p>
                    <div class="hw24-pagebar"><button class="hw24-btn" type="submit" wire:loading.attr="disabled">Änderungen speichern</button><button class="hw24-btn" type="button" wire:click="cancelEdit">Ohne Speichern schließen</button></div>
                </form>
                <details style="margin-top:16px"><summary>Änderungshistorie dieser Zeile (bis zu 100 letzte Feldänderungen)</summary>
                    <div class="hw24-scroll"><table style="width:100%"><thead><tr>@foreach(['Zeitpunkt UTC','Mitarbeiter-ID','Revision','Feld','Vorher','Nachher'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
                    @forelse($editAudit as $event)<tr><td>{{ $event['at_utc'] }}</td><td>{{ $event['actor'] }}</td><td>{{ $event['revision'] }}</td><td>{{ $event['name']?:$event['position'] }}</td><td style="white-space:pre-wrap">{{ $event['old_value'] }}</td><td style="white-space:pre-wrap">{{ $event['new_value'] }}</td></tr>
                    @empty<tr><td colspan="6">Noch keine manuellen Änderungen.</td></tr>@endforelse
                    </tbody></table></div>
                </details>
            </section>
        @endif
        @if(!$available)
            <p>Privates Referenzarchiv noch nicht verfügbar oder nicht bestätigt.</p>
        @else
            <div class="hw24-toolbar">
                <label>Quelle / Tabelle <select wire:model.live="sheet"><option value="">Neuester Stand</option>@foreach($sources as $s)<option value="{{ $s['id'] }}">{{ $s['name'] }}</option>@endforeach</select></label>
                <label style="flex:2 1 270px">Alle Spalten durchsuchen <input wire:model.live.debounce.500ms="search" type="search" maxlength="100" placeholder="EAN, ASIN, Artikelname, Hersteller …"></label>
                <label>Spaltenfilter <select wire:model.live="filterColumn"><option value="">Alle Spalten</option>@foreach($columns as $c)<option value="{{ $c['position'] }}">{{ \App\Filament\Pages\ReferenceTableWorkbench::excelColumn((int)$c['position']) }} · {{ $c['name']?:'(ohne Überschrift)' }}</option>@endforeach</select></label>
                <label>Filterwert <input wire:model.live.debounce.500ms="filterValue" type="text" maxlength="200" placeholder="Wert enthält …"></label>
                @unless($ameise)<label>Bearbeitungsstatus <select wire:model.live="status"><option value="">Alle</option><option>LP geb.</option><option>WE geb.</option></select></label>@endunless
                <button class="hw24-btn" type="button" wire:click="clearFilters">Filter zurücksetzen</button>
                <button class="hw24-btn" type="button" wire:click="$refresh">Aktualisieren</button>
            </div>
            <div style="background:#dceeff;color:#143d62;padding:12px;font-weight:700">{{ $selected['name'] }} · {{ number_format($total,0,',','.') }} passende Zeilen · {{ $columnTotal }} von {{ count($allColumns) }} Spalten sichtbar</div>
            <div class="hw24-toolbar">
                <label class="hw24-check"><input type="checkbox" wire:model.live="showEmpty"> Vollständig leere Spalten einblenden</label>
                <label style="flex:0 1 135px">Zeilen pro Seite<select wire:model.live="rowsPerPage"><option value="10">10</option><option value="25">25</option><option value="50">50</option></select></label>
                <label>Direkt zu Spalte <select x-on:change="const el=$refs.sheet.querySelector('[data-col=&quot;'+$event.target.value+'&quot;]'); if(el){$refs.sheet.scrollLeft+=$event.target.value?(el.getBoundingClientRect().left-$refs.sheet.getBoundingClientRect().left-110):0}"><option value="">Spalte auswählen …</option>@foreach($columns as $c)<option value="{{ $c['position'] }}">{{ \App\Filament\Pages\ReferenceTableWorkbench::excelColumn((int)$c['position']) }} · {{ $c['name']?:'(leer)' }}</option>@endforeach</select></label>
                <button class="hw24-btn" type="button" x-on:click="$refs.sheet.scrollLeft=0">Tabellenanfang</button>
            </div>
            @unless($ameise)<p><span style="background:#00B050;color:#10291b;padding:4px">LP geb.</span> <span style="background:#FFD84D;color:#493800;padding:4px">WE geb.</span> · vereinfachte Zeilenfärbung; Excel-Formeln werden nicht neu berechnet.</p>@endunless
            <div class="hw24-pagebar">
                <button class="hw24-btn" type="button" wire:click="step('rowPage',-1)" @disabled($rp<=1)>Zurück</button><span>Seite {{ $rp }} / {{ max(1,ceil($total/$perPage)) }}</span><button class="hw24-btn" type="button" wire:click="step('rowPage',1)" @disabled($rp*$perPage>=$total)>Weiter</button>
                <span>Alle sichtbaren Spalten nebeneinander · horizontal scrollen · Überschrift anklicken zum Sortieren (Text)</span>
                <span wire:loading>Wird geladen …</span>
            </div>
            <div class="hw24-scroll" x-ref="sheet" style="position:relative" tabindex="0" aria-label="Vollständige Artikeltabelle, horizontal scrollbar">
                <table class="hw24-sheet">
                    <thead><tr><th>Zeile</th>
                    @foreach($columns as $c)<th data-col="{{ $c['position'] }}" aria-sort="{{ $sortColumn===(string)$c['position']?($sortDescending?'descending':'ascending'):'none' }}"><button type="button" wire:click="sortByColumn('{{ $c['position'] }}')"><small class="hw24-colcode">{{ \App\Filament\Pages\ReferenceTableWorkbench::excelColumn((int)$c['position']) }} · {{ $c['position'] }}</small>{{ $c['name']?:'(leer)' }} @if($sortColumn===(string)$c['position']){{ $sortDescending?'▼':'▲' }}@endif</button></th>@endforeach</tr></thead>
                    <tbody>
                        @forelse($rows as $r)
                        <tr wire:key="reference-row-{{ $selected['id'] }}-{{ $r['row_number'] }}" class="{{ ($r['status']??'')==='LP geb.'?'hw24-status-lp':(($r['status']??'')==='WE geb.'?'hw24-status-we':'') }}">
                            <td>{{ $r['row_number'] }}
                                @php($lookupPos=$ameise?collect($allColumns)->firstWhere('name','Artikelnummer')['position']??null:null)
                                @php($lookupValue=$ameise?($r['values'][(string)$lookupPos]??''):($r['values']['5']??$r['values']['4']??''))
                                @if($ameise && $lookupValue!=='')
                                    <a style="display:block;text-decoration:underline;margin:6px 0" href="{{ \App\Filament\Pages\ArticleLookup::getUrl(['q'=>$lookupValue]) }}">Artikelpflege öffnen</a>
                                @endif
                                @unless($ameise)<button type="button" class="hw24-btn" wire:click="openEdit({{ $selected['id'] }},{{ $r['row_number'] }})" wire:loading.attr="disabled" @disabled($editRow>0)>Pflegen</button>@endunless</td>
                            @foreach($columns as $c)
                            @php($value=$r['values'][(string)$c['position']]??'')
                            <td>@if(mb_strlen($value)>160)<details><summary>{{ mb_substr($value,0,160) }} …</summary><div style="white-space:pre-wrap">{{ $value }}</div></details>@else{{ $value }}@endif</td>
                            @endforeach
                        </tr>
                        @empty<tr><td colspan="{{ count($columns)+1 }}" style="padding:16px">Keine passenden Zeilen.</td></tr>@endforelse
                    </tbody>
                </table>
            </div>
            <div class="hw24-pagebar">
                <button class="hw24-btn" type="button" wire:click="step('rowPage',-1)" @disabled($rp<=1)>Zurück</button><span>Seite {{ $rp }} / {{ max(1,ceil($total/$perPage)) }}</span><button class="hw24-btn" type="button" wire:click="step('rowPage',1)" @disabled($rp*$perPage>=$total)>Weiter</button>
            </div>
        @endif
    </div>
</x-filament-panels::page>
