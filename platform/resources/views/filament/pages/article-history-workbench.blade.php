<x-filament-panels::page>
    <div class="hw24-grid">
    @include('filament.pages.reference-grid-styles')
    <div style="background:#dceeff;color:#143d62;padding:16px;border-radius:10px">
        <h2 style="font-size:20px;font-weight:700">JTL-Artikelhistorie · Vorher / Nachher</h2>
        <p>Lesender Vergleich bestätigter Spiegelstände. Kein Rückschluss auf die genaue Änderungszeit, den Bearbeiter oder eine physische Löschung in JTL.</p>
    </div>
    @if(!$available)
        <p>Historien-Datenbank noch nicht verfügbar oder nicht bestätigt. Zuerst privaten Ausgangsstand erfassen.</p>
    @else
        <section>
            <h3 style="font-weight:700">Spiegelstände (UTC)</h3>
            <div class="hw24-scroll">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead><tr ><th>Spiegelstand</th><th>Erfasst</th><th>Vergleich</th><th>Artikel</th><th>Neu</th><th>Geändert</th><th>Nicht mehr im Spiegel</th></tr></thead>
                    <tbody>
                        @foreach($runs as $r)
                        <tr wire:key="history-run-{{ $r['id'] }}" @if($run===(string)$r['id']) class="hw24-selected" @endif>
                            <td><button type="button" class="hw24-btn" wire:click="selectRun({{ $r['id'] }})" >{{ $r['snapshot_at'] }}</button></td>
                            <td>{{ $r['captured_at'] }}</td><td>{{ $r['kind']==='baseline'?'Ausgangsstand / Quellwechsel':'Vergleich' }}</td>
                            <td>{{ $r['row_count'] }} <button type="button" class="hw24-btn" wire:click="selectRun({{ $r['id'] }})" >Änderungen anzeigen</button></td><td>{{ $r['new_count'] }}</td><td>{{ $r['changed_count'] }}</td><td>{{ $r['missing_count'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <section>
            <h3 style="background:#dceeff;color:#143d62;padding:12px;font-weight:700">Änderungen @if($run!=='') · Spiegel #{{ $run }} @else · Alle Stände @endif</h3>
            <div class="hw24-toolbar">
                <label>EAN / Artikelnummer / Name <input type="search" wire:model.live.debounce.500ms="search" maxlength="100" placeholder="EAN, Artikelnummer oder Name …"></label>
                <label>Änderungsart <select wire:model.live="kind">
                    <option value="">Alle</option><option value="new">Neuanlage im Spiegel</option><option value="changed">Feld geändert</option><option value="missing">Nicht mehr im Spiegel</option>
                </select></label>
                <label>Feld <select wire:model.live="field"><option value="">Alle Felder</option>@foreach($labels as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
                <button type="button" class="hw24-btn" wire:click="$set('run','')" >Alle Spiegelstände</button>
                <button type="button" class="hw24-btn" wire:click="$refresh" >Aktualisieren</button>
            </div>
            <p>{{ $total }} Ereignisse · Seite {{ $page }} von {{ max(1,ceil($total/100)) }}</p>
            <div class="hw24-scroll">
                <table style="width:100%;border-collapse:collapse;font-size:12px;text-align:left">
                    <thead ><tr>
                        @foreach(['Spiegelstand (UTC)','Artikelnummer','EAN','Artikelname','Änderungsart','Feld','Vorher','Nachher','Vorheriger Stand (UTC)','Erfasst (UTC)'] as $label)<th style="padding:8px">{{ $label }}</th>@endforeach
                    </tr></thead>
                    <tbody>
                    @forelse($events as $e)
                        <tr wire:key="article-history-event-{{ $e['id'] }}" style="border-bottom:1px solid #b5cfe3;vertical-align:top">
                            <td style="padding:6px;white-space:nowrap">{{ $e['snapshot_at'] }}</td>
                            <td style="padding:6px">{{ $e['article_number'] }}</td><td style="padding:6px">{{ $e['ean'] }}</td>
                            <td style="padding:6px;min-width:220px">{{ $e['article_name'] }}<small> · ID {{ $e['article_id'] }}</small></td>
                            <td style="padding:6px">{{ match($e['kind']){'new'=>'Neu im Spiegel','changed'=>'Geändert','missing'=>'Nicht mehr im Spiegel',default=>'Unbekannt'} }}</td>
                            <td style="padding:6px">{{ $labels[$e['field_key']]??'Artikel' }}</td>
                            @foreach(['old_value','new_value'] as $key)
                            <td style="padding:6px;min-width:140px;max-width:380px;overflow-wrap:anywhere">@if($e['kind']==='changed'){{ $e[$key]===null?'Nicht belegt':($e[$key]===''?'Leer':$e[$key]) }}@else — @endif</td>
                            @endforeach
                            <td style="padding:6px;white-space:nowrap">{{ $e['previous_at'] }}</td><td style="padding:6px;white-space:nowrap">{{ $e['captured_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" style="padding:16px">Keine passenden Änderungen in diesem Spiegelstand. Der Ausgangsstand selbst erzeugt keine Änderungsereignisse.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div style="display:flex;gap:12px;margin:12px 0">
                <button type="button" class="hw24-btn" wire:click="movePage(-1)" @disabled($page<=1)>Zurück</button>
                <button type="button" class="hw24-btn" wire:click="movePage(1)" @disabled($page*100>=$total)>Weiter</button>
            </div>
        </section>
    @endif
    </div>
</x-filament-panels::page>
