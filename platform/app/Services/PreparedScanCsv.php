<?php
declare(strict_types=1);
namespace App\Services;

/** Prepared product CSV only. Never silently interpret a physical inventory sheet. */
final class PreparedScanCsv
{
    public static function parse(string $text): array
    {
        if(strlen($text)>1048576 || !mb_check_encoding($text,'UTF-8') || str_contains($text,"\0"))
            throw new \InvalidArgumentException('Bitte eine UTF-8-CSV bis 1 MB auswählen.');
        $text=preg_replace('/^\xEF\xBB\xBF/','',$text);
        $first=strtok($text,"\r\n");
        if($first===false)throw new \InvalidArgumentException('Datei ist leer.');
        $delimiter=str_contains($first,';')?';':(str_contains($first,"\t")?"\t":',');
        $stream=fopen('php://temp','w+');
        fwrite($stream,$text);rewind($stream);
        try {
            $header=fgetcsv($stream,0,$delimiter,'"','');
            $map=['ean'=>'ean','gtin'=>'ean','lagerplatz'=>'lagerplatz','information'=>'information','kommentar'=>'kommentar'];
            $keys=[]; $seen=[];
            foreach($header?:[] as $h){
                $key=$map[mb_strtolower(trim((string)$h))]??null;
                if($key===null||isset($seen[$key]))throw new \InvalidArgumentException('Nur vorbereitete Neuware-CSV mit EAN/GTIN und optional Lagerplatz, Information, Kommentar. Originalinventur zuerst klassifizieren.');
                $keys[]=$key;$seen[$key]=true;
            }
            if(!isset($seen['ean']))throw new \InvalidArgumentException('EAN-/GTIN-Spalte fehlt.');
            $rows=[];$duplicates=0;$line=1;$identities=[];
            while(($cells=fgetcsv($stream,0,$delimiter,'"',''))!==false){
                $line++;
                if($line>1001)throw new \InvalidArgumentException('Maximal 1.000 Produktzeilen je Datei.');
                if(count($cells)===1&&trim((string)$cells[0])==='')continue;
                if(count($cells)!==count($keys))throw new \InvalidArgumentException('Spaltenzahl stimmt nicht in Zeile '.$line.'.');
                $row=array_combine($keys,array_map(static fn($x)=>trim((string)$x),$cells));
                $ean=$row['ean'];
                if(!preg_match('/^(?:[0-9]{8}|[0-9]{12}|[0-9]{13}|[0-9]{14})$/D',$ean)||preg_match('/^0+$/D',$ean))
                    throw new \InvalidArgumentException('Ungültige GTIN in Zeile '.$line.'.');
                $sum=0;$weight=3;
                for($i=strlen($ean)-2;$i>=0;$i--){$sum+=(int)$ean[$i]*$weight;$weight=$weight===3?1:3;}
                if((10-$sum%10)%10!==(int)$ean[strlen($ean)-1])
                    throw new \InvalidArgumentException('GTIN-Prüfziffer falsch in Zeile '.$line.'.');
                foreach(['lagerplatz'=>100,'information'=>450,'kommentar'=>600] as $field=>$limit){
                    if(mb_strlen($row[$field]??'')>$limit)throw new \InvalidArgumentException('Metadaten zu lang in Zeile '.$line.'.');
                }
                if(preg_match('/\b(?:schrott|mona|mone|b[\s-]*ware)\b/iu',implode(' ',array_diff_key($row,['ean'=>true]))))
                    throw new \InvalidArgumentException('Ausgeschlossene Ware in Zeile '.$line.'. Bitte vorbereitete Neuware-Datei verwenden.');
                $identity=str_pad($ean,14,'0',STR_PAD_LEFT);
                if(isset($identities[$identity])){
                    if($identities[$identity]!==$row)throw new \InvalidArgumentException('Abweichende Daten für doppelte GTIN in Zeile '.$line.'. Bitte zuerst zusammenfassen.');
                    $duplicates++;continue;
                }
                $identities[$identity]=$row;
                $rows[]=$row;
            }
            if(!$rows)throw new \InvalidArgumentException('Keine gültigen Produktzeilen.');
            return ['rows'=>$rows,'duplicates'=>$duplicates];
        } finally {fclose($stream);}
    }
}
