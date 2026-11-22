<x-filament-panels::page>
<div class="hw24-warehouse">
<style>
.hw24-warehouse{--edge:#dbe5ee;--bg:#fff;--soft:#f5f8fc;--ink:#1b3045;--muted:#596c82;color:var(--ink);font-size:13px}
.hw24-warehouse *{box-sizing:border-box}
.hw24-warehouse .hero{background:linear-gradient(115deg,#152e46,#1d5777);color:white;border-radius:16px;padding:23px 26px}
.hw24-warehouse .hero h2{font-size:23px;line-height:1.3;font-weight:800;margin:6px 0}
.hw24-warehouse .hero p{font-size:13px;color:#dbeaf7;line-height:1.7}
.hw24-warehouse .kicker{font-size:11px;letter-spacing:.1em;font-weight:800;color:#b9d9f2}
.hw24-warehouse .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px;margin-top:16px}
.hw24-warehouse .panel{border:1px solid var(--edge);border-radius:14px;overflow:hidden;background:var(--bg);min-width:0}
.hw24-warehouse .full{grid-column:1/-1}
.hw24-warehouse .panel-head{background:var(--soft);padding:14px 18px;border-bottom:1px solid var(--edge);font-size:15px;font-weight:800}
.hw24-warehouse .panel-body{padding:18px}
.hw24-warehouse .small{font-size:12px;line-height:1.6;color:var(--muted);margin:5px 0 12px}
.hw24-warehouse .searchline{display:flex;gap:10px;flex-wrap:wrap;align-items:end}
.hw24-warehouse .field{display:grid;gap:5px;flex:1 1 300px;font-size:12px;font-weight:750}
.hw24-warehouse .input{border:1px solid #b5cad9;background:white;color:#172b42;padding:10px 12px;border-radius:9px;min-height:42px;width:100%}
.hw24-warehouse .btn{display:inline-flex;align-items:center;justify-content:center;min-height:40px;padding:9px 13px;border:0;background:#174b74;color:#fff;border-radius:9px;font-size:12px;font-weight:800;cursor:pointer}
.hw24-warehouse .btn.sec{border:1px solid #c0d3e5;background:#edf6fd;color:#174b74}
.hw24-warehouse .btn:disabled{opacity:.5;cursor:not-allowed}
.hw24-warehouse .stack{display:grid;gap:9px}
.hw24-warehouse .price-row{border:1px solid var(--edge);border-radius:10px;padding:12px;display:grid;grid-template-columns:minmax(110px,1fr) minmax(130px,1fr);gap:10px}
.hw24-warehouse .price-row h4{font-size:13px;font-weight:800;margin:0 0 5px}
.hw24-warehouse .value{font-size:15px;font-weight:850;overflow-wrap:anywhere}
.hw24-warehouse .meta{font-size:11px;color:var(--muted);line-height:1.5}
.hw24-warehouse .tag{display:inline-flex;border-radius:999px;background:#e6f5ed;color:#0c6740;font-size:11px;font-weight:750;padding:4px 9px;margin-top:5px}
.hw24-warehouse .tag.warn{background:#fff0dc;color:#8e4c0b}
.hw24-warehouse .note{background:#fff9ed;border:1px solid #edd6ab;color:#7a4b18;font-size:12px;line-height:1.7;padding:12px 14px;border-radius:10px;margin-top:13px}
.hw24-warehouse .note.ok{background:#effbf4;border-color:#b5dfc9;color:#155a38}
.hw24-warehouse .list{width:100%;border-collapse:collapse;min-width:690px;text-align:left;font-size:12px}
.hw24-warehouse .list th{padding:11px;background:var(--soft);color:var(--muted);white-space:nowrap}
.hw24-warehouse .list td{padding:12px;border-top:1px solid var(--edge);vertical-align:top}
.hw24-warehouse .overflow{overflow:auto;max-height:460px;border:1px solid var(--edge);border-radius:10px}
.hw24-warehouse .actions{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:12px 0}
.hw24-warehouse a{color:#145b93;text-decoration:underline}
.hw24-warehouse .divider{border-top:1px solid var(--edge);margin-top:14px;padding-top:14px}
.hw24-warehouse :is(button,a,input):focus-visible{outline:3px solid #60a5fa;outline-offset:2px}
@media(max-width:830px){.hw24-warehouse .grid{grid-template-columns:minmax(0,1fr)}.hw24-warehouse .hero{padding:19px}.hw24-warehouse .hero h2{font-size:20px}}
@media(max-width:510px){.hw24-warehouse .price-row{grid-template-columns:1fr}}
html.dark .hw24-warehouse{--edge:#334155;--bg:#182638;--soft:#233249;--ink:#f1f5f9;--muted:#c2ccd8}
</style>
<header class="hero">
 <div class="kicker">HAIRWORLD24 · PRODUKTDATEN · LAGER & VERTRIEBSKANÄLE</div>
 <h2>WMS und kanalübergreifender Preisvergleich</h2>
 <p>Ein Artikel, getrennte Quellen: JTL-Wawi, WMS-Bewegungshistorie, Amazon-Katalog und Preisbeobachtungen. Keine erfundenen Brutto-Verkaufspreise, keine automatische WMS-Buchung.</p>
</header>
<div class="grid">
<section class="panel full">
 <div class="panel-head">01 · Artikel eindeutig identifizieren</div>
 <div class="panel-body">
  <form wire:submit="findArticle" class="searchline">
   <label class="field" for="hw24-wms-ean">EAN, HAN oder JTL-Artikelnummer
    <input id="hw24-wms-ean" class="input" maxlength="64" type="search" wire:model="identifier" autocomplete="off" placeholder="3474630337398">
   </label>
   <button class="btn" type="submit" wire:loading.attr="disabled" wire:target="findArticle">JTL-Artikel laden</button>
  </form>
  @if($status!==[])
   <p role="status" class="note {{ ($status['ok']??false) ? 'ok' : '' }}">{{ $status['message'] }} @if(($status['http']??null)!==null) · HTTP {{ $status['http'] }} @endif</p>
  @endif
  @if($product!==[])
   <div class="price-row" style="margin-top:14px">
    <div>
     <h4>{{ $product['name'] ?: 'Artikel ohne Bezeichnung' }}</h4>
     <div class="meta">EAN {{ $product['ean'] ?: 'offen' }} · JTL-Artikel-ID {{ $product['id'] }} · Artikelnummer {{ $product['sku'] ?: 'offen' }}</div>
     <div class="meta">Hersteller {{ $product['brand'] ?: 'offen' }} · Synchronisation {{ $product['sync'] ?: 'unbekannt' }} UTC</div>
    </div>
    <div>
     <h4>JTL-Stammbestand</h4>
     <div class="value">{{ $product['stock']!==null ? number_format((float)$product['stock'],0,',','.') : 'Nicht bestätigt' }}</div>
     <div class="meta">Gespiegelter Lagerbestand, keine belegte frei verfügbare Menge und kein Lagerplatzbestand.</div>
    </div>
   </div>
  @endif
 </div>
</section>

<section class="panel" aria-labelledby="wms-prices">
 <div class="panel-head" id="wms-prices">02 · Preisquellen im Vergleich</div>
 <div class="panel-body">
  <p class="small">Vergleichswerte bleiben nach Kanal, Kontozuordnung, Preisart und Datenherkunft getrennt. Ein JTL-Plattformfeld ist kein verifiziert aktives Händlerangebot.</p>
  @if($product!==[])
   <div class="stack">
    <div class="price-row">
     <div><h4>JTL-Wawi · EK netto</h4><span class="tag">JTL-Quellfeld</span></div>
     <div><div class="value">{{ $product['net_purchase']!==null ? number_format((float)$product['net_purchase'],2,',','.') : 'Nicht belegt' }}</div><div class="meta">Währung {{ $product['currency'] ?: 'nicht bestätigt' }} · kein Verkaufspreis</div></div>
    </div>
    <div class="price-row">
     <div><h4>JTL-Wawi · VK netto</h4><span class="tag">JTL-Quellfeld</span></div>
     <div><div class="value">{{ $product['net_sell']!==null ? number_format((float)$product['net_sell'],2,',','.') : 'Nicht belegt' }}</div><div class="meta">Währung {{ $product['currency'] ?: 'nicht bestätigt' }} · Stammdatenpreis</div></div>
    </div>
    <div class="price-row">
     <div><h4>JTL-Wawi · VK brutto</h4><span class="tag {{ $product['gross_sell']===null ? 'warn' : '' }}">{{ $product['gross_sell']!==null ? 'Quelle bestätigt' : 'Nicht bestätigt' }}</span></div>
     <div><div class="value">{{ $product['gross_sell']!==null ? number_format((float)$product['gross_sell'],2,',','.') : 'Offen' }}</div><div class="meta">{{ $product['gross_source'] ?: 'Keine belegte Brutto-/Steuerquelle, keine automatische Berechnung' }}</div></div>
    </div>
    <div class="price-row">
     <div><h4>Amazon · JTL-Referenzpreis</h4><span class="tag warn">Kein aktives Angebot</span></div>
     <div><div class="value">{{ $product['amazon_jtl']!==null ? number_format((float)$product['amazon_jtl'],2,',','.') : 'Nicht belegt' }}</div><div class="meta">JTL-Spiegelfeld amazon_vk · Preisart und Währung nicht bestätigt</div></div>
    </div>
    <div class="price-row">
     <div><h4>eBay · JTL-Referenzpreis</h4><span class="tag warn">Kein aktives Angebot</span></div>
     <div><div class="value">{{ $product['ebay_jtl']!==null ? number_format((float)$product['ebay_jtl'],2,',','.') : 'Nicht belegt' }}</div><div class="meta">JTL-Spiegelfeld ebay_preis · Preisart und Währung nicht bestätigt</div></div>
    </div>
    <div class="price-row">
     <div><h4>Shopify · eigener aktiver Preis</h4><span class="tag warn">Kontoquelle fehlt</span></div>
     <div><div class="value">Nicht verifiziert</div><div class="meta">Markets, Währung, Status und SKU/EAN-Zuordnung müssen belegt sein</div></div>
    </div>
    <div class="price-row">
     <div><h4>Amazon / eBay · eigene aktive Angebote</h4><span class="tag warn">Kontenquelle fehlt</span></div>
     <div><div class="value">Nicht verifiziert</div><div class="meta">Eine Amazon-Katalogauskunft oder ein JTL-Referenzpreis bestätigt kein Seller-Angebot</div></div>
    </div>
   </div>
  @else
   <p class="note">Zuerst einen JTL-Artikel über die Suche laden.</p>
  @endif
  <div class="divider">
   <h4 style="font-weight:800;font-size:13px">Eigene aktive Angebote aus verifizierten Kontenquellen</h4>
   <p class="small">Es werden nur höchstens 24 Stunden alte, ausdrücklich als aktiv und geprüft gekennzeichnete Shopify-, Amazon- oder eBay-Beobachtungen aus der privaten Preisquellentabelle gelesen. Kontoname und tatsächliche Quelle bleiben sichtbar.</p>
   <button class="btn sec" type="button" wire:click="loadVerifiedOwnOffers" wire:loading.attr="disabled" wire:target="loadVerifiedOwnOffers" @disabled($product===[])>Eigene Kanalpreise überprüfen</button>
   @if($ownOffersStatus!==[])
    <p class="note {{ ($ownOffersStatus['ok']??false) ? 'ok' : '' }}" role="status">{{ $ownOffersStatus['message'] }}</p>
    @foreach($ownOffers as $offer)
     <div class="price-row" style="margin-top:9px" wire:key="offer-{{ $loop->index }}">
      <div><h4>{{ strtoupper($offer['channel']) }} · {{ $offer['type'] }}</h4><span class="tag">Aktive Quelle geprüft</span><div class="meta">Konto {{ $offer['account'] }} · Markt {{ $offer['marketplace'] ?: 'offen' }}</div></div>
      <div><div class="value">{{ number_format((float)$offer['amount'],2,',','.') }} {{ $offer['currency'] }}</div><div class="meta">Stand {{ $offer['date'] }} UTC · {{ $offer['source'] }} @if($offer['offer_id']) · Angebots-ID {{ $offer['offer_id'] }} @endif</div></div>
     </div>
    @endforeach
   @endif
  </div>
  <div class="divider">
   <h4 style="font-weight:800;font-size:13px">Amazon-Wettbewerbspreise separat prüfen</h4>
   <p class="small">Der Katalog-GET kann den HW24-Cache nutzen oder eine echte Amazon-SP-API-Recherche starten. Ein Preisabruf folgt nur nach Auswahl eines im Ergebnis zurückgegebenen ASIN-Kandidaten.</p>
   <button class="btn sec" type="button" wire:click="loadAmazonCandidates" wire:loading.attr="disabled" wire:target="loadAmazonCandidates" @disabled($product===[])>Amazon-Katalogkandidaten abrufen</button>
   @if($amazonCatalog!==[])
    <p class="note {{ ($amazonCatalog['ok']??false) ? 'ok' : '' }}">Katalog-HTTP {{ $amazonCatalog['http']??'—' }} · Quelle {{ $amazonCatalog['source'] }} · {{ count($amazonCatalog['items']??[]) }} angezeigte Kandidaten. {{ $amazonCatalog['message'] }}</p>
    @foreach(($amazonCatalog['items']??[]) as $item)
     <div class="actions" wire:key="market-candidate-{{ $item['asin'] }}">
      <span class="small">{{ $item['asin'] }} · {{ $item['title'] ?: 'Titel unbekannt' }}</span>
      <button type="button" class="btn sec" wire:click="loadAmazonMarketPrice('{{ $item['asin'] }}')" wire:loading.attr="disabled" wire:target="loadAmazonMarketPrice">Angebote prüfen</button>
     </div>
    @endforeach
   @endif
   @if($amazonMarket!==[])
    <p class="note {{ ($amazonMarket['ok']??false) ? 'ok' : '' }}">HTTP {{ $amazonMarket['http']??'—' }} · {{ $amazonMarket['offers']??'?' }} Angebote · {{ $amazonMarket['message'] }}</p>
    @foreach(($amazonMarket['prices']??[]) as $price)
     <div class="price-row" style="margin-top:9px">
      <div><h4>{{ $price['kind'] }}</h4><span class="meta">ASIN {{ $amazonMarket['asin'] }}</span></div>
      <div><div class="value">{{ number_format((float)$price['amount'],2,',','.') }} {{ $price['currency'] }}</div><div class="meta">{{ $price['fulfillment'] ?: 'Fulfillment nicht belegt' }} · Amazon-Angebotsauskunft</div></div>
     </div>
    @endforeach
   @endif
  </div>
 </div>
</section>

<section class="panel" aria-labelledby="wms-history">
 <div class="panel-head" id="wms-history">03 · JTL-WMS-Lagerbewegungen</div>
 <div class="panel-body">
  <p class="small">Die bestehende Historien-Schnittstelle zeigt Bewegung, Zeitpunkt, Menge und Lagerplätze zum letzten abgeschlossenen Snapshot. Sie liefert nicht automatisch aktuelle Fachbestände, Verfügbarkeit oder Reservierungen.</p>
  <div class="actions">
   <button type="button" class="btn" wire:click="loadWmsHistory" wire:loading.attr="disabled" wire:target="loadWmsHistory" @disabled($product===[])>WMS-Bewegungshistorie laden</button>
   @if(($wms['ok']??false)===true && ($wms['has_more']??false)===true)
    <button type="button" class="btn sec" wire:click="loadMoreWmsHistory" wire:loading.attr="disabled" wire:target="loadMoreWmsHistory">Weitere 50 Buchungen</button>
   @endif
  </div>
  @if($wms!==[])
   <p role="status" class="note {{ ($wms['ok']??false) ? 'ok' : '' }}">{{ $wms['message'] }}
    @if(($wms['ok']??false)===true) · {{ count($movements) }} / {{ $wms['total'] }} Bewegungen · Snapshot {{ $wms['snapshot_at'] }} UTC
    @else · HTTP {{ $wms['http']??'—' }} @endif
   </p>
  @endif
  @if($movements!==[])
   <div class="overflow" style="margin-top:13px">
    <table class="list">
     <thead><tr><th>Zeitpunkt UTC</th><th>Buchungsart</th><th>Menge</th><th>Von Lagerplatz</th><th>Nach Lagerplatz</th></tr></thead>
     <tbody>
      @foreach($movements as $i=>$movement)
       <tr wire:key="wms-movement-{{ $i }}">
        <td>{{ $movement['when'] ?: 'Nicht belegt' }}</td>
        <td>{{ $movement['kind'] ?: 'Unbekannt' }}<div class="meta">ID {{ $movement['id'] ?: 'unbekannt' }}</div></td>
        <td>{{ $movement['qty']??'Nicht belegt' }}</td>
        <td>{{ $movement['from'] ?: 'Nicht belegt' }}</td>
        <td>{{ $movement['to'] ?: 'Nicht belegt' }}</td>
       </tr>
      @endforeach
     </tbody>
    </table>
   </div>
  @endif
  <p class="note">Für <strong>aktuelle</strong> WMS-Lagerplätze mit freiem Bestand, gesperrten Mengen und Reservierungen benötigen wir eine eigene verifizierte Snapshot-Abfrage aus JTL-WMS. Aus Bewegungen allein wird kein aktueller Lagerbestand errechnet.</p>
 </div>
</section>
</div>
</div>
</x-filament-panels::page>
