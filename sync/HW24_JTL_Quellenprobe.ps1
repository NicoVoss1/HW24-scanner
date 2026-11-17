# HW24 read-only preflight for one verified JTL product.
# Windows PowerShell 5.1, same Windows account as working connection test.
# NO JTL writes, NO Plesk HTTP requests, NO MariaDB writes, NO local writes.
param(
 [string]$ConfigFile='C:\HW24\jtl-sync.json',
 [string]$MapFile='',
 [string]$TestEan='4015600040482'
)
$ErrorActionPreference='Stop'
[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12
if($TestEan -notmatch '^\d{8,14}$'){throw 'Test-EAN muss 8 bis 14 Ziffern haben'}
if(-not (Test-Path -LiteralPath $ConfigFile -PathType Leaf)){throw 'jtl-sync.json fehlt'}
$config=Get-Content -LiteralPath $ConfigFile -Raw -Encoding UTF8 | ConvertFrom-Json
if(-not $config.sql_server -or -not $config.sql_credential_file){throw 'SQL-Konfiguration ist unvollstaendig'}
if(-not $MapFile){
 $MapFile=Join-Path (Split-Path -Parent $ConfigFile) 'jtl-related-sources.json'
}
if(-not (Test-Path -LiteralPath $MapFile -PathType Leaf)){throw 'jtl-related-sources.json fehlt'}
$map=Get-Content -LiteralPath $MapFile -Raw -Encoding UTF8 | ConvertFrom-Json
Write-Host 'HW24 READ-ONLY QUELLENPROBE' -ForegroundColor Cyan
Write-Host ('EAN: '+$TestEan+' / Zusatzquellen: '+@($map.sources).Count)
Write-Host ('Installierte Flags: WMS='+$map.include_wms_bins+' / Steuer='+$map.include_tax_de)
Write-Host 'Die Probe liest WMS + Steuer testweise, selbst wenn die Flags false sind.'
Write-Host 'Keine HTTP-Uebertragung, keine Aenderung an Daten oder Konfiguration.' -ForegroundColor Cyan

$base=Split-Path -Parent $MyInvocation.MyCommand.Path
$extra=Join-Path $base 'HW24_JTL_Zusatzquellen.ps1'
$warehouse=Join-Path $base 'HW24_JTL_SteuerWMS.ps1'
foreach($file in @($extra,$warehouse)){
 if(-not(Test-Path -LiteralPath $file -PathType Leaf)){throw ('Skriptdatei fehlt: '+$file)}
}
$credential=Import-Clixml -LiteralPath $config.sql_credential_file
if(-not ($credential -is [System.Management.Automation.PSCredential])){throw 'SQL-Konto ist nicht lesbar'}
$builder=New-Object System.Data.SqlClient.SqlConnectionStringBuilder
$builder['Data Source']=[string]$config.sql_server
$builder['Initial Catalog']='eazybusiness'
$builder['Integrated Security']=$false
$builder['User ID']=$credential.UserName
$builder['Password']=$credential.GetNetworkCredential().Password
$builder['Connect Timeout']=15
$builder['Encrypt']=$true
$builder['TrustServerCertificate']=$false
$conn=[System.Data.SqlClient.SqlConnection]::new($builder.ConnectionString)
$failure=0
try {
 $conn.Open()
 Write-Host 'OK: sichere SQL-Verbindung hergestellt' -ForegroundColor Green
 $article=New-Object System.Data.DataTable
 $cmd=[System.Data.SqlClient.SqlCommand]::new()
 $cmd.Connection=$conn
 $cmd.CommandTimeout=30
 $cmd.CommandText=@'
SELECT TOP (1) * FROM dbo.tArtikel
WHERE cBarcode=@ean OR cHAN=@ean OR cArtNr=@ean
ORDER BY kArtikel
'@
 [void]$cmd.Parameters.Add('@ean',[System.Data.SqlDbType]::NVarChar,40)
 $cmd.Parameters['@ean'].Value=$TestEan
 $adapter=[System.Data.SqlClient.SqlDataAdapter]::new($cmd)
 [void]$adapter.Fill($article)
 if($article.Rows.Count -ne 1){throw ('EAN-Treffer nicht eindeutig: '+$article.Rows.Count)}
 $id=[string]$article.Rows[0]['kArtikel']
 Write-Host ('OK: Artikel gefunden kArtikel='+$id+', Artikelnummer='+[string]$article.Rows[0]['cArtNr']) -ForegroundColor Green

 Write-Host ''
 Write-Host '[1/2] Zusatzfelder, Lieferanten, Amazon: SELECT-Probe'
 try {
  . $extra
  $related=Get-HW24RelatedSources -Connection $conn -ArticleTable $article -MapFile $MapFile
  if(-not($related -is [hashtable])){throw 'Rueckgabe des Zusatzquellenlesers ist keine Hashtable'}
  $entry=if($related.ContainsKey($id)){$related[$id]}else{@{}}
  foreach($key in @($entry.Keys | Sort-Object)){
   $rows=@($entry[$key]['rows'])
   Write-Host ('  '+$key+': '+$rows.Count+' Datensaetze'+$(if($entry[$key]['truncated']){' (Liste gekuerzt)'}else{''}))
   if($key -eq 'warengruppe'){
    foreach($row in $rows){if($row.ContainsKey('cName')){Write-Host ('    Warengruppe: '+$row['cName'])}}
   }
   if($key -eq 'kosten'){
    foreach($row in $rows){if($row.ContainsKey('fArtikelkosten')){Write-Host ('    Artikelkosten: '+$row['fArtikelkosten'])}}
   }
  }
  Write-Host 'OK: Zusatzquellenabfrage abgeschlossen' -ForegroundColor Green
 }catch{
  $failure++
  Write-Host ('FEHLER Zusatzquellen: '+$_.Exception.Message) -ForegroundColor Red
 }

 Write-Host ''
 Write-Host '[2/2] WMS-Fach + DE-Steuerwerte: SELECT-Probe'
 try {
  . $warehouse
  $enriched=Get-HW24TaxAndBinSources -Connection $conn -ArticleTable $article -IncludeBins $true -IncludeTax $true
  if(-not($enriched -is [hashtable])){throw 'Rueckgabe des WMS-/Steuerlesers ist keine Hashtable'}
  if(-not $enriched.ContainsKey($id)){throw ('Keine Ausgabe fuer kArtikel='+$id)}
  $data=$enriched[$id]
  $bins=@($data['wms_lagerplaetze']['rows'])
  Write-Host ('  WMS-Faecher: '+$bins.Count)
  foreach($bin in $bins){
   Write-Host ('    Lager '+$bin['warenlager_id']+' / Fach '+$bin['lagerplatz']+
      ' / Menge '+$bin['menge']+' / gesperrt '+$bin['gesperrt']+
      ' / Auslieferungssperre '+$bin['auslieferung_gesperrt'])
  }
  $taxRows=@($data['steuer_saetze_de']['rows'])
  Write-Host ('  DE-Steuerkonfigurationen: '+$taxRows.Count)
  foreach($rate in $taxRows){
   Write-Host ('    Firma '+$rate['firma_id']+' / Zone '+$rate['steuerzone_id']+
    ' / Land '+$rate['zielland']+' / Steuersatz-Quellwert '+$rate['steuersatz_quellwert'])
  }
  if($bins.Count -eq 0){Write-Warning 'Keine Fachposition gefunden. Bitte SQL12-Wertabfrage vergleichen.'}
  Write-Host 'OK: WMS-/Steuerabfrage abgeschlossen' -ForegroundColor Green
 }catch{
  $failure++
  Write-Host ('FEHLER WMS/Steuer: '+$_.Exception.Message) -ForegroundColor Red
 }
}finally{$conn.Dispose()}
Write-Host ''
if($failure -eq 0){Write-Host 'GRUEN: Beide SQL-Quellenleser konnten ausgefuehrt werden.' -ForegroundColor Green}
else{Write-Host ('ROT: '+$failure+' von 2 Quellenpruefungen fehlgeschlagen. Kein Voll-Sync starten.') -ForegroundColor Red}
Write-Host 'Keine Datenbank- oder Konfigurationsaenderungen vorgenommen.'
