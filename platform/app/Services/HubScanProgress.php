<?php
declare(strict_types=1);
namespace App\Services;

/** Evidence-driven labels: never show queued work as actively running. */
final class HubScanProgress
{
    public static function present(array $row,array $job,array $stock): array
    {
        $e=$job['evidence']??[];$j=$stock['state']??null;$q=$job['status']??'missing';
        $review=$row['review']??'';
        return [
            'Erfassung'=>$review==='removed'?'Gelöscht':'Erfasst',
            'JTL'=>$j==='existing'?'Vorhanden':($j==='absent'?'Nicht vorhanden':($j==='multiple'?'Mehrdeutig':'Aktueller Stand offen')),
            'Recherche'=>match($q){
                'queued'=>'Wartet auf Worker','leased'=>'Auftrag reserviert','retry'=>'Wiederholung geplant',
                'completed'=>'Abgeschlossen','blocked'=>'Ausnahme · '.($job['last_error_code']??'Grund offen'),
                default=>'Nicht angemeldet / Status offen'},
            'Amazon'=>match($e['catalog_state']??null){'live'=>'Katalog archiviert','cache'=>'Archiv verwendet','unverified'=>'Archiv nicht bestätigt','unavailable'=>'Nicht verfügbar',default=>'Noch nicht bestätigt'},
            'KI'=>($e['ai_state']??null)==='read'?((int)($e['ai_count']??0)>0?'Archivbericht vorhanden':'Kein Bericht · bei Bedarf manuell'):'Archivprüfung offen',
            'Entwurf'=>match($review){'removed'=>'Gelöscht','ready'=>'Gespeichert','draft'=>'Gespeichert',default=>($j==='existing'?'Bestandsartikel · Pflege separat':'Noch nicht gespeichert')},
            'Ameise'=>$review==='ready'?'Prüfexport freigegeben':'Nicht freigegeben',
        ];
    }
}
