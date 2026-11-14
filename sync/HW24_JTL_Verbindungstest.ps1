# HW24 · read-only preliminary connection test
# Does not modify eazybusiness, send articles, or write to MariaDB.
# Configuration + DPAPI secrets as described in JTL_SYNC_EINRICHTUNG.md.
param(
 [Parameter(Mandatory=$true)][string]$ConfigFile,
 [string]$TestEan='4015600040482'
)
$ErrorActionPreference='Stop'
[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12
if(-not (Test-Path -LiteralPath $ConfigFile)){throw 'Konfigurationsdatei nicht gefunden'}
$config=Get-Content -LiteralPath $ConfigFile -Raw -Encoding UTF8 | ConvertFrom-Json
if(-not $config.sql_server -or -not $config.sql_credential_file -or -not $config.endpoint){
 throw 'Konfiguration unvollstaendig: sql_server, sql_credential_file und endpoint erforderlich'
}
if($TestEan -notmatch '^\d{8,14}$'){throw 'TestEan muss 8 bis 14 Ziffern haben'}
$cred=Import-Clixml -LiteralPath $config.sql_credential_file
if(-not ($cred -is [System.Management.Automation.PSCredential])){throw 'SQL DPAPI-Konto ungueltig'}
$sb=New-Object System.Data.SqlClient.SqlConnectionStringBuilder
$sb.DataSource=[string]$config.sql_server
$sb.InitialCatalog='eazybusiness'
$sb.IntegratedSecurity=$false
$sb.UserID=$cred.UserName
$sb.Password=$cred.GetNetworkCredential().Password
$sb.ConnectTimeout=15
$sb.Encrypt=$true
$sb.TrustServerCertificate=$false
Write-Host '1/2 JTL SQL Server testen (nur SELECT, TLS-Zertifikatspruefung aktiv) ...'
$conn=New-Object System.Data.SqlClient.SqlConnection($sb.ConnectionString)
try{
 $conn.Open()
 $count=New-Object System.Data.SqlClient.SqlCommand(
  'SELECT COUNT_BIG(*) FROM dbo.tArtikel',$conn)
 $count.CommandTimeout=20
 $total=[int64]$count.ExecuteScalar()
 Write-Host ('   OK: eazybusiness / tArtikel. Artikelanzahl: '+$total)
 $cmd=New-Object System.Data.SqlClient.SqlCommand(
  'SELECT TOP (8) kArtikel,cArtNr,cBarcode,cHAN,cASIN
   FROM dbo.tArtikel
   WHERE cBarcode=@ean OR cHAN=@ean OR cArtNr=@ean ORDER BY kArtikel',$conn)
 [void]$cmd.Parameters.Add('@ean',[System.Data.SqlDbType]::NVarChar,40)
 $cmd.Parameters['@ean'].Value=$TestEan
 $reader=$cmd.ExecuteReader()
 $rows=0
 while($reader.Read()){
  $rows++
  Write-Host ('   Treffer kArtikel='+$reader['kArtikel']+
              ' / Artikelnummer='+$reader['cArtNr']+
              ' / GTIN='+$reader['cBarcode'])
 }
 $reader.Close()
 Write-Host ("   Testkennung $TestEan : $rows Treffer")
} catch{
 Write-Host '   SQL-Verbindung fehlgeschlagen. Bitte Credentials, SQL-Server-Port und SQL-TLS-Zertifikat pruefen.'
 throw
} finally {$conn.Dispose()}

Write-Host '2/2 HTTPS/Plesk JTL Status pruefen (nur GET) ...'
$endpoint=[uri]$config.endpoint
if($endpoint.Scheme -ne 'https' -or $endpoint.AbsolutePath -notmatch '/api/jtl-sync\.php$'){
 throw 'endpoint muss auf https://.../api/jtl-sync.php zeigen'
}
$statusUri=[uri]::new($endpoint,'jtl-status.php')
$headers=@{}
if($config.http_credential_file -and (Test-Path -LiteralPath $config.http_credential_file)){
 $basicCred=Import-Clixml -LiteralPath $config.http_credential_file
 if(-not ($basicCred -is [System.Management.Automation.PSCredential])){throw 'HTTP-Basic-DPAPI-Daten ungueltig'}
 $user=$basicCred.GetNetworkCredential()
 $headers['Authorization']='Basic '+[Convert]::ToBase64String(
  [Text.Encoding]::UTF8.GetBytes($user.UserName+':'+$user.Password))
}
try {
 $result=Invoke-RestMethod -Uri $statusUri.AbsoluteUri -Headers $headers -Method Get -TimeoutSec 20 -UseBasicParsing
 Write-Host ('   API: '+$result.service+' / naechster Schritt: '+$result.next)
 Write-Host ('   PHP: '+$result.php_ok+' / MySQL-Treiber: '+$result.pdo_mysql)
 Write-Host ('   Lesekonfiguration: '+$result.read_config+' / Sync-Konfiguration: '+$result.sync_config)
 Write-Host ('   Tabellen: '+$result.mirror_tables+' / letzter Snapshot bereit: '+$result.mirror_ready)
 if($result.latest_rows -ne $null){Write-Host ('   Letzte Artikelanzahl: '+$result.latest_rows)}
}catch{
 Write-Host '   API-Test fehlgeschlagen: Plesk Pull/Deployment, HTTP-Passwortschutz oder HTTPS prüfen.'
 throw
}
Write-Host 'Fertig. Es wurden KEINE JTL- oder MariaDB-Daten geaendert.';
