<x-filament-panels::page>
    <div style="background:#dceeff;color:#143d62;padding:16px;border-radius:10px">
        <h2 style="font-size:20px;font-weight:700">{{ $ameise?'JTL-Ameise-Stammdaten':'Historische Artikeldaten' }}</h2>
        <p>Originale Spaltenreihenfolge · datierte Referenz · lesende Vorschau, keine Änderung in JTL.</p>
    </div>
    @if(!$available)
        <p>Privates Referenzarchiv noch nicht verfügbar oder nicht bestätigt. Originaldatei zunächst privat importieren.</p>
    @else
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:end">
            <label>Quelle / Tabelle <select wire:model.live="sheet" class="rounded-lg border-gray-300 dark:bg-gray-800"><option value="">Neuester Stand</option>@foreach($sources as $s)<option value="{{ $s['id'] }}">{{ $s['name'] }}</option>@endforeach</select></label>
            <label>Alle Spalten durchsuchen <input wire:model.live.debounce.500ms="search" type="text" maxlength="100" class="rounded-lg border-gray-300 dark:bg-gray-800"></label>
            <label>Spaltenfilter <select wire:model.live="filterColumn" class="rounded-lg border-gray-300 dark:bg-gray-800"><option value="">Alle</option>@foreach($allColumns as $c)<option value="{{ $c['position'] }}">{{ \App\Filament\Pages\ReferenceTableWorkbench::excelColumn((int)$c['position']) }} · {{ $c['name']?:'(ohne Überschrift)' }}</option>@endforeach</select></label>
            <label>Filterwert <input wire:model.live.debounce.500ms="filterValue" type="text" maxlength="200" class="rounded-lg border-gray-300 dark:bg-gray-800"></label>
            @if($ameise)<label><input type="checkbox" wire:model.live="showEmpty"> Auch leere Spalten</label>
            @else<label>Bearbeitungsstatus <select wire:model.live="status" class="rounded-lg border-gray-300 dark:bg-gray-800"><option value="">Alle</option><option>LP geb.</option><option>WE geb.</option></select></label>@endif
        </div>
        <div style="background:#dceeff;color:#143d62;padding:12px;font-weight:700">{{ $selected['name'] }} · {{ $total }} passende Zeilen · {{ $columnTotal }} sichtbare Spalten</div>
        @unless($ameise)<p><span style="background:#00B050;color:#10291b;padding:4px">LP geb.</span> <span style="background:#FFD84D;color:#493800;padding:4px">WE geb.</span> · vereinfachte Zeilenfärbung; Excel-Formeln werden nicht neu berechnet.</p>@endunless
        <div style="display:flex;gap:12px;align-items:center">
            <button type="button" wire:click="step('columnPage',-1)" @disabled($cp<=1)>← Spalten</button>
            <span>Spaltengruppe {{ $cp }} / {{ max(1,ceil($columnTotal/12)) }}</span>
            <button type="button" wire:click="step('columnPage',1)" @disabled($cp*12>=$columnTotal)>Spalten →</button>
        </div>
        <div style="overflow:auto;max-height:70vh">
            <table style="border-collapse:collapse;font-size:12px;text-align:left;width:100%">
                <thead style="position:sticky;top:0;background:#dceeff;color:#143d62">
                    <tr><th style="padding:8px;position:sticky;left:0;background:#dceeff">Zeile</th>
                    @foreach($columns as $c)<th style="min-width:135px;padding:8px;border:1px solid #b5cfe3"><small>{{ \App\Filament\Pages\ReferenceTableWorkbench::excelColumn((int)$c['position']) }}</small><br>{{ $c['name']?:'(leer)' }}</th>@endforeach</tr>
                </thead>
                <tbody>
                    @forelse($rows as $r)
                    <tr wire:key="reference-row-{{ $selected['id'] }}-{{ $r['row_number'] }}" @if(($r['status']??'')==='LP geb.') style="background:#00B050;color:#10291b" @elseif(($r['status']??'')==='WE geb.') style="background:#FFD84D;color:#493800" @endif>
                        <td style="position:sticky;left:0;background:#dceeff;color:#143d62;padding:6px">{{ $r['row_number'] }}</td>
                        @foreach($columns as $c)<td style="padding:6px;border:1px solid #b5cfe3;max-width:380px;overflow-wrap:anywhere;vertical-align:top">{{ $r['values'][(string)$c['position']]??'' }}</td>@endforeach
                    </tr>
                    @empty<tr><td colspan="{{ count($columns)+1 }}" style="padding:16px">Keine passenden Zeilen.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        <div style="display:flex;gap:12px;align-items:center">
            <button type="button" wire:click="step('rowPage',-1)" @disabled($rp<=1)>Zurück</button><span>Seite {{ $rp }} / {{ max(1,ceil($total/50)) }}</span><button type="button" wire:click="step('rowPage',1)" @disabled($rp*50>=$total)>Weiter</button>
        </div>
    @endif
</x-filament-panels::page>
