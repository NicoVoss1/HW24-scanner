<?php
declare(strict_types=1);
namespace App\Services;

/** Explicit observed catalog-to-JTL name equivalences. No fuzzy manufacturer matching. */
final class HubManufacturerPolicy
{
    public static function jtlSearchName(string $name): string
    {
        $name=trim($name);
        return match(mb_strtolower($name)){
            'revlon professional','revlon'=>'Revlon',
            default=>$name,
        };
    }

    public static function equivalent(string $a,string $b): bool
    {
        return mb_strtolower(self::jtlSearchName($a))===mb_strtolower(self::jtlSearchName($b));
    }
}
