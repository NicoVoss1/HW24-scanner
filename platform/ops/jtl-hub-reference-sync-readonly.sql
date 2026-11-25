-- Read-only Windows SQL Server reference export for the existing HW24 sync.
-- Only non-sensitive reference rows. No schema or business-data changes.
IF DB_NAME() <> N'eazybusiness' THROW 50001, 'Expected eazybusiness', 1;
SELECT
 (SELECT kLieferStatus AS id,kSprache AS language_id,cName AS name
 FROM dbo.tLieferStatus FOR JSON PATH) AS delivery_status,
 (SELECT kSteuerklasse AS id,cName AS name,nStandard AS is_default,nTyp AS type_id
 FROM dbo.tSteuerklasse FOR JSON PATH) AS tax_classes,
 (SELECT kMassEinheit AS id,cCode AS code,cDisplayCode AS display_code
 FROM dbo.tMassEinheit FOR JSON PATH) AS mass_units,
 (SELECT kLieferant AS id,cLiefNr AS supplier_number,cFirma AS company,cAktiv AS active
 FROM dbo.tlieferant FOR JSON PATH) AS suppliers,
 SYSUTCDATETIME() AS observed_at_utc;
