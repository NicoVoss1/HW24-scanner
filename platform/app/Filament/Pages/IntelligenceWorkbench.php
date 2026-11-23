<?php
declare(strict_types=1);

namespace App\Filament\Pages;

use App\Domain\Jtl\ArticleCard;
use App\Domain\Amazon\OfferEvidence;
use App\Services\LegacyApprovedActionBridge;
use App\Services\LegacyReadBridge;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;

/**
 * Native Filament Amazon + AI workflow with archived evidence.
 * All upstream operations are manual. Paid calls require separate opt-in,
 * private operator gate, server-side budget readiness, and confirmation.
 */
final class IntelligenceWorkbench extends Page
{
    protected static ?string $title = 'KI & Amazon Recherche';
    protected static ?string $navigationLabel = 'KI & Amazon';
    protected static ?string $slug = 'intelligence';
    protected static ?int $navigationSort = 2;
    protected string $view = 'filament.pages.intelligence-workbench';

    public string $identifier = '';
    public bool $confirmPaid = false;
    public bool $confirmRecheck = false;

    #[Locked] public array $article = [];
    #[Locked] public array $lookupState = [];
    #[Locked] public array $catalog = [];
    #[Locked] public array $keywords = [];
    #[Locked] public array $catalogArchive = [];
    #[Locked] public array $pricing = [];
    #[Locked] public array $reviews = [];
    #[Locked] public array $archiveState = [];
    #[Locked] public array $readiness = [];
    #[Locked] public array $reviewResult = [];

    public function mount(): void
    {
        $ean = (string) request()->query('ean', '');
        if (preg_match('/^[0-9]{8,14}$/D', $ean)) {
            $this->identifier = $ean;
        }
        // Deliberately NO HTTP / OpenAI / Amazon call during mount.
    }

    public function findArticle(LegacyReadBridge $bridge): void
    {
        $this->reset(['article','lookupState','catalog','keywords','pricing','reviews','archiveState',
            'readiness','reviewResult','catalogArchive','confirmPaid','confirmRecheck']);
        $id = trim($this->identifier);
        if (!preg_match('/^[\pL\pN][\pL\pN._\/-]{1,63}$/uD',$id)) {
            $this->lookupState=['ok'=>false,'message'=>'Bitte eine gültige EAN, HAN oder Artikelnummer eingeben.'];
            return;
        }
        $r=$bridge->fetch('jtl.article',['q'=>$id]);
        $raw=is_array($r['data']??null)?$r['data']:[];
        $matches=is_array($raw['matches']??null)?$raw['matches']:[];
        if (($r['ok']??false)!==true || ($raw['jtl_status']??null)!=='current_snapshot' ||
            count($matches)!==1 || !is_array($matches[0])) {
            $this->lookupState=['ok'=>false,'message'=>'Kein eindeutiger aktueller JTL-Artikel. API/Identität prüfen.',
                'http'=>$r['http_status']??null];
            return;
        }
        $item=ArticleCard::fromMatch($matches[0]);
        $ean=(string)($item['ean']??'');
        if (!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean)
            || trim((string)($item['artikelname']??''))==='') {
            $this->lookupState=['ok'=>false,'message'=>'EAN oder Artikelname nicht bestätigt.'];
            return;
        }
        $this->article=[
            'ean'=>$ean,
            'name'=>mb_substr((string)$item['artikelname'],0,600),
            'brand'=>mb_substr((string)($item['hersteller']??''),0,180),
            'group'=>mb_substr((string)($item['warengruppe_name']??''),0,150),
            'jtl_id'=>(string)($item['jtl_article_id']??''),
            'sku'=>(string)($item['artikelnummer']??''),
            'asin'=>(string)($item['asin']??''),
        ];
        $this->lookupState=['ok'=>true,'message'=>'JTL-Referenzartikel eindeutig aus aktuellem Snapshot geladen.',
            'http'=>$r['http_status']??null];
    }

    private function ean(): ?string
    {
        $ean=$this->article['ean']??null;
        return is_string($ean)&&preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean)
            ?$ean:null;
    }

    public function loadAmazonCatalog(LegacyReadBridge $bridge): void
    {
        $ean=$this->ean();
        if($ean===null)return;
        $this->reset(['catalog','pricing']);
        // Explicit action: this endpoint may use cache OR make a live SP-API
        // request and write its own historic archive and lookup audit.
        $r=$bridge->fetch('amazon.catalog',['ean'=>$ean]);
        $raw=is_array($r['data']??null)?$r['data']:[];
        $items=[];
        if(($r['ok']??false)===true && is_array($raw['items']??null)){
            foreach(array_slice($raw['items'],0,12) as $item){
                if(!is_array($item))continue;
                $asin=strtoupper((string)($item['asin']??''));
                if(!preg_match('/^[A-Z0-9]{10}$/D',$asin))continue;
                $items[]=[
                    'asin'=>$asin,
                    'title'=>mb_substr((string)($item['title']??''),0,500),
                    'brand'=>mb_substr((string)($item['brand']??''),0,180),
                    'manufacturer'=>mb_substr((string)($item['manufacturer']??''),0,180),
                ];
            }
        }
        $this->catalog=[
            'ok'=>($r['ok']??false)===true,
            'http'=>$r['http_status']??null,
            'source'=>in_array(($raw['source']??null),['cache','live'],true)?$raw['source']:'unbekannt',
            'snapshot_id'=>is_numeric($raw['snapshot_id']??null)?(int)$raw['snapshot_id']:null,
            'fetched_at'=>mb_substr((string)($raw['fetched_at_utc']??''),0,40),
            'count'=>is_numeric($raw['count']??null)?(int)$raw['count']:null,
            'has_more'=>($raw['has_next_page']??false)===true,
            'error'=>mb_substr((string)($raw['error']??''),0,120),
            'items'=>$items,
        ];
    }

    public function loadAmazonArchive(LegacyReadBridge $bridge): void
    {
        $ean=$this->ean();
        if($ean===null)return;
        $r=$bridge->fetch('amazon.archive',['ean'=>$ean,'limit'=>10]);
        $raw=is_array($r['data']??null)?$r['data']:[];
        $snapshots=[];
        if(($r['ok']??false)===true && is_array($raw['snapshots']??null)){
            foreach(array_slice($raw['snapshots'],0,10) as $row){
                if(!is_array($row)||!is_numeric($row['id']??null))continue;
                $snapshots[]=[
                    'id'=>(int)$row['id'],
                    'date'=>mb_substr((string)($row['fetched_at_utc']??''),0,40),
                    'count'=>is_numeric($row['count']??null)?(int)$row['count']:null,
                ];
            }
        }
        $this->catalogArchive=[
            'ok'=>($r['ok']??false)===true,
            'http'=>$r['http_status']??null,
            'snapshots'=>$snapshots,
            'message'=>($r['ok']??false)===true
                ?'Historische Katalog-Snapshots geladen. Keine Amazon-Liveabfrage, keine Seller-Angebote.'
                :'Die neue Amazon-Archiv-GET-Schnittstelle wurde auf LIVE noch nicht gesondert bereitgestellt oder antwortet nicht.',
        ];
    }

    public function searchAmazonByName(LegacyReadBridge $bridge): void
    {
        if($this->ean()===null)return;
        $name=preg_replace('/[^\p{L}\p{N} .,_\/()+\-]/u',' ',$this->article['name']??'');
        $name=trim(preg_replace('/\s+/u',' ',(string)$name)??'');
        $name=mb_substr($name,0,120);
        if(mb_strlen($name)<3)return;
        $r=$bridge->fetch('amazon.keywords',['q'=>$name]);
        $raw=is_array($r['data']??null)?$r['data']:[];
        $rows=[];
        if(($r['ok']??false)===true && is_array($raw['items']??null)){
            foreach(array_slice($raw['items'],0,10) as $item){
                if(!is_array($item))continue;
                $asin=strtoupper((string)($item['asin']??''));
                if(!preg_match('/^[A-Z0-9]{10}$/D',$asin))continue;
                $gtins=is_array($item['gtins']??null)?$item['gtins']:[];
                $rows[]=[
                    'asin'=>$asin,'title'=>mb_substr((string)($item['title']??''),0,400),
                    'brand'=>mb_substr((string)($item['brand']??''),0,180),
                    'ean_match'=>in_array($this->ean(),$gtins,true),
                ];
            }
        }
        $this->keywords=[
            'ok'=>($r['ok']??false)===true,'http'=>$r['http_status']??null,
            'source'=>in_array(($raw['source']??null),['cache','live'],true)?$raw['source']:'unbekannt',
            'count'=>is_numeric($raw['count']??null)?(int)$raw['count']:null,
            'items'=>$rows,
        ];
    }

    public function loadAmazonPricing(string $asin, LegacyReadBridge $bridge): void
    {
        $asin=strtoupper(trim($asin));
        $allowed=array_column($this->catalog['items']??[],'asin');
        if(!in_array($asin,$allowed,true)){
            $this->pricing=['ok'=>false,'message'=>'ASIN nicht in der zuvor geprüften Katalogauskunft enthalten.'];
            return;
        }
        $r=$bridge->fetch('amazon.pricing',['asin'=>$asin]);
        $raw=is_array($r['data']??null)?$r['data']:[];
        $prices=[];
        foreach(['buybox_prices'=>'Buy Box','lowest_prices'=>'Niedrigster Angebotspreis'] as $key=>$label){
            if(!is_array($raw[$key]??null))continue;
            foreach(array_slice($raw[$key],0,4) as $p){
                if(!is_array($p))continue;
                $v=$p['listing_price']??$p['landed_price']??null;
                if(!is_array($v)||!is_numeric($v['amount']??null))continue;
                $prices[]=[
                    'type'=>$label,'amount'=>(float)$v['amount'],
                    'currency'=>mb_substr((string)($v['currency']??''),0,8),
                    'fulfillment'=>mb_substr((string)($p['fulfillment']??''),0,40),
                ];
            }
        }
        $names=[];
        $sellerNameLookup='not_checked';
        if(($r['ok']??false)===true){
            $ids=OfferEvidence::sellerIds($raw);
            if($ids===[]){
                $sellerNameLookup='no_seller_ids';
            }else{
                $namesResponse=$bridge->fetch('amazon.sellers',['ids'=>implode(',',$ids)]);
                $namesRaw=is_array($namesResponse['data']??null)?$namesResponse['data']:[];
                if(($namesResponse['ok']??false)===true && is_array($namesRaw['names']??null)){
                    $names=$namesRaw['names'];
                    $sellerNameLookup='archive_loaded';
                }else{
                    // Pricing succeeds independently, but no seller name is
                    // ever invented if the verified-name archive is offline.
                    $sellerNameLookup='seller_name_archive_unavailable';
                }
            }
        }
        $offerBoard=OfferEvidence::present($raw,$names);
        $this->pricing=[
            'ok'=>($r['ok']??false)===true,'http'=>$r['http_status']??null,
            'asin'=>$asin,'offers'=>is_numeric($raw['total_offer_count']??null)
                ?(int)$raw['total_offer_count']:null,'prices'=>$prices,
            'retrieved_at'=>$offerBoard['retrieved_at'],
            'returned'=>$offerBoard['returned'],
            'buy_box'=>$offerBoard['buy_box'],
            'lowest_summary'=>$offerBoard['lowest_summary'],
            'lowest_visible'=>$offerBoard['lowest_visible'],
            'sellers'=>$offerBoard['offers'],
            'seller_name_lookup'=>$sellerNameLookup,
            'verified_seller_names'=>count(array_filter(
                $offerBoard['offers'],static fn(array $offer): bool=>($offer['seller_name']??null)!==null
            )),
            'winner_count'=>$offerBoard['winners'],
            'message'=>'Amazon-Angebotsdaten mit Buy Box, günstigstem sichtbarem Gesamtpreis und Seller-Feedback. Keine automatische Lieferanten- oder eigene Angebotsidentität.'];
    }

    /** Current product archive. The historic actor scope remains the legacy service account. */
    public function loadAiArchive(LegacyReadBridge $bridge): void
    {
        $ean=$this->ean();
        if($ean===null)return;
        $this->loadAiArchivePage($bridge,$ean,null,false);
    }

    /**
     * Read the full history within the existing Legacy Basic-Auth actor.
     * This is NOT a cross-user admin operation or proof that older actors
     * have no records. It makes old records browsable without an EAN.
     */
    public function loadAiArchiveAll(LegacyReadBridge $bridge): void
    {
        $this->loadAiArchivePage($bridge,null,null,false);
    }

    /** Page backwards without re-running OpenAI or querying another user's archive. */
    public function loadOlderAiArchive(LegacyReadBridge $bridge): void
    {
        if(($this->archiveState['ok']??false)!==true)return;
        $cursor=$this->archiveState['more']??null;
        if(!is_int($cursor)||$cursor<1)return;
        $ean=($this->archiveState['mode']??'')==='all'?null:$this->ean();
        if(($this->archiveState['mode']??'')==='ean'&&$ean===null)return;
        $this->loadAiArchivePage($bridge,$ean,$cursor,true);
    }

    private function loadAiArchivePage(
        LegacyReadBridge $bridge,
        ?string $ean,
        ?int $beforeId,
        bool $append
    ): void {
        if(!$append){
            $this->reset(['reviews','archiveState','reviewResult','confirmPaid','confirmRecheck']);
        }
        $params=['limit'=>20];
        if($ean!==null)$params['ean']=$ean;
        if($beforeId!==null)$params['before_id']=$beforeId;
        $r=$bridge->fetch('ai.history',$params);
        $raw=is_array($r['data']??null)?$r['data']:[];
        if(($r['ok']??false)!==true||!is_array($raw['reviews']??null)){
            $this->archiveState=[
                'ok'=>false,'http'=>$r['http_status']??null,
                'count'=>count($this->reviews),
                'mode'=>$ean===null?'all':'ean','more'=>null,
                'message'=>'KI-Archiv derzeit nicht lesbar. Bisher geladene Berichte bleiben erhalten; es wurde keine neue KI-Abfrage gestartet.',
            ];
            return;
        }
        $known=array_fill_keys(array_column($this->reviews,'id'),true);
        foreach(array_slice($raw['reviews'],0,20) as $row){
            if(!is_array($row))continue;
            $id=(int)($row['id']??0);
            if($id<1||isset($known[$id]))continue;
            $known[$id]=true;
            $body=is_array($row['review']??null)?$row['review']:[];
            $facts=is_array($body['productFacts']??null)?$body['productFacts']:[];
            $tokens=is_array($row['tokens']??null)?$row['tokens']:[];
            $this->reviews[]=[
                'id'=>$id,
                'ean'=>mb_substr((string)($row['ean']??''),0,20),
                'article_name'=>mb_substr((string)($row['article_name']??''),0,600),
                'status'=>mb_substr((string)($row['status']??''),0,45),
                'model'=>mb_substr((string)($row['model']??''),0,80),
                'date'=>mb_substr((string)($row['created_at_utc']??''),0,40),
                'cost'=>is_numeric($row['usd_cost']??null)?(float)$row['usd_cost']:null,
                'tokens_input'=>max(0,(int)($tokens['input']??0)),
                'tokens_cached'=>max(0,(int)($tokens['cached']??0)),
                'tokens_output'=>max(0,(int)($tokens['output']??0)),
                'reason'=>mb_substr((string)($body['reason']??''),0,2500),
                'manufacturer'=>mb_substr((string)($facts['manufacturer']??''),0,180),
                'title'=>mb_substr((string)($facts['suggestedTitle']??''),0,600),
                'group'=>mb_substr((string)($body['suggestedWarengruppe']??''),0,150),
            ];
        }
        $next=$raw['next_before_id']??null;
        $next=is_numeric($next)&&(int)$next>0?(int)$next:null;
        $this->archiveState=[
            'ok'=>true,'http'=>$r['http_status']??null,
            'mode'=>$ean===null?'all':'ean',
            'scope'=>'legacy_authenticated_actor',
            'count'=>count($this->reviews),'more'=>$next,
            'message'=>$ean===null
                ?'Archiv des verbundenen Legacy-Benutzerkontos: letzte Prüfungen aller EANs, nicht alle Benutzer oder ältere Datenbanken.'
                :'Gespeicherte KI-Prüfungen zur ausgewählten EAN aus dem verbundenen Legacy-Benutzerkonto.',
        ];
    }

    public function inspectAiBudget(LegacyReadBridge $bridge): void
    {
        if($this->ean()===null)return;
        $r=$bridge->fetch('ai.budget');
        $raw=is_array($r['data']??null)?$r['data']:[];
        $this->readiness=[
            'ok'=>($r['ok']??false)===true,
            'http'=>$r['http_status']??null,
            'enabled'=>($raw['enabled']??false)===true,
            'archive_ready'=>($raw['archive_ready']??false)===true,
            'spent'=>is_numeric($raw['usd_month']??null)?(float)$raw['usd_month']:null,
            'budget'=>is_numeric($raw['usd_budget']??null)?(float)$raw['usd_budget']:null,
            'operator_enabled'=>config('hw24_legacy.ai_paid_enabled')===true,
        ];
    }

    public function runAiReview(LegacyApprovedActionBridge $bridge, LegacyReadBridge $read): void
    {
        $ean=$this->ean();
        $this->reviewResult=[];
        if($ean===null || !$this->confirmPaid || config('hw24_legacy.ai_paid_enabled')!==true ||
            ($this->readiness['ok']??false)!==true ||
            ($this->readiness['enabled']??false)!==true ||
            ($this->readiness['archive_ready']??false)!==true ||
            !is_numeric($this->readiness['spent']??null) ||
            !is_numeric($this->readiness['budget']??null) ||
            (float)$this->readiness['spent'] >= (float)$this->readiness['budget']) {
            $this->reviewResult=['ok'=>false,'message'=>'Kostenfreigabe, Live-Budgetstatus oder private Betreiberfreigabe fehlt. Es wurde keine Analyse gestartet.'];
            return;
        }
        // Fresh archive check on the actual click, protect from stale recheck ID.
        $arch=$read->fetch('ai.history',['ean'=>$ean,'limit'=>1]);
        $raw=is_array($arch['data']??null)?$arch['data']:[];
        if(($arch['ok']??false)!==true || !is_array($raw['reviews']??null)) {
            $this->reviewResult=['ok'=>false,'message'=>'KI-Archiv nicht nachweisbar. Kostenpflichtiger Auftrag verweigert.'];
            return;
        }
        $latest=is_array($raw['reviews'][0]??null)?$raw['reviews'][0]:null;
        if($latest && !$this->confirmRecheck) {
            $this->reviewResult=['ok'=>false,'message'=>'Ein Bericht existiert bereits. Für eine kostenpflichtige Neuprüfung die zusätzliche Bestätigung aktivieren.'];
            return;
        }
        if($this->confirmRecheck && (!$latest || (int)($latest['id']??0)<1)) {
            $this->reviewResult=['ok'=>false,'message'=>'Keine Archiv-ID für die kostenpflichtige Neuprüfung gefunden.'];
            return;
        }
        $payload=[
            'ean'=>$ean,
            'articleName'=>$this->article['name'],
            'brand'=>$this->article['brand'],
            'warengruppe'=>$this->article['group'],
            'category'=>'',
            'unit'=>'',
            'jtl_status'=>'current_snapshot',
            'candidates'=>[], // Never auto-assume Amazon EAN/ASIN identity.
        ];
        $headers=$latest?[
            'X-HW24-Recheck'=>'confirmed',
            'X-HW24-Previous-Review'=>(string)$latest['id'],
        ]:[];
        $r=$bridge->post('ai.review',$payload,$headers);
        $data=is_array($r['data']??null)?$r['data']:[];
        $this->reviewResult=[
            'ok'=>($r['ok']??false)===true,
            'http'=>$r['http_status']??null,
            'message'=>($r['ok']??false)===true
                ?'KI-Antwort erhalten. Archivstatus erneut prüfen.'
                :'KI-Auftrag nicht als erfolgreich bestätigt. Bitte Kosten- und Archivstatus vor einem erneuten Versuch prüfen.',
            'id'=>is_numeric($data['review_id']??null)?(int)$data['review_id']:null,
            'spent'=>is_numeric($data['usd_call']??null)?(float)$data['usd_call']:null,
        ];
        $this->confirmPaid=false;
        $this->confirmRecheck=false;
        $this->readiness=[]; // require refreshed budgets for any future operation.
    }
}
