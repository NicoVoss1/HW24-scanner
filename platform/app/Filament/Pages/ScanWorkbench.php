<?php
declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\LegacyReadBridge;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;

/**
 * First functional migration step: show the actual old scan batches read-only.
 * Scanner writes and JTL Ameise CSV remain in the existing, working LEGACY app.
 * The old API owns row authorization via PHP_AUTH_USER (same pinned Basic actor).
 */
final class ScanWorkbench extends Page
{
    protected static ?string $title = 'Scanliste & Sammelimport';
    protected static ?string $navigationLabel = 'Scanliste';
    protected static ?string $slug = 'scanliste';
    protected static ?int $navigationSort = 3;
    protected string $view = 'filament.pages.scan-workbench';

    #[Locked]
    public array $batches = [];

    #[Locked]
    public array $items = [];

    #[Locked]
    public ?int $selectedBatchId = null;

    #[Locked]
    public array $diagnostic = [];

    public function loadBatches(LegacyReadBridge $bridge): void
    {
        $this->reset(['items', 'batches', 'selectedBatchId', 'diagnostic']);

        $result = $bridge->fetch('scan.batches', ['op' => 'batches']);
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];

        if (($result['ok'] ?? false) !== true ||
            !is_array($raw['batches'] ?? null)) {
            $this->diagnostic = [
                'ok' => false,
                'http' => $result['http_status'] ?? null,
                'message' => 'Die vorhandene Scanlisten-API ist nicht erreichbar oder dem angemeldeten Nutzer nicht zugeordnet. Bestehende Daten wurden nicht verändert.',
            ];
            return;
        }

        foreach (array_slice($raw['batches'], 0, 50) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $id = filter_var($entry['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || $id < 1) {
                continue;
            }

            $this->batches[] = [
                'id' => $id,
                'label' => mb_substr((string) ($entry['label'] ?? 'Ohne Bezeichnung'), 0, 160),
                'created' => (string) ($entry['created_at_utc'] ?? ''),
                'updated' => (string) ($entry['updated_at_utc'] ?? ''),
            ];
        }

        $this->diagnostic = [
            'ok' => true,
            'http' => $result['http_status'] ?? null,
            'message' => 'Scan-Stapel aus der bestehenden HW24-MariaDB geladen. Kein neuer Stapel angelegt.',
        ];
    }

    public function loadBatch(int $batchId, LegacyReadBridge $bridge): void
    {
        $this->reset(['items', 'selectedBatchId']);
        // Never let the browser request arbitrary batch IDs.
        $allowed = array_column($this->batches, 'id');
        if (!in_array($batchId, $allowed, true)) {
            $this->diagnostic = [
                'ok' => false,
                'http' => null,
                'message' => 'Bitte zunächst einen verfügbaren eigenen Scan-Stapel auswählen.',
            ];
            return;
        }
        $result = $bridge->fetch('scan.items', ['op' => 'items', 'batch_id' => $batchId]);
        $raw = is_array($result['data'] ?? null) ? $result['data'] : [];

        if (($result['ok'] ?? false) !== true || !is_array($raw['items'] ?? null)) {
            $this->diagnostic = [
                'ok' => false,
                'http' => $result['http_status'] ?? null,
                'message' => 'Die Positionen dieses Stapels konnten nicht gelesen werden. Der bisherige Bestand bleibt unverändert.',
            ];
            return;
        }
        $this->selectedBatchId = $batchId;
        foreach (array_slice($raw['items'], 0, 3000) as $item) {
            if (!is_array($item)) {
                continue;
            }
            $this->items[] = [
                'ean' => mb_substr((string) ($item['ean'] ?? ''), 0, 14),
                'name' => mb_substr((string) ($item['artikelname'] ?? ''), 0, 180),
                'article_number' => mb_substr((string) ($item['artikelnummer'] ?? ''), 0, 100),
                'scans' => max(0, (int) ($item['scan_count'] ?? 0)),
                'resolution' => mb_substr((string) ($item['resolution'] ?? 'unverified'), 0, 40),
                'review' => mb_substr((string) ($item['review_state'] ?? ''), 0, 40),
            ];
        }
        $this->diagnostic = [
            'ok' => true,
            'http' => $result['http_status'] ?? null,
            'message' => 'Gespeicherte Scan-Positionen nur gelesen. Erfassen, Entwurf, Import und Export verbleiben vorerst im bisherigen HW24-Arbeitsbereich.',
        ];
    }
}
