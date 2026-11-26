<x-filament-panels::page>
    <div style="background:#dceeff;color:#143d62;padding:16px;border-radius:10px">
        <h2 style="font-size:20px;font-weight:700">JTL-Artikelhistorie · Vorher / Nachher</h2>
        <p>Lesender Vergleich bestätigter Spiegelstände. Kein Rückschluss auf die genaue Änderungszeit, den Bearbeiter oder eine physische Löschung in JTL.</p>
    </div>
    @if(!$available)
        <p>Historien-Datenbank noch nicht verfügbar oder nicht bestätigt. Zuerst privaten Ausgangsstand erfassen.</p>
    @else
        <section>
            <h3 style="font-weight:700">Spiegelstände (UTC)</h3>
            <div style="overflow-x:auto">
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead><tr style="background:#dceeff;color:#143d62"><th>Spiegelstand</th><th>Erfasst</th><th>Vergleich</th><th>Artikel</th><th>Neu</th><th>Geändert</th><th>Nicht mehr im Spiegel</th></tr></thead>
                    <tbody>
                        @foreach($runs as $r)
                        <tr wire:key="history-run-{{ $r['id'] }}" style="border-bottom:1px solid #aac2d5">
                            <td><button type="button" wire:click="$set('run','{{ $r['id'] }}')" style="text-decoration:underline">{{ $r['snapshot_at'] }}</button></td>
                            <td>{{ $r['captured_at'] }}</td><td>{{ $r['kind']==='baseline'?'Ausgangsstand / Quellwechsel':'Vergleich' }}</td>
                            <td>{{ $r['row_count'] }}</td><td>{{ $r['new_count'] }}</td><td>{{ $r['changed_count'] }}</td><td>{{ $r['missing_count'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
        <section>
            <h3 style="font-weight:700">Feldgenaue Veränderungen</h3>
            <div style="display:flex;flex-wrap:wrap;gap:12px;margin:12px 0">
                <label>EAN / Artikelnummer / Name <input type="text" wire:model.live.debounce.500ms="search" maxlength="100" class="rounded-lg border-gray-300 dark:bg-gray-800"></label>
                <label>Änderungsart <select wire:model.live="kind" class="rounded-lg border-gray-300 dark:bg-gray-800">
                    <option value="">Alle</option><option value="new">Neuanlage im Spiegel</option><option value="changed">Feld geändert</option><option value="missing">Nicht mehr im Spiegel</option>
                </select></label>
                <button type="button" wire:click="$set('run','')" style="text-decoration:underline">Alle Spiegelstände</button>
                <button type="button" wire:click="$refresh" style="text-decoration:underline">Aktualisieren</button>
            </div>
            <p>{{ $total }} Ereignisse passend · maximal 200 angezeigt. @if($run!=='') Filter Spiegel #{{ $run }}. @endif</p>
            @forelse($events as $e)
                <div wire:key="article-history-event-{{ $e['id'] }}" style="border:1px solid #b5cfe3;border-radius:8px;padding:12px;margin:8px 0">
                    <strong>{{ $e['article_name']??'Artikel ohne bestätigten Namen' }}</strong>
                    <p>Artikelnummer: {{ $e['article_number']??'Nicht belegt' }} · EAN: {{ $e['ean']??'Nicht belegt' }} · kArtikel {{ $e['article_id'] }}</p>
                    <p style="font-size:12px">Vorher: {{ $e['previous_at']??'Nicht belegt' }} UTC · Nachher: {{ $e['snapshot_at'] }} UTC · erfasst: {{ $e['captured_at'] }} UTC</p>
                    @if($e['kind']==='changed')
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:8px;overflow-wrap:anywhere">
                            <div><strong>{{ $labels[$e['field_key']]??$e['field_key'] }}</strong></div>
                            <div><small>Alter Spiegelwert</small><p>{{ $e['old_value']===null?'Nicht belegt':($e['old_value']===''?'Leer':$e['old_value']) }}</p></div>
                            <div><small>Neuer Spiegelwert</small><p>{{ $e['new_value']===null?'Nicht belegt':($e['new_value']===''?'Leer':$e['new_value']) }}</p></div>
                        </div>
                    @else
                        <p>{{ $e['kind']==='new'?'Neu im Vergleich zum vorigen Spiegelstand':'Im aktuellen vollständigen Spiegel nicht mehr enthalten; keine bestätigte JTL-Löschung' }}</p>
                    @endif
                </div>
            @empty
                <p>Keine passenden beobachteten Veränderungen. Ein einzelner Ausgangsstand enthält noch keine Änderungsereignisse.</p>
            @endforelse
        </section>
    @endif
</x-filament-panels::page>
