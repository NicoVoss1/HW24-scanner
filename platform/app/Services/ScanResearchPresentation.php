<?php
declare(strict_types=1);
namespace App\Services;

/** Present persisted source checks and draft coverage without conflating either with approval. */
final class ScanResearchPresentation
{
    public static function filled(mixed $value): bool
    {
        return is_scalar($value)&&trim((string)$value)!=='';
    }

    public static function draftCoverage(array $row): array
    {
        $draft=is_array($row['draft']??null)?$row['draft']:[];
        $draft['GTIN']=$row['ean']??'';
        if(!self::filled($draft['Artikelname']??null))$draft['Artikelname']=$row['name']??'';
        return [
            'filled'=>count(array_filter(ScanResearch::FIELDS,fn($f)=>self::filled($draft[$f]??null))),
            'total'=>count(ScanResearch::FIELDS),
            'missing_required'=>array_values(array_filter(ScanResearch::REQUIRED,fn($f)=>!self::filled($draft[$f]??null))),
        ];
    }

    public static function sources(array $job): array
    {
        $e=is_array($job['evidence']??null)?$job['evidence']:[];
        $count=static fn(string $key): string => is_int($e[$key]??null)?(string)$e[$key]:'nicht belegt';
        $archive=static fn(string $key): string => is_int($e[$key]??null)?' · Archiv #'.$e[$key]:'';
        return [
            'JTL-Wawi / Spiegel'=>match($e['jtl_state']??null){
                'existing'=>'Artikel vorhanden','new_candidate'=>'Kein Treffer im aktuellen Spiegel','multiple'=>'Mehrere Treffer – prüfen','unverified'=>'Nicht verifiziert',default=>'Nicht belegt'},
            'Historische Artikeldaten'=>match($e['history_state']??null){
                'read'=>$count('history_count').' Treffer gelesen','unavailable'=>'Nicht verfügbar',default=>'Nicht belegt'},
            'OpenAI-Archiv'=>match($e['ai_state']??null){
                'read'=>$count('ai_count').' Bericht(e) in der Abfrage · '.match($e['ai_scope']??null){'shared_workspace'=>'Gemeinsames Archiv','legacy_account'=>'Bisheriges Konto',default=>'Umfang unbekannt'},
                'unavailable'=>'Nicht verfügbar',default=>'Nicht belegt'},
            'Amazon-Katalog'=>match($e['catalog_state']??null){
                'live'=>'Live abgefragt'.$archive('catalog_snapshot_id'),'cache'=>'Gespeicherte Antwort verwendet'.$archive('catalog_snapshot_id'),
                'unavailable'=>'Nicht verfügbar','unverified'=>'Archiv nicht bestätigt',default=>'Nicht belegt'},
            'Amazon-Marktpreise'=>match($e['price_state']??null){
                'live_archived'=>'Live abgefragt und archiviert'.$archive('price_snapshot_id'),
                'archive_reused'=>'Archiv wiederverwendet'.$archive('price_snapshot_id'),
                'identity_unverified'=>'Produktidentität offen','no_match'=>'Kein Katalogtreffer',
                'archive_unverified'=>'Archiv nicht bestätigt','unavailable'=>'Nicht verfügbar',default=>'Nicht belegt'},
            'Amazon-Händler'=>match($e['seller_state']??null){
                'read'=>$count('seller_count').' bestätigte Namenszuordnung(en)','no_ids'=>'Keine Händler-IDs','unavailable'=>'Nicht verfügbar',default=>'Nicht belegt'},
        ];
    }

    /** Dated, operator-verified reference values; never represent these as freshly synced. */
    private static function observedReference(string $field,int $id,array $o): ?string
    {
        $v=match($field){
            'Lieferstatus'=>$o['delivery_status_by_language'][$id.':1']??null,
            'Steuerklasse'=>$o['tax_classes'][$id]['name']??null,
            'Maßeinheit'=>$o['mass_unit_examples'][$id]['display_code']??null,
            default=>null,
        };
        return is_string($v)&&trim($v)!==''?$v:null;
    }

    /** A calculation, not a JTL original, only if every observed zone for this tax class agrees. */
    public static function grossCalculation(array $a,array $observations): ?array
    {
        if(self::filled($a['brutto_vk']??null))return null;
        $class=(int)($a['steuerklasse_id']??$a['jtl_source_fields']['kSteuerklasse']??0);
        $net=$a['netto_vk']??null;
        if($class<1||!is_numeric($net)||!is_finite((float)$net)||(float)$net<0)return null;
        $rates=[];
        foreach($observations['tax_rate_percent_by_class_and_zone']??[] as $key=>$rate)
            if(str_starts_with((string)$key,$class.':')&&is_numeric($rate))$rates[]=(float)$rate;
        if(count($rates)<2||count(array_unique($rates))!==1)return null;
        return ['value'=>number_format(round((float)$net*(1+$rates[0]/100),2),2,',','.'),'rate'=>$rates[0],
            'source'=>'Berechnung aus JTL Netto-VK und den am 23.11.2026 beobachteten übereinstimmenden Steuersätzen; kein bestätigter JTL-Bruttowert.'];
    }

    /** Current mirror only, exact EAN, no guessed reference names or derived prices. */
    public static function jtlFields(string $ean,array $response,array $observations=[]): array
    {
        $raw=is_array($response['data']??null)?$response['data']:[];
        $matches=$raw['matches']??null;
        if(($response['ok']??false)!==true||($raw['jtl_status']??null)!=='current_snapshot'||
            !is_array($matches)||count($matches)!==1||($raw['limited']??false)===true||
            !is_array($matches[0])||(string)($matches[0]['ean']??'')!==$ean)
            return ['ok'=>false,'message'=>'Kein eindeutiger aktueller JTL-Spiegeltreffer zur EAN. Keine Feldbewertung möglich.'];
        $a=$matches[0];$source=is_array($a['jtl_source_fields']??null)?$a['jtl_source_fields']:[];
        $direct=['Artikelnummer'=>'artikelnummer','GTIN'=>'ean','HAN'=>'han','ASIN'=>'asin','Artikelname'=>'artikelname',
            'Hersteller'=>'hersteller','Artikeltyp'=>'artikeltyp','Warengruppe'=>'warengruppe_name','Steuerklasse'=>'steuerklasse_name',
            'Währung'=>'waehrung','Kosten'=>'kosten','Netto-VK'=>'netto_vk','Brutto-VK'=>'brutto_vk',
            'Netto-EK in Standardwährung'=>'netto_ek_standard_currency','Artikelgewicht'=>'artikelgewicht','Versandgewicht'=>'versandgewicht','Herkunftsland'=>'herkunftsland',
            'Kurzbeschreibung'=>'kurzbeschreibung','Beschreibung'=>'beschreibung','Titel-Tag (SEO)'=>'seo_titel',
            'Meta-Description (SEO)'=>'seo_beschreibung','Versandklasse'=>'versandklasse_name','Lieferstatus'=>'lieferstatus_name','Maßeinheit'=>'masseinheit_name'];
        $refs=['Warengruppe'=>'kWarengruppe','Steuerklasse'=>'kSteuerklasse','Versandklasse'=>'kVersandklasse',
            'Maßeinheit'=>'kMassEinheit','Lieferstatus'=>'kLieferStatus','Hersteller'=>'kHersteller'];
        $rawFields=['Inhalt/Menge'=>'fMassMenge','Anmerkung'=>'cAnmerkung','Artikel aktiv'=>'cAktiv',
            'Bestandsführung aktiv'=>'cLagerAktiv','Überverkäufe möglich'=>'cLagerKleinerNull',
            'Stückzahl teilbar'=>'cTeilbar','Zusätzliche Bearbeitungszeit'=>'nBearbeitungszeit',
            'Beschaffungszeit automatisch ermitteln'=>'nAutomatischeLiefertageberechnung',
            'Top-Artikel'=>'cTopArtikel','Neu im Sortiment'=>'cNeu'];
        $fields=[];
        foreach(ScanResearch::FIELDS as $field){
            $key=$direct[$field]??null;
            $value=$key!==null?($a[$key]??null):null;
            $id=$refs[$field]??null;
            $rawKey=$rawFields[$field]??null;
            $origin=$key!==null?'JTL-Spiegel: '.$key:'Keine geprüfte Zuordnung';
            if($rawKey!==null&&array_key_exists($rawKey,$source)){
                $value=$source[$rawKey];$origin='JTL-Spiegel: '.$rawKey;
            }
            if(self::filled($value)){
                $state='value';$display=(string)$value;
                if(in_array($field,['Artikelgewicht','Versandgewicht','Herkunftsland'],true))$origin='JTL tArtikel: '.(['Artikelgewicht'=>'fArtGewicht','Versandgewicht'=>'fGewicht','Herkunftsland'=>'cHerkunftsland'][$field]);
                if(in_array($field,['Inhalt/Menge','Artikelgewicht','Versandgewicht','Kosten','Netto-VK','Brutto-VK','Netto-EK in Standardwährung'],true)&&preg_match('/^-?\d+\.\d+$/D',$display))$display=rtrim(rtrim($display,'0'),'.');
            }elseif($id!==null&&isset($source[$id])&&is_numeric($source[$id])&&(int)$source[$id]>0){
                $ref=(int)$source[$id];
                $label=self::observedReference($field,$ref,$observations);
                if($label!==null){
                    $state='observed_reference';$display=$label.' (ID '.$ref.')';
                    $origin='JTL-Auslesung 23.11.2026 · noch nicht laufend synchronisiert';
                }else{
                    $state='reference';$display='ID '.$ref.' – Klartext nicht belegt';
                    $origin='JTL-Spiegel: '.$id;
                }
            }elseif($key!==null&&array_key_exists($key,$a)){
                // A null optional mirror mapping does not prove that JTL itself is empty.
                $state='empty_snapshot';$display='';
            }elseif($key!==null||$rawKey!==null||$id!==null){
                $state='missing_source';$display='';
                if($rawKey!==null)$origin='JTL tArtikel: '.$rawKey;
                elseif($id!==null)$origin='JTL tArtikel: '.$id;
            }else{
                $state='unmapped';$display='';
            }
            $fields[$field]=['state'=>$state,'value'=>$display,'source'=>$origin];
        }
        return ['ok'=>true,'snapshot_at'=>ScanEntryPresentation::germanDate($raw['snapshot_at']??null),
            'fields'=>$fields,'filled'=>count(array_filter($fields,fn($v)=>in_array($v['state'],['value','observed_reference'],true))),
            'total'=>count($fields),'name'=>(string)($a['artikelname']??''),
            'average_ek_net'=>$a['netto_ek']??null,
            'gross_calculation'=>self::grossCalculation($a,$observations)];
    }
}
