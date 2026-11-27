-- HW24 eazybusiness: PHASE 5, only read-only verified reference values and system metadata.
-- Purpose: labels for delivery status, tax, units, channel price types, and price schema.
-- For HeidiSQL connected to Microsoft SQL Server 2017.
-- Run each SELECT independently after the DB context check.
-- NO SELECT * FROM dbo.tShop: it includes API keys and passwords.
-- No business prices, customer records, order information or credentials returned.
-- Do NOT add results verbatim to the public GitHub journal.

-- 0) Must be 'eazybusiness'. STOP if different.
SELECT DB_NAME() AS datenbankname;

-- 1) Delivery status with language (1 row previously reported).
SELECT kLieferStatus, kSprache, cName
FROM dbo.tLieferStatus
ORDER BY kLieferStatus, kSprache;

-- 2) Tax-class labels (2 rows previously reported).
SELECT kSteuerklasse, cName, nStandard, nTyp
FROM dbo.tSteuerklasse
ORDER BY kSteuerklasse;

-- 3) Tax zones + rates (4 rates previously reported).
-- Do not assume all records represent the current standard German consumer VAT rate.
SELECT kSteuersatz, kSteuerklasse, kSteuerzone, fSteuersatz, nPrio
FROM dbo.tSteuersatz
ORDER BY kSteuerklasse, kSteuerzone, nPrio, kSteuersatz;

-- 4) Original units and conversion factors (31 rows previously reported).
SELECT kMassEinheit, cCode, cDisplayCode, kBezugsMassEinheit,
       fBezugsMassEinheitFaktor, cUnitCode
FROM dbo.tMassEinheit
ORDER BY kMassEinheit;

-- 5) Verified names of channel price types (12 rows previously reported).
SELECT kChannelPriceType, kChannel, kShop,
       cPriceTypeId, cDisplayName, cDescription, kKundenGruppe
FROM SCX.tChannelPriceType
ORDER BY kChannel, kShop, kChannelPriceType;

-- 6) Shop IDs and labels. EXPLICIT ALLOWLIST.
-- IMPORTANT: NEVER fetch dbo.tShop.cAPIKey, cPasswortWeb, cBenutzerWeb.
SELECT kShop, cName, nTyp, nAktiv, kFirma
FROM dbo.tShop
ORDER BY kShop;

-- 7) Columns of still-unmapped price/account/channel/reference tables:
-- Metadata ONLY. No stored secrets, personal data or offers.
SELECT s.name AS schema_name, t.name AS table_name,
       c.column_id AS column_position, c.name AS column_name,
       ty.name AS sql_type, c.is_nullable, c.is_identity
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id=t.schema_id
JOIN sys.columns c ON c.object_id=t.object_id
JOIN sys.types ty ON ty.user_type_id=c.user_type_id
WHERE t.is_ms_shipped=0 AND (
  (s.name=N'dbo' AND t.name IN (
    N'tPreisDetail', N'tKundenGruppe', N'tShopScx', N'tShopWaehrung',
    N'tSteuerzone', N'tSteuerzoneLand', N'tMassEinheitSprache',
    N'tArtikelSonderpreis', N'tSonderpreise', N'tPreis'))
  OR (s.name=N'SCX' AND t.name IN (N'tChannel',N'tChannelPriceType'))
  OR (s.name=N'Artikel' AND t.name IN (N'tArtikelType'))
)
ORDER BY s.name, t.name, c.column_id;

-- 8) Candidate currency reference table NAMES ONLY.
SELECT s.name AS schema_name, t.name AS table_name
FROM sys.tables t
JOIN sys.schemas s ON s.schema_id=t.schema_id
WHERE t.is_ms_shipped=0 AND (
  t.name LIKE N'%Waehr%' OR t.name LIKE N'%Währ%'
  OR t.name LIKE N'%Currency%'
)
ORDER BY s.name,t.name;

-- 9) Aggregated channel offer counts by shop. NO seller IDs or business prices.
SELECT kShop, COUNT_BIG(*) AS angebote,
       SUM(CASE WHEN nIsListed=1 THEN CONVERT(bigint,1) ELSE CONVERT(bigint,0) END) AS als_gelistet_markiert
FROM SCX.tOffer
GROUP BY kShop
ORDER BY kShop;
