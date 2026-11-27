<?php
declare(strict_types=1);
namespace App\Services;

/** Eligibility for human-confirmed Ameise draft release, not a direct import. */
final class HubBatchRelease
{
    public static function reason(array $row,array $stock): ?string
    {
        if(($row['resolution']??null)!=='new_candidate'||($row['review']??null)!=='draft')
            return 'Kein gespeicherter offener Neuanlage-Entwurf';
        if(($stock['state']??null)!=='absent')return 'Neuanlage im aktuellen JTL-Spiegel nicht eindeutig bestätigt';
        $draft=$row['draft']??[];
        if(($draft['GTIN']??null)!==($row['ean']??null))return 'GTIN stimmt nicht mit Scan überein';
        $missing=HubFieldPolicy::missing($draft);
        if($missing)return 'Basisfelder offen: '.implode(', ',$missing);
        if(HubCategoryPolicy::conflict((string)($draft['Artikelname']??''),(string)($draft['Warengruppe']??''),
            (string)($draft['Kategorie Ebene 1']??''),(string)($draft['Kategorie Ebene 2']??'')))
            return 'Kategorie-Konflikt';
        return null;
    }
}
