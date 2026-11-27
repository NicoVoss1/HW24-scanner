<?php
declare(strict_types=1);
namespace App\Filament\Pages;
final class AmeiseReferenceWorkbench extends ReferenceTableWorkbench
{
    protected static ?string $title='JTL-Ameise-Stammdaten';
    protected static ?string $navigationLabel='JTL-Ameise-Stammdaten';
    protected static ?string $slug='ameise-stammdaten';
    protected static ?int $navigationSort=26;
    protected const AMEISE=true;
}
