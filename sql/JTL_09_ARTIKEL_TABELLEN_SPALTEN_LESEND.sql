/* HW24 Artikelmanager · DB Mapping Teil 2 (JTL-Wawi 2.2.2.0)
   Nur SQL-Server Metadaten: SCHREIBT NICHTS.
   Ergebnis: genau EINE Ergebnistabelle, als CSV mit Ueberschriften exportieren.
   Die Zieltabelle-Liste ist aus "Artikelmanager DB Mapping.csv" bestaetigt.
   Fuer einige Tabellen sind mehrere gleichnamige Spalten in Beziehungen
   moeglich; Fremdschluessel werden nur angezeigt, soweit sie in sys.foreign_key_columns hinterlegt sind.
*/
USE [eazybusiness];
SET NOCOUNT ON;

WITH targets AS (
 SELECT *
 FROM (VALUES
 ( 1,N'Artikelstamm',N'dbo',N'tArtikel'),
 ( 2,N'Artikelstamm',N'dbo',N'tArtikelBeschreibung'),
 ( 3,N'Hersteller',N'dbo',N'tHersteller'),
 ( 4,N'Hersteller',N'dbo',N'tHerstellerSprache'),
 ( 5,N'Kosten und Einkauf',N'Artikel',N'tArtikelkosten'),
 ( 6,N'Kosten und Einkauf',N'dbo',N'teklieferantpreise'),
 ( 7,N'Kosten und Einkauf',N'dbo',N'tlieferant'),
 ( 8,N'Kosten und Einkauf',N'dbo',N'tliefartikel'),
 ( 9,N'Kosten und Einkauf',N'dbo',N'tLiefArtikelPreis'),
 (10,N'Kosten und Einkauf',N'dbo',N'tArtikelEinkaufsliste'),
 (11,N'Preise',N'dbo',N'tPreis'),
 (12,N'Preise',N'dbo',N'tPreisDetail'),
 (13,N'Amazon',N'dbo',N'pf_amazon_angebot'),
 (14,N'Amazon',N'dbo',N'pf_amazon_angebot_ext'),
 (15,N'Amazon',N'dbo',N'pf_amazon_angebot_mapping'),
 (16,N'Amazon',N'dbo',N'pf_amazon_angebot_fba'),
 (17,N'Amazon',N'dbo',N'pf_amazon_apa_preis'),
 (18,N'Amazon',N'Artikel',N'tArtikelAsin'),
 (19,N'Verkaufskanaele',N'SCX',N'tVerkaufskanalArtikelData'),
 (20,N'Verkaufskanaele',N'SCX',N'tArtikelVerkaufskanalFeldWert'),
 (21,N'Kategorien',N'dbo',N'tWarengruppe'),
 (22,N'Kategorien',N'dbo',N'tkategorie'),
 (23,N'Kategorien',N'dbo',N'tkategorieartikel'),
 (24,N'Kategorien',N'dbo',N'tKategorieSprache'),
 (25,N'Artikelattribute',N'dbo',N'tArtikelAttribut'),
 (26,N'Artikelattribute',N'dbo',N'tArtikelMerkmal'),
 (27,N'Einheiten',N'dbo',N'tEinheit'),
 (28,N'Einheiten',N'dbo',N'tMassEinheit'),
 (29,N'Steuern',N'dbo',N'tSteuerklasse'),
 (30,N'Versand',N'dbo',N'tVersandklasse'),
 (31,N'Lager',N'dbo',N'tlagerbestand'),
 (32,N'Lager',N'dbo',N'tWarenLagerPlatzArtikel'),
 (33,N'Lager',N'dbo',N'tLagerArtikel')
 ) AS v(prioritaet,datenbereich,tabellenschema,tabellenname)
)
SELECT
 target.datenbereich AS DATENBEREICH,
 target.tabellenschema AS TABLE_SCHEMA,
 target.tabellenname AS TABLE_NAME,
 col.column_id AS ORDINAL_POSITION,
 col.name AS COLUMN_NAME,
 typ.name AS DATA_TYPE,
 col.max_length AS MAX_LENGTH_BYTES,
 col.precision AS NUMERIC_PRECISION,
 col.scale AS NUMERIC_SCALE,
 col.is_nullable AS IS_NULLABLE,
 col.is_identity AS IS_IDENTITY,
 col.is_computed AS IS_COMPUTED,
 CASE WHEN EXISTS (
    SELECT 1 FROM sys.indexes i
    JOIN sys.index_columns ic
      ON ic.object_id=i.object_id AND ic.index_id=i.index_id
    WHERE i.object_id=tab.object_id
      AND i.is_primary_key=1 AND ic.column_id=col.column_id
 ) THEN 1 ELSE 0 END AS IS_PRIMARY_KEY_COLUMN,
 fk.name AS FK_NAME,
 refschema.name AS FK_REFERENCED_SCHEMA,
 reftab.name AS FK_REFERENCED_TABLE,
 refcol.name AS FK_REFERENCED_COLUMN
FROM targets target
JOIN sys.schemas sch ON sch.name=target.tabellenschema
JOIN sys.tables tab ON tab.schema_id=sch.schema_id AND tab.name=target.tabellenname
JOIN sys.columns col ON col.object_id=tab.object_id
JOIN sys.types typ ON typ.user_type_id=col.user_type_id
LEFT JOIN sys.foreign_key_columns fkcol
 ON fkcol.parent_object_id=tab.object_id
 AND fkcol.parent_column_id=col.column_id
LEFT JOIN sys.foreign_keys fk ON fk.object_id=fkcol.constraint_object_id
LEFT JOIN sys.tables reftab ON reftab.object_id=fkcol.referenced_object_id
LEFT JOIN sys.schemas refschema ON refschema.schema_id=reftab.schema_id
LEFT JOIN sys.columns refcol ON refcol.object_id=fkcol.referenced_object_id
 AND refcol.column_id=fkcol.referenced_column_id
ORDER BY target.prioritaet,col.column_id,FK_NAME;
