<?php
declare(strict_types=1);
namespace App\Services;

use PDO;
use Throwable;

/**
 * Nur lesende Abfrage auf einen separaten, manuell validierten JTL-CSV-Snapshot.
 * Kein Zugriff auf produktive JTL-/HW24-Datenbanken, keine Amazon/DHL-Aufrufe.
 */
final class TrackingSnapshotReader
{
    public function findOrder(string $order): array
    {
        if (!preg_match('/^[0-9]{3}-[0-9]{7}-[0-9]{7}$/D',$order)) {
            return ['ok'=>false,'state'=>'invalid_order','message'=>'Bitte eine gültige Amazon-Bestellnummer eingeben.'];
        }
        $source=(string) config('hw24_support.tracking_snapshot','');
        $root=realpath(storage_path('app/private'));
        $path=$source!==''?realpath($source):false;
        if (!$root || !$path || !is_file($path) || !is_readable($path) ||
            !str_starts_with($path,rtrim($root,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)) {
            return ['ok'=>false,'state'=>'not_synchronized','message'=>'Noch kein geschützter JTL-Tracking-Snapshot für die Testplattform bereitgestellt.'];
        }
        try {
            $pdo=new PDO('sqlite:'.$path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
            $pdo->exec('PRAGMA query_only=ON');
            $meta=$pdo->query('SELECT imported_at_utc,source_rows,package_count,event_count,time_zone_verified FROM snapshot_info WHERE id=1')->fetch(PDO::FETCH_ASSOC);
            if (!is_array($meta)) throw new \RuntimeException('snapshot manifest absent');
            $stmt=$pdo->prepare('SELECT package_id,shipment_id,amazon_order_id,jtl_order,delivery_note,tracking_number,carrier FROM packages WHERE amazon_order_id=:order ORDER BY package_id LIMIT 21');
            $stmt->execute(['order'=>$order]);
            $packages=$stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($packages)>20) {
                return ['ok'=>false,'state'=>'ambiguous','message'=>'Mehr als 20 Pakete gefunden. Bitte Bestellung manuell prüfen.'];
            }
            $evt=$pdo->prepare('SELECT state_id,event_at_raw,status_code,event_text,provider_status,scan_location,country FROM tracking_events WHERE package_id=:id ORDER BY event_at_raw DESC,state_id DESC LIMIT 100');
            $result=[];
            foreach ($packages as $package) {
                $evt->execute(['id'=>(int)$package['package_id']]);
                $events=[];
                foreach ($evt->fetchAll(PDO::FETCH_ASSOC) as $event) {
                    $events[]=[
                        'at_raw'=>(string)$event['event_at_raw'],
                        'text'=>mb_substr((string)$event['event_text'],0,1300),
                        'code'=>(string)($event['provider_status']??''),
                        'location'=>(string)($event['scan_location']??''),
                        'country'=>(string)($event['country']??''),
                    ];
                }
                $result[]=[
                    'package_id'=>(int)$package['package_id'],
                    'jtl_order'=>(string)$package['jtl_order'],
                    'delivery_note'=>(string)$package['delivery_note'],
                    'tracking'=>(string)$package['tracking_number'],
                    'carrier'=>(string)$package['carrier'],
                    'events'=>$events,
                    'latest'=>$events[0]??null,
                ];
            }
            $at=(string)$meta['imported_at_utc'];
            $ts=strtotime($at.' UTC');
            $maxHours=max(1,min(168,(int)config('hw24_support.snapshot_max_age_hours',12)));
            $age=$ts!==false?time()-$ts:PHP_INT_MAX;
            return [
                'ok'=>true,'state'=>count($result)>0?'ready':'order_not_in_snapshot',
                'order'=>$order,'packages'=>$result,
                'snapshot_at_utc'=>$at,
                'stale'=>$age<0||$age>($maxHours*3600),
                'source'=>'JTL Track & Trace, isolierter CSV-Snapshot',
                'timezone_verified'=>(int)$meta['time_zone_verified']===1,
                'message'=>count($result)>0
                    ?'Gespeicherte Paketereignisse gefunden; kein Live-Abruf bei DHL.'
                    :'Bestellung ist in diesem Snapshot nicht enthalten. Nicht automatisch als unversandt bewerten.',
            ];
        } catch (Throwable) {
            // Keine privaten SQL-Inhalte, Dateipfade oder Bestellnummern in Logs.
            return ['ok'=>false,'state'=>'snapshot_unreadable','message'=>'Tracking-Snapshot nicht lesbar oder inkonsistent.'];
        }
    }
}