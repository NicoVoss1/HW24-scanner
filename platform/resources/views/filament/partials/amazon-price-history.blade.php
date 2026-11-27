<div class="hw24-note" style="margin-top:12px">
    <strong>Amazon-Preisarchiv · letzte Beobachtungen (UTC)</strong>
    <p role="status">{{ match($archive['state']??'disabled'){
        'recorded'=>($archive['inserted']??false)?'Neue Preisbeobachtung archiviert.':'Bereits archivierte Beobachtung wiederverwendet.',
        'recorded_history_unavailable'=>'Preis gespeichert, Verlauf momentan nicht vollständig lesbar.',
        'storage_not_ready'=>'Preisarchiv nicht bereit; aktuelle Preise werden weiterhin angezeigt.',
        'archive_unavailable_or_invalid'=>'Preisbeobachtung nicht gespeichert; Archiv oder Antwort prüfen.',
        'not_recorded'=>'Keine neue erfolgreiche Live-Beobachtung gespeichert.',
        default=>'Preisarchiv noch nicht aktiviert.'
    } }}</p>
    @foreach(($archive['history']??[]) as $row)
        <div style="border-top:1px solid #cbd5e1;padding:8px 0">
            <strong>{{ $row['observed_at_utc'] }} UTC · #{{ $row['id'] }}</strong>
            <p>{{ $row['total_offer_count']??'?' }} Angebote gesamt · {{ $row['returned_offer_count'] }} Einzelangebote archiviert.</p>
            @foreach(['buybox'=>'Buy Box','lowest'=>'Niedrigster Preis laut Amazon'] as $key=>$label)
                @forelse(($row[$key]??[]) as $price)
                    <p>{{ $label }} · {{ $price['fulfillment']??'Versandart unbekannt' }} · {{ $price['condition']??'Zustand unbekannt' }}:
                        @if(($price['landed_price']??null)!==null)
                            {{ number_format((float)$price['landed_price']['amount'],2,',','.') }} {{ $price['landed_price']['currency'] }} gesamt
                        @elseif(($price['listing_price']??null)!==null)
                            {{ number_format((float)$price['listing_price']['amount'],2,',','.') }} {{ $price['listing_price']['currency'] }} Artikelpreis; Gesamtpreis unbekannt
                        @else Nicht gemeldet @endif
                    </p>
                @empty <p>{{ $label }}: nicht gemeldet.</p> @endforelse
            @endforeach
        </div>
    @endforeach
    @if(count($archive['history']??[])<2)<p>Ein historischer Vergleich benötigt zwei tatsächlich gespeicherte Beobachtungen.</p>@endif
    <p>Marktpreise sind keine eigenen Verkaufspreise. Vergleiche nur bei gleicher Währung, Versandart und Zustand.</p>
</div>
