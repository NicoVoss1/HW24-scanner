<div style="overflow-x:auto">
@if(!($offers['ok']??false))
    <p>{{ $offers['message']??'Noch keine Angebote geladen.' }}</p>
@else
    <p><strong>Amazon · {{ $offers['asin'] }}</strong> · {{ $offers['retrieved_at']??'Zeitpunkt nicht belegt' }} · {{ $offers['returned'] }} sichtbare Angebote / {{ $offers['total_offers']??'?' }} gemeldet</p>
    <div style="display:flex;flex-wrap:wrap;gap:12px;margin:12px 0">
        @foreach(['buy_box'=>'Buy Box','lowest_summary'=>'Niedrigster Gesamtpreis (Amazon)','lowest_visible'=>'Niedrigster sichtbarer Gesamtpreis'] as $key=>$label)
            <div style="border:1px solid #8199b0;border-radius:8px;padding:12px;min-width:170px"><small>{{ $label }}</small><div style="font-size:20px;font-weight:700">{{ isset($offers[$key]['amount'])?number_format($offers[$key]['amount'],2,',','.').' '.$offers[$key]['currency']:'Nicht belegt' }}</div></div>
        @endforeach
    </div>
    <table style="width:100%;border-collapse:collapse;min-width:760px;text-align:left">
        <thead><tr>@foreach(['Händler','Buy Box','Artikelpreis','Versand','Gesamtpreis','Versandart','Feedback'] as $h)<th style="background:#dceeff;color:#143d62;padding:8px;border:1px solid #8199b0">{{ $h }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($offers['offers'] as $offer)
            <tr style="{{ $offer['winner']?'background:#e5f5e9;color:#164b2a':'' }}">
                <td style="padding:8px;border:1px solid #8199b0">
                    <div style="display:flex;gap:8px;align-items:center">
                        <span aria-hidden="true" style="border:1px solid #8199b0;border-radius:7px;display:grid;place-items:center;width:34px;height:34px;font-weight:700">{{ mb_strtoupper(mb_substr($offer['seller_name']??'?',0,2)) }}</span>
                        <div><strong>{{ $offer['seller_name']??'Händlername nicht bestätigt' }}</strong><br><small>ID {{ $offer['seller_id'] }}</small><br><a target="_blank" rel="noopener noreferrer" href="https://www.amazon.de/sp?seller={{ rawurlencode($offer['seller_id']) }}" style="text-decoration:underline">Amazon-Händlerprofil</a></div>
                    </div>
                </td>
                <td style="padding:8px;border:1px solid #8199b0">{{ $offer['winner']?'✓ Gewinner':'—' }}</td>
                @foreach(['item','shipping','total'] as $k)<td style="padding:8px;border:1px solid #8199b0">{{ isset($offer[$k]['amount'])?number_format($offer[$k]['amount'],2,',','.').' '.$offer[$k]['currency']:'Nicht belegt' }}</td>@endforeach
                <td style="padding:8px;border:1px solid #8199b0">{{ $offer['fba']?'Versand durch Amazon':'Händlerversand / prüfen' }} @if($offer['prime']===true) · Prime @endif</td>
                <td style="padding:8px;border:1px solid #8199b0">{{ $offer['feedback_percent']!==null?$offer['feedback_percent'].' %':'Nicht belegt' }} @if($offer['feedback_count']!==null) ({{ $offer['feedback_count'] }}) @endif</td>
            </tr>
        @empty<tr><td colspan="7">Keine bestätigten sichtbaren Angebote.</td></tr>@endforelse
        </tbody>
    </table>
    <p>Amazon-Marktplatzhändler sind nicht automatisch unsere Lieferanten. Marktpreise werden nicht in JTL-Verkaufspreise übernommen. Händlerzeichen sind Initialen, keine ungeprüften Logos.</p>
@endif
</div>
