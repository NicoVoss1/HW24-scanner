<?php
declare(strict_types=1);
namespace App\Services;

/** An explicitly selected JTL template; no guessed template tags or product-specific cloning. */
final class HubTemplateDefaults
{
    public static function propose(string $identifier,string $targetEan,array $draft,array $response,array $observations=[]): array
    {
        $raw=$response['data']??[];
        $matches=$raw['matches']??null;
        if(($response['ok']??false)!==true||($raw['jtl_status']??null)!=='current_snapshot'||
            !is_array($matches)||count($matches)!==1||($raw['limited']??false)===true||!is_array($matches[0]))
            return ['ok'=>false,'message'=>'Keine eindeutige Vorlage im aktuellen JTL-Spiegel.'];
        $a=$matches[0];$number=(string)($a['artikelnummer']??'');$ean=(string)($a['ean']??'');
        if($number!==$identifier||$number===''||$ean===$targetEan||(int)($a['jtl_article_id']??0)<1)
            return ['ok'=>false,'message'=>'Bitte die genaue JTL-Artikelnummer eines anderen Vorlagenartikels angeben.'];
        $brand=trim((string)($a['hersteller']??''));$group=trim((string)($a['warengruppe_name']??''));
        if($brand===''||$group==='')return ['ok'=>false,'message'=>'Hersteller oder Warengruppe der Vorlage nicht eindeutig belegt.'];
        foreach(['Hersteller'=>$brand,'Warengruppe'=>$group] as $field=>$value){
            $old=trim((string)($draft[$field]??''));
            if($old!==''&&mb_strtolower($old)!==mb_strtolower($value))
                return ['ok'=>false,'message'=>'Vorlage widerspricht dem vorhandenen Feld '.$field.'.'];
        }
        $target=HubCategoryPolicy::suggest((string)($draft['Artikelname']??''),(string)($draft['Warengruppe']??''));
        $template=HubCategoryPolicy::suggest((string)($a['artikelname']??''),$group);
        if($target!==null&&$template!==null&&$target!==$template)
            return ['ok'=>false,'message'=>'Vorlage gehört zu einem anderen Produkttyp.'];
        $src=is_array($a['jtl_source_fields']??null)?$a['jtl_source_fields']:[];
        $values=['Hersteller'=>$brand,'Warengruppe'=>$group];
        foreach(['Artikeltyp'=>'artikeltyp','Währung'=>'waehrung','Steuerklasse'=>'steuerklasse_name',
            'Versandklasse'=>'versandklasse_name','Lieferstatus'=>'lieferstatus_name'] as $f=>$key){
            if(is_string($a[$key]??null)&&trim($a[$key])!=='')$values[$f]=trim($a[$key]);
        }
        // Only known reference labels, never append display-only ID suffix to import value.
        $tax=$observations['tax_classes'][(int)($src['kSteuerklasse']??0)]['name']??null;
        $delivery=$observations['delivery_status_by_language'][(int)($src['kLieferStatus']??0).':1']??null;
        foreach(['Steuerklasse'=>$tax,'Lieferstatus'=>$delivery] as $f=>$v)
            if(!isset($values[$f])&&is_string($v)&&trim($v)!=='')$values[$f]=$v;
        foreach(['Bestandsführung aktiv'=>'cLagerAktiv','Überverkäufe möglich'=>'cLagerKleinerNull',
            'Stückzahl teilbar'=>'cTeilbar'] as $f=>$key){
            if(in_array($src[$key]??null,['Y','N'],true))$values[$f]=$src[$key]==='Y'?'Ja':'Nein';
        }
        // Only empty target fields. Explicit zero and all existing text remain untouched.
        $fields=[];
        foreach($values as $f=>$v)if(trim((string)($draft[$f]??''))==='')$fields[$f]=$v;
        return ['ok'=>true,'number'=>$number,'id'=>(int)$a['jtl_article_id'],'name'=>(string)($a['artikelname']??''),
            'snapshot_at'=>ScanEntryPresentation::germanDate($raw['snapshot_at']??null),
            'manufacturer'=>$brand,'group'=>$group,'fields'=>$fields,
            'category_confirmed'=>$target!==null&&$template!==null&&$target===$template];
    }

    public static function emptyTexts(array $draft): array
    {
        $name=trim((string)($draft['Artikelname']??''));
        if($name==='')return [];
        $out=[];
        foreach(['Kurzbeschreibung','Beschreibung'] as $f)
            if(trim((string)($draft[$f]??''))==='')$out[$f]=$name;
        return $out;
    }
}
