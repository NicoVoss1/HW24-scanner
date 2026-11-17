# HW24 JTL SQL Server 2017 -> Plesk MariaDB full snapshot synchronizer
# Windows PowerShell 5.1 or PowerShell 7; run on the Windows host that can reach eazybusiness.
# No SQL port exposure, no direct writes into JTL-Wawi.
# Prerequisite: SQL read-only credential, separate HTTPS basic credential and
# API token stored encrypted under the SAME Windows account using DPAPI.
# See sync/JTL_SYNC_EINRICHTUNG.md. Do not commit a config or credentials.
param(
  [Parameter(Mandatory=$true)][string]$ConfigFile
)
$ErrorActionPreference='Stop'
[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12
$config=Get-Content -LiteralPath $ConfigFile -Raw -Encoding UTF8 | ConvertFrom-Json
if(-not $config.endpoint -or -not $config.sql_server -or -not $config.sql_credential_file) {
  throw 'Konfiguration unvollstaendig'
}
$sqlCredential=Import-Clixml -LiteralPath $config.sql_credential_file
if(-not ($sqlCredential -is [PSCredential])){throw 'SQL-Anmeldedaten nicht lesbar'}
$basicCredential=Import-Clixml -LiteralPath $config.http_credential_file
if(-not ($basicCredential -is [PSCredential])){throw 'Plesk-HTTP-Zugang nicht lesbar'}
$encryptedToken=(Get-Content -LiteralPath $config.api_token_file -Raw -Encoding UTF8).Trim()
$secureToken=ConvertTo-SecureString -String $encryptedToken
$token=(New-Object -TypeName System.Net.NetworkCredential -ArgumentList @('token',$secureToken)).Password
if($token.Length -lt 32){throw 'API-Token ist zu kurz'}
$login=$basicCredential.GetNetworkCredential()
$basic=[Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($login.UserName+':'+$login.Password))
$headers=@{'Authorization'='Basic '+$basic;'X-HW24-Sync-Token'=$token}
$dsn=New-Object -TypeName System.Data.SqlClient.SqlConnectionStringBuilder
$dsn['Data Source']=[string]$config.sql_server
$dsn['Initial Catalog']='eazybusiness'
$dsn['Integrated Security']=$false
$dsn['User ID']=$sqlCredential.UserName
$dsn['Password']=$sqlCredential.GetNetworkCredential().Password
$dsn['Connect Timeout']=15
$dsn['Encrypt']=$true
$dsn['TrustServerCertificate']=$false # install/verify proper SQL Server certificate before running
function To-DbObject($row) {
  $obj=@{}
  foreach($col in $row.Table.Columns){
    $name=[string]$col.ColumnName
    $value=$row[$name]
    if($value -is [DBNull]){$obj[$name]=$null}
    elseif($value -is [DateTime]){$obj[$name]=$value.ToString('yyyy-MM-dd HH:mm:ss.fff',[Globalization.CultureInfo]::InvariantCulture)}
    elseif($value -is [byte[]]){$obj[$name]=[Convert]::ToBase64String($value)}
    elseif($value -is [IFormattable]){$obj[$name]=$value.ToString($null,[Globalization.CultureInfo]::InvariantCulture)}
    else{$obj[$name]=[string]$value}
  }
  return $obj
}
function Post-Chunk($payload){
  $json=$payload | ConvertTo-Json -Depth 15 -Compress
  $body=[Text.Encoding]::UTF8.GetBytes($json)
  if($body.Length -gt 1500000){throw 'Ein Datenpaket ist zu gross. BatchSize reduzieren.'}
  $response=Invoke-RestMethod -UseBasicParsing -Uri $config.endpoint -Method Post -Headers $headers -Body $body -ContentType 'application/json; charset=utf-8' -TimeoutSec 60
  if(-not $response.ok){throw ('Uebertragung fehlgeschlagen: '+($response|ConvertTo-Json -Compress))}
  return $response
}
# Check Plesk endpoint + token + target tables WITHOUT any writes first.
Write-Host 'HW24-Sync: Empfaenger pruefen, ohne Daten zu veraendern ...'
$probe=Post-Chunk @{action='probe'}
if($probe.status -ne 'receiver_ready'){throw 'Empfaenger nicht einsatzbereit'}
Write-Host 'HW24-Sync: API-Empfaenger bestaetigt.'
$connection=[System.Data.SqlClient.SqlConnection]::new($dsn.ConnectionString)
try{
  $connection.Open()
  $command=New-Object System.Data.SqlClient.SqlCommand
  $command.Connection=$connection
  $command.CommandTimeout=120
  $command.CommandText=@'
SELECT * FROM dbo.tArtikel ORDER BY kArtikel;
SELECT * FROM dbo.tArtikelBeschreibung ORDER BY kArtikel, kSprache, kPlattform, kShop;
'@
  $adapter=New-Object -TypeName System.Data.SqlClient.SqlDataAdapter -ArgumentList $command
  $dataset=New-Object System.Data.DataSet
  [void]$adapter.Fill($dataset)
  # Optional safe lookup: use manufacturer names only when the real
  # SQL Server catalog confirms dbo.tHersteller(kHersteller,cName).
  # If the read-only account has no permission, do NOT invent names.
  $manufacturerNames=@{}
  try {
    $metadata=[System.Data.SqlClient.SqlCommand]::new()
    $metadata.Connection=$connection
    $metadata.CommandTimeout=20
    $metadata.CommandText=@'
SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA='dbo' AND TABLE_NAME='tHersteller'
  AND COLUMN_NAME IN ('kHersteller','cName');
'@
    $verified=[int]$metadata.ExecuteScalar()
    if($verified -eq 2){
      $query=[System.Data.SqlClient.SqlCommand]::new()
      $query.Connection=$connection
      $query.CommandTimeout=30
      $query.CommandText='SELECT kHersteller,cName FROM dbo.tHersteller'
      $reader=$query.ExecuteReader()
      try {
        while($reader.Read()){
          if($reader.IsDBNull(0) -or $reader.IsDBNull(1)){continue}
          $key=[string]$reader.GetValue(0)
          $name=([string]$reader.GetValue(1)).Trim()
          if($key -and $name){$manufacturerNames[$key]=$name}
        }
      } finally {$reader.Close()}
      Write-Host ("JTL-Herstellernamen verifiziert: "+$manufacturerNames.Count)
    } else {
      Write-Warning 'Hersteller-Tabelle bzw. Spalten nicht nachgewiesen. Namen bleiben leer, IDs bleiben im Rohdatensatz.'
    }
  } catch {
    Write-Warning 'Hersteller-Namensabfrage nicht möglich (JTL-Schema oder SELECT-Berechtigung prüfen). Keine Namen geraten.'
  }
  # Optional additional article-related sources. Admin mapping must use
  # verified SQL Server table/column names; no guesses and no JTL writes.
  $relatedSources=@{}
  if($config.related_sources_file) {
    . (Join-Path $PSScriptRoot 'HW24_JTL_Zusatzquellen.ps1')
    $relatedSources=Get-HW24RelatedSources -Connection $connection -ArticleTable $dataset.Tables[0] -MapFile ([string]$config.related_sources_file)
    Write-Host ("HW24 Zusatzquellen: "+$relatedSources.Count+" Artikel angereichert")
  }
}finally{
  $connection.Dispose()
}
if($dataset.Tables.Count -lt 2){throw 'Artikel- oder Beschreibungstabelle fehlt'}
$articles=$dataset.Tables[0]
$descriptionTable=$dataset.Tables[1]
$total=$articles.Rows.Count
$minRows=if($config.min_rows){[int]$config.min_rows}else{2500}
if($total -lt $minRows){throw "Sicherheitsabbruch: nur $total Artikel, erwartet mindestens $minRows"}
$descriptions=@{}
$primary=@{}
foreach($row in $descriptionTable.Rows){
  $d=To-DbObject $row
  $id=[string]$d['kArtikel']
  if(-not $descriptions.ContainsKey($id)){$descriptions[$id]=New-Object System.Collections.ArrayList}
  [void]$descriptions[$id].Add($d)
  if([string]$d['kSprache'] -eq '1' -and [string]$d['kPlattform'] -eq '1' -and [string]$d['kShop'] -eq '0'){
    $primary[$id]=$d
  }
}
$run=[Guid]::NewGuid().ToString('N')
Write-Host "JTL-Quelle: $total Artikel, $($descriptionTable.Rows.Count) Beschreibungszeilen. Run-ID: $run"
[void](Post-Chunk @{action='begin';run_id=$run;total=$total})
$size=if($config.batch_size){[int]$config.batch_size}else{15}
if($size -lt 1 -or $size -gt 30){throw 'BatchSize muss zwischen 1 und 30 liegen'}
$batch=New-Object System.Collections.ArrayList
$sent=0
foreach($row in $articles.Rows){
  $a=To-DbObject $row
  $id=[string]$a['kArtikel']
  if($relatedSources.ContainsKey($id)){$a['HW24_RelatedSources']=$relatedSources[$id]}
  $main=if($primary.ContainsKey($id)){$primary[$id]}else{@{}}
  $all=@()
  if($descriptions.ContainsKey($id)){$all=@($descriptions[$id].ToArray())}
  $makerId=[string]$a['kHersteller']
  $manufacturer=$null
  if($makerId -and $manufacturerNames.ContainsKey($makerId)){$manufacturer=$manufacturerNames[$makerId]}
  $record=@{
    jtl_article_id=$id
    artikelnummer=$a['cArtNr']
    ean=$a['cBarcode']
    han=$a['cHAN']
    asin=$a['cASIN']
    artikelname=$main['cName']
    kurzbeschreibung=$main['cKurzBeschreibung']
    beschreibung=$main['cBeschreibung']
    seo_titel=$main['cTitleTag']
    seo_beschreibung=$main['cMetaDescription']
    hersteller=$manufacturer # Only a resolved, read-only manufacturer master row; never ID->name guessing
    netto_vk=$a['fVKNetto']
    netto_ek=$a['fEKNetto']
    letzter_ek=$a['fLetzterEK']
    letzter_ek_datum=$a['dLetzterEK']
    amazon_vk=$a['fAmazonVK']
    ebay_preis=$a['fEbayPreis']
    bestand=$a['nLagerbestand']
    source_modified_at=$a['dMod']
    raw=$a
    descriptions=$all
  }
  [void]$batch.Add($record)
  if($batch.Count -ge $size){
    [void](Post-Chunk @{action='batch';run_id=$run;total=$total;rows=$batch.ToArray()})
    $sent+=$batch.Count
    Write-Progress -Activity 'HW24 JTL-Synchronisation' -Status "$sent / $total" -PercentComplete ([int](100*$sent/$total))
    $batch.Clear()
  }
}
if($batch.Count){
  [void](Post-Chunk @{action='batch';run_id=$run;total=$total;rows=$batch.ToArray()})
  $sent+=$batch.Count
}
if($sent -ne $total){throw "Anzahl unvollstaendig: $sent / $total"}
$done=Post-Chunk @{action='finish';run_id=$run;total=$total}
Write-Host "JTL-Snapshot erfolgreich und atomar freigegeben: $($done.articles) Artikel."
