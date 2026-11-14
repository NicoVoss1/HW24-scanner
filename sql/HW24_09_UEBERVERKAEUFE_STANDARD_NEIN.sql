-- HW24 · Sicherheitskorrektur für bereits angelegte Standardwerte
-- Ausschließlich den neuen Standard für Überverkäufe auf NEIN stellen.
-- Bestehende JTL-Artikel und deren Artikelwerte bleiben unverändert.
-- Vor Ausführung in phpMyAdmin DB hw24_artikelmanager auswählen.

UPDATE hw24_profile_defaults
SET default_value='N',
    provenance='user+export',
    explanation='Keine Überverkäufe als Standard. Manuelle Ausnahme je Artikel nur nach bewusster Freigabe. JTL Export 14.11.2026: 2690 von 2691 Artikeln N.',
    changed_utc=UTC_TIMESTAMP(6)
WHERE field_key='Überverkäufe möglich' AND default_value <> 'N';

SELECT field_key,default_value,applies_to,provenance
FROM hw24_profile_defaults
WHERE field_key='Überverkäufe möglich';
