<?php
declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;

/**
 * A no-data-loss functional bridge into the existing legacy workspaces.
 * Links are intentionally pinned to the existing HTTPS LIVE domain.
 * No proxying, automatic imports or new secret handling are introduced.
 */
final class LegacyWorkspace extends Page
{
    protected static ?string $title = 'Bisherige Werkzeuge';
    protected static ?string $navigationLabel = 'Alle Werkzeuge';
    protected static ?string $slug = 'werkzeuge';
    protected static ?int $navigationSort = 4;
    protected string $view = 'filament.pages.legacy-workspace';
}
