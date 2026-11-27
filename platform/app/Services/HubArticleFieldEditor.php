<?php
declare(strict_types=1);
namespace App\Services;

/** Operator-provided corrections, not automatically inferred product data. */
final class HubArticleFieldEditor
{
    public const MANUAL=['HAN','Artikelname','Kurzbeschreibung','Beschreibung','Titel-Tag (SEO)','Meta-Description (SEO)',
        'Kosten','Netto-VK','Netto-EK in Standardwährung','Artikelgewicht','Versandgewicht','Inhalt/Menge',
        'Zusätzliche Bearbeitungszeit','Beschaffungszeit manuell setzen','Anmerkung','URL-Pfad'];
    public const FLAGS=['Artikel aktiv','Bestandsführung aktiv','Überverkäufe möglich','Stückzahl teilbar','Beschaffungszeit automatisch ermitteln','Top-Artikel','Neu im Sortiment'];
    public const REFERENCE=['Hersteller','Warengruppe','Artikeltyp','Währung','Steuerklasse','Versandklasse','Lieferstatus','Maßeinheit','Kategorie Ebene 1','Kategorie Ebene 2','Kategorie Ebene 3'];
    public const NUMERIC=['Kosten','Netto-VK','Netto-EK in Standardwährung','Artikelgewicht','Versandgewicht','Inhalt/Menge','Zusätzliche Bearbeitungszeit','Beschaffungszeit manuell setzen'];
    public static function manualValue(string $field,mixed $value):?string
    {
        if(!is_string($value)||!in_array($field,[...self::MANUAL,...self::FLAGS],true))return null;
        $v=trim($value);
        if($v===''||mb_strlen($v)>30000||preg_match('/^[=+\-@]/u',$v)||preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u',$v))return null;
        if(in_array($field,self::FLAGS,true))return in_array($v,['Y','N'],true)?$v:null;
        if(in_array($field,self::NUMERIC,true)){
            $v=str_replace(',','.',$v);
            if(!preg_match('/^\d{1,10}(?:\.\d{1,6})?$/D',$v))return null;
            if(in_array($field,['Zusätzliche Bearbeitungszeit','Beschaffungszeit manuell setzen'],true)&&str_contains($v,'.'))return null;
        }
        if($field==='HAN'&&mb_strlen($v)>140)return null;
        if($field==='Artikelname'&&mb_strlen($v)>500)return null;
        return $v;
    }
    public static function option(array $current,string $field,string $value,string $source):array
    {
        $old=$current[$field]??[];
        return ['value'=>$value,'source'=>$source,'old'=>(string)($old['value']??''),
            'old_state'=>$old['state']??'unmapped','replacement'=>true];
    }
    /** Explicit choices are never included in the automatic unique selection. */
    public static function manualOptions(array $current,array $inputs):array
    {
        $out=[];
        if(count($inputs)>count(ScanResearch::FIELDS))throw new \RuntimeException('Too many fields');
        foreach($inputs as $f=>$v){
            if($v==='')continue;
            $value=self::manualValue($f,$v);
            if($value===null)throw new \RuntimeException('Ungültiger manueller Wert: '.$f);
            $old=$current[$f]??[];
            if(($old['state']??null)==='value'&&trim((string)($old['value']??''))===$value)continue;
            $token=hash('sha256',$f."\0".$value);
            $out[$f][$token]=self::option($current,$f,$value,'Manuelle Bedienerangabe · ausdrücklich geprüft');
        }
        return $out;
    }

    /** Use the same template engine as scan intake; only confirmed names, no prices or weights from peers. */
    public static function templateOptions(array $current,array $template):array
    {
        $out=[];
        foreach($template['fields']??[] as $f=>$v){
            if(!in_array($f,[...self::REFERENCE,...self::FLAGS],true)||!is_string($v)||trim($v)===''||empty($template['sources'][$f]))continue;
            if(in_array($current[$f]['state']??null,['value','observed_reference','reference'],true))continue;
            $out[$f][hash('sha256',$f."\0".$v)]=self::option($current,$f,$v,$template['sources'][$f]);
        }
        return $out;
    }

    /** Existing reference names or documented choices. Not a claim that the current JTL value is known. */
    public static function referenceOptions(array $current,array $refs,array $observations,array $manufacturers=[]):array
    {
        $out=[];
        $add=static function(string $f,mixed $v,string $source)use(&$out,$current){
            if(!is_string($v)||trim($v)===''||mb_strlen($v)>300||preg_match('/^[=+\-@]/u',$v))return;
            if(($current[$f]['state']??null)==='value'&&trim((string)$current[$f]['value'])===$v)return;
            $out[$f][hash('sha256',$f."\0".$v)]=self::option($current,$f,$v,$source);
        };
        foreach($manufacturers as $r)if(is_string($r['hersteller']??null)&&isset($r['kHersteller'])&&HubManufacturerPolicy::resolve($r['hersteller'],$manufacturers)!==null)
            $add('Hersteller',$r['hersteller'],'JTL Herstellerstamm · ID '.$r['kHersteller']);
        foreach($observations['tax_classes']??[] as $id=>$row)$add('Steuerklasse',$row['name']??null,'JTL-Auslesung 23.11.2026 · ID '.$id);
        foreach($observations['mass_unit_examples']??[] as $id=>$row)$add('Maßeinheit',$row['display_code']??null,'JTL-Auslesung 23.11.2026 · ID '.$id);
        foreach($observations['delivery_status_by_language']??[] as $id=>$name)$add('Lieferstatus',$name,'JTL-Auslesung 23.11.2026 · ID/Sprache '.$id);
        foreach($refs as $r)foreach(self::REFERENCE as $f){
            if($f==='Hersteller')continue;
            $add($f,$r['fields'][$f]??null,'Ameise-Original desselben Artikels · Zeile '.$r['row']);
        }
        return $out;
    }
}
