<?php
declare(strict_types=1);

namespace App\Services;

/** Display persisted intake evidence; never infer scanner origin from scan_count. */
final class ScanEntryPresentation
{
    public static function germanDate(mixed $value): ?string
    {
        if (!is_string($value) ||
            !preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d{1,6})?(?:Z|\+00:00)?$/D',$value))
            return null;
        try {
            $date=new \DateTimeImmutable($value,new \DateTimeZone('UTC'));
            $errors=\DateTimeImmutable::getLastErrors();
            if ($errors!==false && ($errors['warning_count']||$errors['error_count'])) return null;
            $local=$date->setTimezone(new \DateTimeZone('Europe/Berlin'));
            return $local->format('d.m.Y · H:i:s').' '.($local->format('I')==='1'?'MESZ':'MEZ');
        } catch (\Throwable) {
            return null;
        }
    }

    public static function present(array $item): array
    {
        $csv=is_array($item['csv_meta']??null)?$item['csv_meta']:[];
        $hasCsv=($csv['origin']??null)==='CSV-Import';
        $count=$item['scan_event_count']??null;
        $hasScan=is_numeric($count)&&(int)$count>0;
        $origin=match(true) {
            $hasCsv&&$hasScan=>'CSV-Import und Scan',
            $hasCsv=>'CSV-Import nachgewiesen',
            $hasScan=>'Scan nachgewiesen',
            default=>'Erfassungsart nicht belegt',
        };
        $importAt=$hasCsv?self::germanDate($csv['imported_at_utc']??null):null;
        $confirmedAt=$hasCsv?self::germanDate($csv['confirmed_at_utc']??null):null;
        $importLabel=match(true) {
            $importAt!==null=>'Angelegt per CSV: '.$importAt,
            $confirmedAt!==null=>'CSV bestätigt: '.$confirmedAt,
            $hasCsv=>'Importzeit nicht separat gespeichert',
            default=>null,
        };
        $resolution=match($item['resolution']??'') {
            'existing'=>'In JTL vorhanden',
            'new_candidate'=>'Neuanlage-Kandidat',
            'multiple'=>'Mehrdeutiger Treffer',
            'unverified'=>'Noch nicht verifiziert',
            default=>'Status nicht bekannt',
        };
        $review=match($item['review_state']??'') {
            'scan_only'=>'Erfasst · Prüfung offen',
            'draft'=>'Entwurf · in Bearbeitung',
            'ready'=>'Für Ameise-Prüfexport freigegeben',
            'removed'=>'Entfernt',
            default=>'Prüfstatus nicht bekannt',
        };
        return [
            'origin_label'=>$origin,
            'entry_at'=>self::germanDate($item['first_scanned_utc']??null),
            'import_label'=>$importLabel,
            'last_scan_at'=>$hasScan?self::germanDate($item['last_scanned_utc']??null):null,
            'updated_at'=>self::germanDate($item['updated_at_utc']??null),
            'resolution_label'=>$resolution,
            'review_label'=>$review,
        ];
    }
}
