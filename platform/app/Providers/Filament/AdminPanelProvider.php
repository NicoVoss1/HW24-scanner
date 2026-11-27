<?php
declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\ArticleLookup;
use App\Filament\Pages\ScanWorkbench;
use App\Filament\Pages\LegacyWorkspace;
use App\Filament\Pages\IntelligenceWorkbench;
use App\Filament\Pages\WarehousePriceWorkbench;
use App\Filament\Pages\QueryAuditWorkbench;
use App\Filament\Pages\ArticleHistoryWorkbench;
use App\Filament\Pages\ReferenceTableWorkbench;
use App\Filament\Pages\AmeiseReferenceWorkbench;
use App\Filament\Widgets\Hw24SystemOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName('HW24 Intelligence Console')
            ->login()
            ->maxContentWidth(\Filament\Support\Enums\Width::Full)
            ->renderHook(\Filament\View\PanelsRenderHook::HEAD_END, fn () => view('filament.partials.hub-workspace-styles'))
            ->colors(['primary' => Color::Blue])
            ->pages([Dashboard::class, ArticleLookup::class, IntelligenceWorkbench::class, WarehousePriceWorkbench::class, ScanWorkbench::class, LegacyWorkspace::class, ArticleHistoryWorkbench::class, AmeiseReferenceWorkbench::class, ReferenceTableWorkbench::class, \App\Filament\Pages\FieldPolicyWorkbench::class, \App\Filament\Pages\InventoryQualityWorkbench::class, QueryAuditWorkbench::class])
            ->widgets([Hw24SystemOverview::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}
