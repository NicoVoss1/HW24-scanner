<?php
declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Jtl\ArticleCard;
use App\Domain\Jtl\RelatedSourceEvidence;
use App\Domain\Amazon\OfferEvidence;
use App\Services\LegacyReadBridge;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Locked;

/**
 * Source-bound stock movement history and channel price observations.
 * No direct WMS, JTL-Wawi, seller or marketplace writes.
 * WMS movements != current bin quantities or sellable inventory.
 */
final class WarehousePriceWorkbench extends Page
{
    protected static ?string $title='WMS & Preisvergleich';
    protected static ?string $navigationLabel='WMS & Preise';
    protected static ?string $slug='lager-preise';
    protected static ?int $navigationSort=3;
    protected string $view='filament.pages.warehouse-price-workbench';

    public string $identifier='';

    #[Locked] public array $product=[];
    #[Locked] public array $status=[];
    #[Locked] public array $wms=[];
    #[Locked] public array $movements=[];
    #[Locked] public int $wmsOffset=0;
    #[Locked] public array $amazonCatalog=[];
    #[Locked] public array $amazonMarket=[];
    #[Locked] public array $related=[];
    #[Locked] public array $articleStock=[];
    #[Locked] public array $ownOffers=[];
    #[Locked] public array $ownOffersStatus=[];

    public function mount(): void
    {
        $ean=(string)request()->query('ean','');
        if(preg_match('/^[0-9]{8,14}$/D',$ean))$this->identifier=$ean;
        // Intentional: no HTTP when the page renders.
    }

    public function findArticle(LegacyReadBridge $bridge): void
    {
        $this->reset(['product','status','wms','movements','wmsOffset','amazonCatalog','amazonMarket','ownOffers','ownOffersStatus','related','articleStock']);
        $identifier=trim($this->identifier);
        if(!preg_match('/^[\pL\pN][\pL\pN._\/-]{1,63}$/uD',$identifier)){
            $this->status=['ok'=>false,'message'=>'Bitte gültige EAN, HAN oder Artikelnummer eingeben.'];
            return;
        }
        $res=$bridge->fetch('jtl.article',['q'=>$identifier]);
        $raw=is_array($res['data']??null)?$res['data']:[];
        $matches=is_array($raw['matches']??null)?$raw['matches']:[];
        if(($res['ok']??false)!==true || ($raw['jtl_status']??'')!=='current_snapshot' ||
           count($matches)!==1 || !is_array($matches[0])){
            $this->status=['ok'=>false,'http'=>$res['http_status']??null,
                'message'=>'Kein eindeutig bestätigter aktueller JTL-Snapshot für diesen Artikel.'];
            return;
        }

        $row=$matches[0];
        $this->related=RelatedSourceEvidence::present($row);
        $article=ArticleCard::fromMatch($row);
        $id=(string)($article['jtl_article_id']??'');
        if(!preg_match('/^[1-9][0-9]{0,11}$/D',$id)){
            $this->status=['ok'=>false,'message'=>'JTL-Artikel-ID nicht eindeutig belegt.'];
            return;
        }
        try{$stockRead=app(\App\Services\HubStockReader::class)->readArticle($id,(string)($raw['snapshot_at']??''));}catch(\Throwable){$stockRead=['ok'=>false];}
        $this->articleStock=\App\Services\HubStockReader::workspace($stockRead,$this->related);
        $this->product=[
            'id'=>$id,
            'ean'=>$article['ean'],
            'sku'=>$article['artikelnummer'],
            'name'=>$article['artikelname'],
            'brand'=>$article['hersteller'],
            'sync'=>$article['synced_at_utc'],
            'stock'=>$article['bestand'],
            'net_purchase'=>$article['netto_ek'],
            'net_sell'=>$article['netto_vk'],
            'gross_sell'=>$article['brutto_vk'],
            'gross_source'=>$article['brutto_vk_source'],
            'currency'=>$article['waehrung'],
            'amazon_jtl'=>$this->decimal($row['amazon_vk']??null),
            'ebay_jtl'=>$this->decimal($row['ebay_preis']??null),
            'asin'=>$article['asin'],
        ];
        $this->status=['ok'=>true,'http'=>$res['http_status']??null,
            'message'=>'JTL-Stammdaten geladen. Preisarten und WMS-Historie sind getrennte Quellen.'];
    }

    private function decimal(mixed $value): ?string
    {
        if(!is_scalar($value)||!preg_match('/^(?:0|[1-9]\d{0,9})(?:\.\d{1,4})?$/D',trim((string)$value)))return null;
        return trim((string)$value);
    }

    private function wmsRequest(LegacyReadBridge $bridge,bool $next): void
    {
        $id=(string)($this->product['id']??'');
        if(!preg_match('/^[1-9][0-9]{0,11}$/D',$id))return;
        if($next && (($this->wms['ok']??false)!==true || ($this->wms['has_more']??false)!==true))return;
        $params=['article_id'=>$id,'offset'=>$next?$this->wmsOffset:0];
        if($next && preg_match('/^[0-9a-f]{32}$/D',(string)($this->wms['run_id']??''))){
            $params['run_id']=$this->wms['run_id'];
        }elseif($next){
            $this->wms=['ok'=>false,'message'=>'Fortsetzung benötigt eine bestätigte WMS-Snapshot-ID.'];
            return;
        }
        $res=$bridge->fetch('jtl.wms.history',$params);
        $raw=is_array($res['data']??null)?$res['data']:[];
        if(($res['ok']??false)!==true){
            $this->wms=['ok'=>false,'http'=>$res['http_status']??null,
                'message'=>'WMS-Historien-Endpunkt nicht erreichbar oder auf LIVE noch nicht bereitgestellt.'];
            return;
        }
        if(($raw['status']??'')==='not_synchronized'){
            $this->reset(['wmsOffset','movements']);
            $this->wms=['ok'=>false,'http'=>$res['http_status']??null,
                'message'=>'Noch kein bereitgestellter WMS-Bewegungsspiegel. Kein Lagerplatzbestand ableitbar.'];
            return;
        }
        $run=(string)($raw['run_id']??'');
        $offset=(int)($raw['offset']??-1);
        if(($raw['status']??'')!=='ready' || !preg_match('/^[0-9a-f]{32}$/D',$run) ||
            $offset!==($next?$this->wmsOffset:0) || !is_array($raw['rows']??null) ||
            ($next && $run!==($this->wms['run_id']??null))){
            $this->wms=['ok'=>false,'message'=>'WMS-Antwort oder Synchronisationsstand inkonsistent. Bitte neu abfragen.'];
            return;
        }
        $safe=[];
        foreach(array_slice($raw['rows'],0,50) as $row){
            if(!is_array($row))continue;
            $safe[]=[
                'id'=>mb_substr((string)($row['movement_id']??''),0,24),
                'when'=>mb_substr((string)($row['zeitstempel']??''),0,35),
                'kind'=>mb_substr((string)($row['buchungsart']??''),0,140),
                'qty'=>$this->quantity($row['menge']??null),
                'from'=>mb_substr((string)($row['lagerplatz_von']??''),0,140),
                'to'=>mb_substr((string)($row['lagerplatz_nach']??''),0,140),
            ];
        }
        if(!$next)$this->movements=[];
        $this->movements=array_merge($this->movements,$safe);
        $this->wmsOffset=$offset+count($raw['rows']);
        $this->wms=[
            'ok'=>true,'http'=>$res['http_status']??null,
            'run_id'=>$run,
            'snapshot_at'=>mb_substr((string)($raw['snapshot_at']??''),0,40),
            'total'=>max(0,(int)($raw['total']??0)),
            'has_more'=>($raw['has_more']??false)===true && count($raw['rows'])>0,
            'message'=>'Historische WMS-Buchungsbewegungen zum gespeicherten Snapshot; kein aktueller Lagerplatzbestand.',
        ];
    }

    private function quantity(mixed $v): ?string
    {
        if(!is_scalar($v)||!preg_match('/^-?\d{1,15}(?:\.\d{1,13})?$/D',(string)$v))return null;
        return (string)$v;
    }

    public function loadWmsHistory(LegacyReadBridge $bridge): void
    {
        $this->reset(['wms','movements','wmsOffset']);
        $this->wmsRequest($bridge,false);
    }

    public function loadMoreWmsHistory(LegacyReadBridge $bridge): void
    {
        $this->wmsRequest($bridge,true);
    }

    /**
     * Read previously verified, explicitly account-scoped seller-price observations
     * from our isolated platform SQLite. NEVER infer from JTL platform hints.
     */
    public function loadVerifiedOwnOffers(): void
    {
        $this->reset(['ownOffers','ownOffersStatus']);
        $ean=(string)($this->product['ean']??'');
        if(!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean))return;
        if(!Schema::hasTable('channel_price_observations')){
            $this->ownOffersStatus=['ok'=>false,
                'message'=>'Separater Plattform-Preisquellenkatalog fehlt. Keine aktiven Angebotswerte behaupten.'];
            return;
        }
        $now=now('UTC');
        try{
            $records=DB::table('channel_price_observations')
                ->where('ean',$ean)
                ->whereIn('channel',['amazon','ebay','shopify'])
                ->where('price_type','own_offer')
                ->where('status','verified')
                ->where('publication_status','active')
                ->where('observed_at','>=',$now->copy()->subDay())
                ->where(function($q)use($now){
                    $q->whereNull('expires_at')->orWhere('expires_at','>',$now);
                })
                ->orderByDesc('observed_at')
                ->limit(100)->get();
        }catch(\Throwable){
            $this->ownOffersStatus=['ok'=>false,'message'=>'Preisquellenkatalog derzeit nicht lesbar; keine falschen Angebotspreise.'];
            return;
        }
        $seen=[];
        foreach($records as $record){
            $channel=(string)$record->channel;
            $account=trim((string)$record->account);
            $source=trim((string)$record->source);
            $currency=(string)$record->currency;
            $observed=(string)$record->observed_at;
            $marketplace=trim((string)$record->marketplace);
            if($account===''||$source===''||$observed===''||!preg_match('/^[A-Z]{3}$/D',$currency))continue;
            $amount=$this->decimal($record->gross??null);
            $priceType='VK brutto';
            if($amount===null){$amount=$this->decimal($record->net??null);$priceType='VK netto';}
            if($amount===null){$amount=$this->decimal($record->displayed_amount??null);$priceType='Angezeigter Angebotspreis (Steuerstatus unbekannt)';}
            if($amount===null)continue;
            $key=implode('|',[$channel,$account,$marketplace,(string)$record->offer_id]);
            if(isset($seen[$key]))continue;
            $seen[$key]=true;
            $this->ownOffers[]=[
                'channel'=>$channel,'account'=>mb_substr($account,0,100),
                'marketplace'=>mb_substr($marketplace,0,32),
                'offer_id'=>mb_substr((string)$record->offer_id,0,120),
                'type'=>$priceType,'amount'=>$amount,'currency'=>$currency,
                'source'=>mb_substr($source,0,255),
                'date'=>mb_substr($observed,0,40),
            ];
            if(count($this->ownOffers)>=25)break;
        }
        $this->ownOffersStatus=['ok'=>true,'count'=>count($this->ownOffers),
            'message'=>count($this->ownOffers)>0
                ?'Zeitlich aktuelle, aktiv gemeldete eigene Angebote aus geprüften Plattform-Beobachtungen.'
                :'Keine verifizierten, aktuellen und aktiven eigenen Amazon-, eBay- oder Shopify-Angebote im Plattform-Preisquellenkatalog.'];
    }

    public function loadAmazonCandidates(LegacyReadBridge $bridge): void
    {
        $ean=(string)($this->product['ean']??'');
        if(!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean))return;
        $this->reset(['amazonCatalog','amazonMarket']);
        $res=$bridge->fetch('amazon.catalog',['ean'=>$ean]);
        $raw=is_array($res['data']??null)?$res['data']:[];
        $items=[];
        if(($res['ok']??false)===true && is_array($raw['items']??null)){
            foreach(array_slice($raw['items'],0,12) as $v){
                if(!is_array($v))continue;
                $asin=strtoupper(trim((string)($v['asin']??'')));
                if(!preg_match('/^[A-Z0-9]{10}$/D',$asin))continue;
                $items[]=['asin'=>$asin,'title'=>mb_substr((string)($v['title']??''),0,180)];
            }
        }
        $this->amazonCatalog=[
            'ok'=>($res['ok']??false)===true,
            'http'=>$res['http_status']??null,
            'source'=>in_array(($raw['source']??''),['cache','live'],true)?$raw['source']:'unbekannt',
            'date'=>mb_substr((string)($raw['fetched_at_utc']??''),0,40),
            'items'=>$items,
            'message'=>'Amazon-Katalogkandidaten sind noch keine bestätigte eigene Verkäuferangebote.',
        ];
    }

    public function loadAmazonMarketPrice(string $asin,LegacyReadBridge $bridge): void
    {
        $this->amazonMarket=[];
        $allowed=array_column($this->amazonCatalog['items']??[],'asin');
        if(!in_array($asin,$allowed,true)){
            $this->amazonMarket=['ok'=>false,'message'=>'ASIN nicht in zuvor manuell geladenen Katalogtreffern.'];
            return;
        }
        $res=$bridge->fetch('amazon.pricing',['asin'=>$asin]);
        $raw=is_array($res['data']??null)?$res['data']:[];
        $rows=[];
        foreach(['buybox_prices'=>'Buy Box','lowest_prices'=>'Niedrigstes Angebot'] as $key=>$kind){
            if(!is_array($raw[$key]??null))continue;
            foreach(array_slice($raw[$key],0,4) as $v){
                if(!is_array($v))continue;
                $amount=$v['listing_price']??$v['landed_price']??null;
                if(!is_array($amount))continue;
                $price=$this->decimal($amount['amount']??null);
                $currency=(string)($amount['currency']??'');
                if($price===null || !preg_match('/^[A-Z]{3}$/D',$currency))continue;
                $rows[]=[
                    'kind'=>$kind,'amount'=>$price,'currency'=>$currency,
                    'fulfillment'=>mb_substr((string)($v['fulfillment']??''),0,60),
                ];
            }
        }
        $names=[];
        if(($res['ok']??false)===true){
            // Existing verified seller-name archive, GET only. No Amazon SP call.
            $ids=OfferEvidence::sellerIds($raw);
            if($ids!==[]){
                $namesRes=$bridge->fetch('amazon.sellers',['ids'=>implode(',',$ids)]);
                $namesRaw=is_array($namesRes['data']??null)?$namesRes['data']:[];
                if(($namesRes['ok']??false)===true && is_array($namesRaw['names']??null)){
                    $names=$namesRaw['names'];
                }
            }
        }
        $offers=OfferEvidence::present($raw,$names);
        $this->amazonMarket=[
            'ok'=>($res['ok']??false)===true,'http'=>$res['http_status']??null,
            'asin'=>$asin,'offers'=>is_numeric($raw['total_offer_count']??null)?(int)$raw['total_offer_count']:null,
            'prices'=>$rows,
            'source'=>$offers['source'],
            'retrieved_at'=>$offers['retrieved_at'],
            'returned'=>$offers['returned'],
            'winner_count'=>$offers['winners'],
            'buy_box'=>$offers['buy_box'],
            'lowest_summary'=>$offers['lowest_summary'],
            'lowest_visible'=>$offers['lowest_visible'],
            'sellers'=>$offers['offers'],
            'message'=>'Buy Box, günstigstes Angebot und Verkäufer werden getrennt gezeigt. Das sind Amazon-Angebote, keine belegten eigenen Seller-Angebote und keine Großhandelslieferanten.',
        ];
    }
}
