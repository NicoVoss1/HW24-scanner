# Diagnose fuer den Windows-Server, auf dem JTL-Wawi/eazybusiness laeuft.
# Nur LESEN: Aufgabenplaner-Metadaten und gesicherten Plesk-Status abrufen.
# Keine Credentials, Tokens oder Task-Argumente ausgeben.
param([string]$ConfigFile='C:\HW24\jtl-sync.json')
$ErrorActionPreference='Stop'
Write-Host ('Windows-Zeit: '+(Get-Date -Format 'yyyy-MM-dd HH:mm:ss zzz'))
Write-Host '--- Windows Aufgabenplaner: HW24 / JTL / WMS / Artikelmanager / Sync ---'
$tasks=@(Get-ScheduledTask | Where-Object {
  ($_.TaskName+' '+$_.TaskPath -match 'HW24|JTL|WMS|Artikelmanager|Scanner|Sync') -or
  (($_.Actions | ForEach-Object { $_.Execute+' '+$_.Arguments }) -join ' ' -match 'HW24_JTL_Synchronisieren|jtl-sync\.json')
})
if(-not $tasks.Count){Write-Host 'WARNUNG: Kein Task mit HW24/JTL/WMS/Sync erkennbar. Name oder anderer Scheduler moeglich.'}
foreach($task in $tasks){
  $info=$null
  try{$info=Get-ScheduledTaskInfo -TaskName $task.TaskName -TaskPath $task.TaskPath -ErrorAction Stop}catch{}
  [PSCustomObject]@{
    TaskName=$task.TaskPath+$task.TaskName
    Status=[string]$task.State
    LetzterLauf=if($info -and $info.LastRunTime -gt [DateTime]'2001-01-01'){$info.LastRunTime.ToString('yyyy-MM-dd HH:mm:ss')}else{'nicht erfasst'}
    LetzterCode=if($info){[string]$info.LastTaskResult}else{'nicht abrufbar'}
    NaechsterLauf=if($info -and $info.NextRunTime -gt [DateTime]'2001-01-01'){$info.NextRunTime.ToString('yyyy-MM-dd HH:mm:ss')}else{'nicht geplant'}
  }
} | Format-Table -AutoSize
Write-Host '--- HW24 Synchronisationskonfiguration (nur Dateiexistenz) ---'
if(-not (Test-Path -LiteralPath $ConfigFile)){
 Write-Host ('KEINE Konfiguration unter '+$ConfigFile+' gefunden. Dies sagt nichts ueber alternative Task-Pfade aus.')
 exit 0
}
$cfg=Get-Content -LiteralPath $ConfigFile -Raw -Encoding UTF8 | ConvertFrom-Json
Write-Host ('Konfigurationsdatei vorhanden: '+$ConfigFile)
if(-not $cfg.endpoint){Write-Host 'KEINE HTTPS-Sync-URL konfiguriert.';exit 0}
$uri=[uri]$cfg.endpoint
if($uri.Scheme -ne 'https' -or $uri.AbsolutePath -notmatch '/api/jtl-sync\.php$'){
 Write-Host 'Sync-URL hat ein unerwartetes Schema oder einen unerwarteten Pfad.'
 exit 0
}
Write-Host ('API-Zielhost: '+$uri.Host+' (kein Token oder Zugang ausgegeben)')
$headers=@{}
if($cfg.http_credential_file -and (Test-Path -LiteralPath $cfg.http_credential_file)){
 try{
  $credential=Import-Clixml -LiteralPath $cfg.http_credential_file -ErrorAction Stop
  $c=$credential.GetNetworkCredential()
  $headers.Authorization='Basic '+[Convert]::ToBase64String(
   [Text.Encoding]::UTF8.GetBytes($c.UserName+':'+$c.Password))
 }catch{Write-Host 'HTTP-DPAPI-Zugang kann unter diesem Windows-Konto nicht entschluesselt werden.'}
}
$statusUrl=([uri]::new($uri,'jtl-status.php')).AbsoluteUri
try{
 $result=Invoke-RestMethod -Method GET -Uri $statusUrl -Headers $headers -TimeoutSec 20 -UseBasicParsing
 [PSCustomObject]@{
   service=$result.service
   mirror_tables=$result.mirror_tables
   mirror_ready=$result.mirror_ready
   sync_config=$result.sync_config
   latest_snapshot_utc=$result.latest_snapshot_utc
   age_minutes=$result.age_minutes
   latest_rows=$result.latest_rows
   next=$result.next
 } | Format-List
}catch{
 Write-Host 'HTTP-Statusantwort konnte nicht gelesen werden (HTTPS/Passwortschutz/Deployment pruefen).'
}
Write-Host 'Keine JTL-, WMS- oder MariaDB-Datensaetze veraendert.'
