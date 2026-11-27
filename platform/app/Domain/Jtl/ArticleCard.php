<?php
declare(strict_types=1);

namespace App\Domain\Jtl;

/**
 * Presentation-only, source-bound JTL summary for the operator dashboard.
 *
 * Never derive gross VK, currency, item type, shipping or delivery status
 * from unrelated IDs or assumptions. All values remain nullable.
 * No DB or HTTP access occurs here.
 */
final class ArticleCard
{
    private static function text(mixed $value, int $max = 220): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }
        $text = trim((string) $value);

        return $text !== '' ? mb_substr($text, 0, $max) : null;
    }

    private static function decimal(mixed $value): ?float
    {
        if (! is_scalar($value) || ! is_numeric($value)) {
            return null;
        }
        $number = (float) $value;

        return is_finite($number) ? $number : null;
    }

    public static function fromMatch(array $row): array
    {
        $fields = is_array($row['jtl_source_fields'] ?? null) ? $row['jtl_source_fields'] : [];
        $gross = self::decimal($row['brutto_vk'] ?? null);
        $grossSource = self::text($row['brutto_vk_source'] ?? null, 200);
        $ean = self::text($row['ean'] ?? null, 20);
        $asin = self::text($row['asin'] ?? null, 20);
        if ($asin !== null && ! preg_match('/^[A-Z0-9]{10}$/D', $asin)) {
            $asin = null;
        }

        return [
            'jtl_article_id' => self::text($row['jtl_article_id'] ?? null, 20) ?? '',
            'artikelnummer' => self::text($row['artikelnummer'] ?? null, 100) ?? '',
            'ean' => $ean ?? '',
            'han' => self::text($row['han'] ?? null, 100) ?? '',
            'artikelname' => is_scalar($row['artikelname']??null) ? trim((string)$row['artikelname']) : '',
            'hersteller' => self::text($row['hersteller'] ?? null, 120) ?? '',
            'bestand' => self::decimal($row['bestand'] ?? null),
            'netto_vk' => self::decimal($row['netto_vk'] ?? null),
            'netto_ek' => self::decimal($row['netto_ek'] ?? null),
            'brutto_vk' => $gross !== null && $gross > 0 && $grossSource !== null ? $gross : null,
            'brutto_vk_source' => $gross !== null && $gross > 0 ? $grossSource : null,
            'waehrung' => self::text($row['waehrung'] ?? null, 20),
            'artikeltyp' => self::text($row['artikeltyp'] ?? null, 100),
            'warengruppe_name' => self::text($row['warengruppe_name'] ?? null, 140),
            'steuerklasse_id' => self::text($row['steuerklasse_id'] ?? ($fields['kSteuerklasse'] ?? null), 30),
            'steuerklasse_name' => self::text($row['steuerklasse_name'] ?? null, 120),
            'lieferstatus_id' => self::text($fields['kLieferStatus'] ?? null, 30),
            'lieferstatus_name' => self::text($row['lieferstatus_name'] ?? null, 120),
            'masseinheit_menge' => self::decimal($fields['fMassMenge'] ?? null),
            'asin' => $asin,
            'synced_at_utc' => self::text($row['synced_at_utc'] ?? null, 45),
        ];
    }
}
