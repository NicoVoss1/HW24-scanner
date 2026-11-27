<div style="width:100%;margin:12px 0">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,150px),1fr));gap:10px">
        @foreach(['total'=>'Gesamtbestand','available'=>'Verfügbar','orders'=>'In Aufträgen','incoming'=>'Im Zulauf'] as $key=>$label)
            <div style="border:1px solid #8199b0;border-radius:8px;padding:10px">
                <small>{{ $label }}</small>
                <div style="font-size:20px;font-weight:700">{{ \App\Services\HubStockReader::display($stockData['item'][$key]??null) }}</div>
                @if(isset($stockData['conflicts'][$key]))<small>Quellenabweichung: Spiegel {{ $stockData['conflicts'][$key]['mirror'] }} / API {{ $stockData['conflicts'][$key]['api'] }}</small>@endif
            </div>
        @endforeach
    </div>
    <p style="font-size:12px;margin:6px 0">JTL-Spiegelstand: {{ $stockData['at']??'Nicht bestätigt' }}. Verfügbar und in Aufträgen sind eigenständige JTL-Originalwerte, keine berechneten Mengen.</p>
    @if(($stockData['item']['total']??null)!==null&&($stockData['item']['available']??null)!==null&&(float)$stockData['item']['available']>(float)$stockData['item']['total'])
        <p role="status" style="border:1px solid #d97706;border-radius:6px;padding:8px">JTL meldet mehr verfügbare Stücke als Gesamtbestand. Quellwerte bleiben unverändert; Bestandsdefinitionen und Synchronisation prüfen.</p>
    @endif
    @if(!($stockData['ok']??false))<p>Aktueller Bestandsstand nicht bestätigt. Kein Rückgriff auf das alte Stammdatenfeld.</p>@endif
    @if($showBins??false)
        @php
            $wms=$stockData['wms']??[];
        @endphp
        <h4 style="font-size:16px;font-weight:700;margin:14px 0 6px">Warenlager und WMS-Lagerplätze</h4>
        @if(($wms['confirmed']??false)===true)
            <p>{{ $wms['count']??0 }} angezeigte Lagerplätze · {{ $wms['positive_bins']??0 }} mit positivem Bestand. Fachmengen nicht zum Gesamtbestand addieren.</p>
            @if($wms['truncated']??false)<p role="alert">Lagerplatzliste nicht vollständig. Nur gelieferte Positionen angezeigt.</p>@endif
            <div style="overflow-x:auto"><table style="width:100%;min-width:780px;border-collapse:collapse;text-align:left">
                <thead><tr>@foreach(['Warenlager','Lagerplatz','Menge','Sperrstatus','Letzte Bewegung','Weitere Angaben'] as $heading)<th style="background:#dceeff;color:#143d62;padding:8px;border:1px solid #8199b0">{{ $heading }}</th>@endforeach</tr></thead>
                <tbody>
                    @forelse($wms['bins']??[] as $bin)
                        <tr>
                            <td style="padding:8px;border:1px solid #8199b0">{{ $bin['warehouse'] }} @if($bin['warehouse_id'])<small>(ID {{ $bin['warehouse_id'] }})</small>@endif</td>
                            <td style="padding:8px;border:1px solid #8199b0"><strong>{{ $bin['bin'] }}</strong> @if($bin['bin_id'])<small>(ID {{ $bin['bin_id'] }})</small>@endif</td>
                            <td style="padding:8px;border:1px solid #8199b0">{{ \App\Services\HubStockReader::display($bin['quantity']) }}</td>
                            <td style="padding:8px;border:1px solid #8199b0">{{ $bin['locked']?'Fach gesperrt':($bin['lock_known']?'Fach nicht gesperrt':'Fachsperre unbekannt') }}<br>{{ $bin['shipping_locked']?'Auslieferung gesperrt':($bin['shipping_lock_known']?'Auslieferung nicht gesperrt':'Ausliefersperre unbekannt') }}</td>
                            <td style="padding:8px;border:1px solid #8199b0">{{ $bin['last_movement']??'Nicht belegt' }}<br><small>{{ $bin['last_source']??'' }}</small></td>
                            <td style="padding:8px;border:1px solid #8199b0">{{ $bin['comment']??'' }} @if($bin['type_id'])<small>Platztyp-ID {{ $bin['type_id'] }}</small>@endif @if($bin['status_id'])<small>Status-ID {{ $bin['status_id'] }}</small>@endif</td>
                        </tr>
                    @empty<tr><td colspan="6">Keine Fachpositionen im bestätigten Quellstand.</td></tr>@endforelse
                </tbody>
            </table></div>
        @else
            <p>WMS-Lagerplatzdaten nicht bestätigt. Fehlende Daten bedeuten nicht, dass keine Ware vorhanden ist.</p>
        @endif
    @endif
</div>
