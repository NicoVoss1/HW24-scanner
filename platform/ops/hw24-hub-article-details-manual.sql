-- Existing mirror, additive read-only projection, no modification to JTL or article records.
CREATE SQL SECURITY DEFINER VIEW hw24_hub_article_details AS
SELECT a.source_id,a.jtl_article_id,a.artikelnummer,a.ean,a.artikelname,a.hersteller,
 NULLIF(JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.HW24_WarengruppeName')),'null') AS warengruppe,
 NULLIF(JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.cLabelNamen')),'null') AS label_namen,
 NULLIF(JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.fArtGewicht')),'null') AS artikelgewicht,
 NULLIF(JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.fGewicht')),'null') AS versandgewicht,
 NULLIF(JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.cHerkunftsland')),'null') AS herkunftsland,
 NULLIF(JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.fMassMenge')),'null') AS menge,
 NULLIF(JSON_UNQUOTE(JSON_EXTRACT(IF(JSON_VALID(a.raw_json),a.raw_json,'{}'),'$.kMassEinheit')),'null') AS einheit_id
FROM hw24_jtl_articles a;
