<?php
declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * OPTIONAL seeder only for the independent Laravel platform database.
 * These are SQL COLUMN observations from operator-supplied HeidiSQL output,
 * not verified values, account IDs or live channel prices.
 *
 * Run only via: php artisan db:seed --class=ObservedSourcesSeeder
 * No access to the original JTL/WMS or legacy MariaDB is required.
 */
final class ObservedSourcesSeeder extends Seeder
{
    public function run(): void
    {
        $columns=[
            // schema, table, field, SQL type, business group
            ['dbo','tArtikel','kArtikel','int','Artikelstamm'],
            ['dbo','tArtikel','cBarcode','nvarchar','Artikelstamm'],
            ['dbo','tArtikel','cArtNr','nvarchar','Artikelstamm'],
            ['dbo','tArtikel','cASIN','nvarchar','Amazon'],
            ['dbo','tArtikel','fVKNetto','decimal','Preise'],
            ['dbo','tArtikel','fAmazonVK','decimal','Amazon'],
            ['dbo','tArtikel','fEbayPreis','decimal','eBay'],
            ['dbo','tArtikel','kSteuerklasse','int','Steuern'],
            ['dbo','tArtikel','kLieferStatus','int','Logistik'],
            ['dbo','tArtikel','fMassMenge','decimal','Mengen'],
            ['Artikel','tArtikelType','kArtikel','int','Artikeltyp'],
            ['Artikel','tArtikelType','uArtikelType','uniqueidentifier','Artikeltyp'],
            ['SCX','tChannelPriceType','kChannelPriceType','int','Kanalpreise'],
            ['SCX','tChannelPriceType','kChannel','int','Kanalpreise'],
            ['SCX','tChannelPriceType','kShop','int','Kanalpreise'],
            ['SCX','tChannelPriceType','cPriceTypeId','nvarchar','Kanalpreise'],
            ['SCX','tChannelPriceType','cDisplayName','nvarchar','Kanalpreise'],
            ['SCX','tChannelPriceType','kKundenGruppe','int','Kanalpreise'],
            ['BI','tKostenTyp','kKostentyp','int','Kostenarten'],
            ['BI','tKostenTyp','cKostentyp','nvarchar','Kostenarten'],
            ['BI','tKostenTyp','cKommentar','nvarchar','Kostenarten'],
            ['dbo','ebay_de_waehrung','Currency','nvarchar','Währung'],
            ['dbo','ebay_de_waehrung','Description','nvarchar','Währung'],
            ['dbo','pf_amazon_apa_preis','cSellerSKU','nvarchar','Amazon'],
            ['dbo','pf_amazon_apa_preis','nPlattform','int','Amazon'],
            ['dbo','pf_amazon_apa_preis','fPreis','decimal','Amazon'],
            ['dbo','pf_amazon_Steuercodes','kSteuercode','int','Steuern'],
            ['dbo','pf_amazon_Steuercodes','cName','nvarchar','Steuern'],
            ['dbo','tLandSteuersatz','cISOLand','nvarchar','Steuern'],
            ['dbo','tLandSteuersatz','fNormalerSatz','decimal','Steuern'],
            ['dbo','tLandSteuersatz','fErmaessigterSatz','decimal','Steuern'],
            ['dbo','tArtikelSonderpreis','kArtikel','int','Sonderpreise'],
            ['dbo','tArtikelSonderpreis','nAktiv','tinyint','Sonderpreise'],
            ['dbo','tArtikelSonderpreis','dStart','datetime','Sonderpreise'],
            ['dbo','tArtikelSonderpreis','dEnde','datetime','Sonderpreise'],
            ['dbo','tkonfigitempreis','kKundengruppe','int','Kanalpreise'],
            ['dbo','tkonfigitempreis','kSteuerklasse','int','Steuern'],
            ['dbo','tkonfigitempreis','fPreis','decimal','Kanalpreise'],
            ['dbo','tkonfigitempreis','kShop','int','Kanalpreise'],
            ['Verkauf','tAuftrag','cWaehrung','char','Aufträge'],
        ];
        $now=now();
        foreach($columns as [$schema,$table,$field,$type,$group]){
            DB::table('source_field_catalog')->insertOrIgnore([
                'source_system'=>'jtl',
                'source_entity'=>$schema.'.'.$table,
                'field_name'=>$field,
                'sql_type'=>$type,
                'business_group'=>$group,
                'verification_state'=>'metadata_only',
                // Only the known tArtikel original columns are in the existing
                // full raw_json; other table presence alone proves no row sync.
                'present_in_mirror'=>$schema==='dbo'&&$table==='tArtikel',
                'is_sensitive'=>false,
                'created_at'=>$now,'updated_at'=>$now
            ]);
        }
        // Operator-provided labels only. No external account ID and no
        // assumed active eBay offer, currency or tax-inclusive price.
        foreach([
            ['ebay','www.serverler24.shop'],
            ['ebay','friseurbedarf24.shop'],
            ['shopify','HAIRWORLD24'],
            ['shopify','Boosterwelt24']
        ] as [$channel,$label]){
            DB::table('channel_account_registry')->insertOrIgnore([
                'channel'=>$channel,'account_label'=>$label,
                'external_account_id'=>null,'source_system'=>null,
                'verification_state'=>'label_only',
                'verified_at'=>null,
                'created_at'=>$now,'updated_at'=>$now
            ]);
        }
    }
}
