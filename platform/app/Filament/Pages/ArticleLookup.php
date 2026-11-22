<?php
declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\LegacyReadBridge;
use App\Domain\Jtl\SourceSnapshot;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;

final class ArticleLookup extends Page
{
    protected static ?string $title = 'Artikel & Schnittstellen';
    protected static ?string $navigationLabel = 'Artikel & APIs';
    protected static ?string $slug = 'artikel';
    protected string $view = 'filament.pages.article-lookup';

    public string $identifier = '';

    #[Locked]
    public array $lookup = [];

    #[Locked]
    public array $amazon = [];

    #[Locked]
    public array $ai = [];

    #[Locked]
    public array $sourceState = [];

    #[Locked]
    public array $sourceRows = [];

    #[Locked]
    public int $sourceNextOffset = 0;

    public string $sourceQuery = '';

    public function findArticle(LegacyReadBridge $bridge): void
    {
        $this->reset(['lookup', 'amazon', 'ai', 'sourceState', 'sourceRows', 'sourceNextOffset', 'sourceQuery']);
        $id = trim($this->identifier);
        if (! preg_match('/^[\pL\pN][\pL\pN._\/-]{1,63}$/uD', $id)) {
            $this->addError('identifier', 'Bitte EAN, HAN oder JTL-Artikelnummer eingeben.');
            return;
        }
        $this->resetErrorBag('identifier');

        $result = $bridge->fetch('jtl.article', ['q' => $id]);
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];
        $matches = [];
        foreach (array_slice(is_array($raw['matches'] ?? null) ? $raw['matches'] : [], 0, 15) as $row) {
            if (! is_array($row)) continue;
            $matches[] = [
                'jtl_article_id' => (string) ($row['jtl_article_id'] ?? ''),
                'artikelnummer' => (string) ($row['artikelnummer'] ?? ''),
                'ean' => (string) ($row['ean'] ?? ''),
                'han' => (string) ($row['han'] ?? ''),
                'artikelname' => (string) ($row['artikelname'] ?? ''),
                'hersteller' => (string) ($row['hersteller'] ?? ''),
                'bestand' => $row['bestand'] ?? null,
                'netto_vk' => $row['netto_vk'] ?? null,
                'netto_ek' => $row['netto_ek'] ?? null,
            ];
        }
        $this->lookup = [
            'ok' => (bool) ($result['ok'] ?? false),
            'state' => (string) ($result['state'] ?? 'unavailable'),
            'http_status' => $result['http_status'] ?? null,
            'jtl_status' => (string) ($raw['jtl_status'] ?? 'unbekannt'),
            'matches' => $matches,
            'ean' => isset($matches[0]) ? $matches[0]['ean'] : null,
        ];
    }

    /** Manually load one page of original JTL source fields, never a bulk DB export. */
    public function readJtlSources(LegacyReadBridge $bridge): void
    {
        $this->reset(['sourceState','sourceRows','sourceNextOffset','sourceQuery']);
        $this->fetchJtlSourcePage($bridge);
    }

    public function loadMoreJtlSources(LegacyReadBridge $bridge): void
    {
        if (($this->sourceState['ok']??false)!==true ||
            ($this->sourceState['has_more']??false)!==true) return;
        $this->fetchJtlSourcePage($bridge);
    }

    private function fetchJtlSourcePage(LegacyReadBridge $bridge): void
    {
        // Multiple matches must be resolved by article number before using
        // any article-specific source rows.
        $matches=$this->lookup['matches']??[];
        if (($this->lookup['jtl_status']??null)!=='current_snapshot' ||
            !is_array($matches)||count($matches)!==1) {
            $this->sourceState=['ok'=>false,'reason'=>'Eindeutigen, aktuellen JTL-Artikel wählen.'];
            return;
        }
        $article=$matches[0];
        $id=(string)($article['jtl_article_id']??'');
        $ean=(string)($article['ean']??'');
        if (!preg_match('/^[0-9]{1,12}$/D',$id) ||
            !preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean)) {
            $this->sourceState=['ok'=>false,'reason'=>'JTL-Artikel-ID oder EAN ungültig.'];
            return;
        }
        $result=$bridge->fetch('jtl.fields',[
            'article_id'=>$id,'ean'=>$ean,'offset'=>$this->sourceNextOffset,'limit'=>80
        ]);
        $raw=is_array($result['data']??null)?$result['data']:[];
        $page=SourceSnapshot::present($raw);
        if(($result['ok']??false)!==true || ($page['ok']??false)!==true ||
            ($page['article_id']??null)!==$id || ($page['ean']??null)!==$ean) {
            $this->sourceState=['ok'=>false,'reason'=>'JTL-Feldquelle nicht verfügbar oder noch nicht bereitgestellt.'];
            return;
        }
        if ($this->sourceNextOffset>0 &&
            ($this->sourceState['snapshot_at']??null)!==$page['snapshot_at']) {
            $this->reset(['sourceRows','sourceNextOffset']);
            $this->sourceState=['ok'=>false,'reason'=>'Neuer JTL-Snapshot erkannt. Quellfelder bitte erneut laden.'];
            return;
        }
        $this->sourceRows=array_merge($this->sourceRows,$page['fields']);
        $this->sourceNextOffset+=count($page['fields']);
        $this->sourceState=[
            'ok'=>true,'snapshot_at'=>$page['snapshot_at'],
            'field_count'=>$page['field_count'],
            'has_more'=>$page['has_more'],
            'prices'=>$page['prices'],
        ];
    }

    public function readAmazon(LegacyReadBridge $bridge): void
    {
        $ean = $this->verifiedEan();
        if ($ean === null) return;

        // A live Amazon SP-API lookup is ONLY invoked when the operator clicks.
        $result = $bridge->fetch('amazon.catalog', ['ean' => $ean]);
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];
        $this->amazon = [
            'ok' => (bool) ($result['ok'] ?? false),
            'state' => (string) ($result['state'] ?? 'unavailable'),
            'http_status' => $result['http_status'] ?? null,
            'count' => $raw['count'] ?? null,
            'source' => $raw['source'] ?? null,
            'error' => (string) ($raw['error'] ?? ''),
            'stage' => (string) ($raw['stage'] ?? ''),
        ];
    }

    public function readAiArchive(LegacyReadBridge $bridge): void
    {
        $ean = $this->verifiedEan();
        if ($ean === null) return;

        // GET existing archive only. No OpenAI billable POST and no new analysis.
        $result = $bridge->fetch('ai.history', ['ean' => $ean, 'limit' => 5]);
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];
        $this->ai = [
            'ok' => (bool) ($result['ok'] ?? false),
            'state' => (string) ($result['state'] ?? 'unavailable'),
            'http_status' => $result['http_status'] ?? null,
            'count' => is_array($raw['reviews'] ?? null) ? count($raw['reviews']) : null,
            'error' => (string) ($raw['error'] ?? ''),
        ];
    }

    public function verifiedEan(): ?string
    {
        $ean = $this->lookup['ean'] ?? null;
        return is_string($ean) && preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D', $ean)
            ? $ean : null;
    }
}
