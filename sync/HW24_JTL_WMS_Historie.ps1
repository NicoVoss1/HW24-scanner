# HW24: separate, read-only JTL WMS history mirror.
# Default: one-article SELECT probe. -Publish transfers a complete snapshot on change
# to the private MariaDB mirror. Does not change JTL data or article snapshot.
param([string]$ConfigFile='C:\HW24\jtl-sync.json',[int]$TestArticle=2594,[switch]$Publish,[switch]$ReceiverProbe)
$ErrorActionPreference='Stop'
if($Publish -and $ReceiverProbe){throw 'Use -Publish OR -ReceiverProbe, not both.'}
[Net.ServicePointManager]::SecurityProtocol=[Net.SecurityProtocolType]::Tls12
$config=Get-Content -LiteralPath $ConfigFile -Raw -Encoding UTF8 | ConvertFrom-Json
$cred=Import-Clixml -LiteralPath $config.sql_credential_file
if(-not ($cred -is [PSCredential])){throw 'SQL credential invalid.'}
$sb=[System.Data.SqlClient.SqlConnectionStringBuilder]::new()
$sb['Data Source']=[string]$config.sql_server
$sb['Initial Catalog']='eazybusiness'
$sb['User ID']=$cred.UserName
$sb['Password']=$cred.GetNetworkCredential().Password
$sb['Encrypt']=$true
$sb['TrustServerCertificate']=$false
$sb['Connect Timeout']=15
$conn=[System.Data.SqlClient.SqlConnection]::new($sb.ConnectionString)
function Read-Value($rd,[int]$i){
 if($rd.IsDBNull($i)){return $null}
 $value=$rd.GetValue($i)
 if($value -is [DateTime]){return $value.ToString('yyyy-MM-dd HH:mm:ss.fff',[Globalization.CultureInfo]::InvariantCulture)}
 if($value -is [IFormattable]){return $value.ToString($null,[Globalization.CultureInfo]::InvariantCulture)}
 return [string]$value
}
if($Publish -or $ReceiverProbe){
 $endpoint=[Uri]::new([Uri]$config.endpoint,'jtl-wms-history.php')
 if($endpoint.Scheme -ne 'https'){throw 'HTTPS required'}
 Write-Host ('HW24 History API: '+$endpoint.GetLeftPart([System.UriPartial]::Path))
 $login=(Import-Clixml -LiteralPath $config.http_credential_file).GetNetworkCredential()
 $secure=ConvertTo-SecureString -String (Get-Content -LiteralPath $config.api_token_file -Raw -Encoding UTF8).Trim()
 $token=([System.Net.NetworkCredential]::new('token',$secure)).Password
 $basic=[Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($login.UserName+':'+$login.Password))
 $headers=@{'Authorization'='Basic '+$basic;'X-HW24-Sync-Token'=$token}
 function Send-History($payload){
  $json=$payload|ConvertTo-Json -Depth 6 -Compress
  $bytes=[Text.Encoding]::UTF8.GetBytes($json)
  if($bytes.Length -gt 1500000){throw 'History package too large'}
  $response=Invoke-RestMethod -UseBasicParsing -Uri $endpoint.AbsoluteUri -Method Post -Headers $headers -Body $bytes -ContentType 'application/json; charset=utf-8' -TimeoutSec 90
  if(-not $response.ok){throw 'History receiver rejected request'}
  return $response
 }
 $ready=Send-History @{action='probe'}
 if($ready.status -ne 'receiver_ready'){throw 'History receiver not ready'}
 Write-Host ('OK: History receiver ready; movement_id type '+$ready.movement_id_type) -ForegroundColor Green
 if($ReceiverProbe){
  if($ready.bounded_retention -ne $true){Write-Warning 'Receiver is still an older version without bounded retention.'}
  else{Write-Host 'OK: Bounded history retention supported.' -ForegroundColor Green}
  $conn.Dispose();return
 }
 if($ready.bounded_retention -ne $true){throw 'Update the WMS history receiver before publishing automatically.'}
}
try{
 $conn.Open()
 $cmd=$conn.CreateCommand()
 $cmd.CommandTimeout=180
 $query=@'
SELECT h.kWarenLagerEingangHistorie,e.kArtikel,h.dZeitstempel,h.kBuchungsArt,
       b.cName,h.fAnzahl,h.kWarenLagerPlatzStart,p1.cName,h.kWarenLagerPlatzZiel,p2.cName,
       COUNT_BIG(*) OVER() AS expected_rows
FROM dbo.tWarenLagerEingangHistorie h
JOIN dbo.tWarenLagerEingang e ON e.kWarenLagerEingang=h.kWarenLagerEingang
LEFT JOIN dbo.tBuchungsArt b ON b.kBuchungsart=h.kBuchungsArt
LEFT JOIN dbo.tWarenLagerPlatz p1 ON p1.kWarenLagerPlatz=h.kWarenLagerPlatzStart
LEFT JOIN dbo.tWarenLagerPlatz p2 ON p2.kWarenLagerPlatz=h.kWarenLagerPlatzZiel
'@
 if(-not $Publish){
  $query+=" WHERE e.kArtikel=@article"
  [void]$cmd.Parameters.Add('@article',[System.Data.SqlDbType]::Int)
  $cmd.Parameters['@article'].Value=$TestArticle
 }
 $cmd.CommandText=$query+' ORDER BY h.kWarenLagerEingangHistorie'
 $rd=$cmd.ExecuteReader()
 $seen=0;$expected=0
 $run=[Guid]::NewGuid().ToString('N')
 $allRows=New-Object System.Collections.ArrayList
 $digest=[System.Security.Cryptography.SHA256]::Create()
 try{
  while($rd.Read()){
   $expected=[long]$rd.GetValue(10)
   $row=[ordered]@{bewegung_id=(Read-Value $rd 0);artikel_id=(Read-Value $rd 1);zeitstempel=(Read-Value $rd 2);
    buchungsart_id=(Read-Value $rd 3);buchungsart=(Read-Value $rd 4);menge=(Read-Value $rd 5);
    start_id=(Read-Value $rd 6);lagerplatz_von=(Read-Value $rd 7);ziel_id=(Read-Value $rd 8);lagerplatz_nach=(Read-Value $rd 9)}
   $seen++
   if($Publish){
    [void]$allRows.Add($row)
    $line=[Text.Encoding]::UTF8.GetBytes(($row|ConvertTo-Json -Compress)+"`n")
    [void]$digest.TransformBlock($line,0,$line.Length,$line,0)
   }else{
    $row|ConvertTo-Json -Compress|Write-Host
   }
  }
 }finally{$rd.Close()}
 if($seen -ne $expected){throw "Incomplete history read: $seen of $expected"}
 if($Publish){
  [void]$digest.TransformFinalBlock([byte[]]@(),0,0)
  $hash=([BitConverter]::ToString($digest.Hash)).Replace('-','').ToLowerInvariant()
  $statePath=Join-Path (Split-Path -Parent $ConfigFile) 'wms-history-state.json'
  $previous=$null
  if(Test-Path -LiteralPath $statePath -PathType Leaf){
    try{$previous=Get-Content -LiteralPath $statePath -Raw -Encoding UTF8 | ConvertFrom-Json}catch{}
  }
  if($previous -and $previous.hash -eq $hash -and $previous.run_id -eq $ready.latest_run_id){
   Write-Host "OK: History unchanged: $seen movements; no upload needed." -ForegroundColor Green
   return
  }
  if($seen -eq 0 -and $ready.latest_run_id){throw 'Unexpected empty source; previous published history preserved.'}
  [void](Send-History @{action='begin';run_id=$run})
  for($offset=0;$offset -lt $allRows.Count;$offset+=250){
   $n=[Math]::Min(250,$allRows.Count-$offset)
   $batch=$allRows.GetRange($offset,$n).ToArray()
   $response=Send-History @{action='batch';run_id=$run;rows=$batch}
   if($response.accepted -ne $n){throw 'Incomplete batch'}
   Write-Progress -Activity 'WMS history' -Status "$($offset+$n) / $expected"
  }
  $done=Send-History @{action='finish';run_id=$run;total=$seen}
  if($done.status -ne 'ready' -or $done.rows -ne $seen){throw 'History not ready'}
  Write-Host "OK: History snapshot ready: $seen joined movements, no date filter." -ForegroundColor Green
  if($done.cleanup_ok -ne $true){throw 'Snapshot published, but mirror cleanup failed. Check DELETE grants on the two history tables before repeating.'}
  @{hash=$hash;run_id=$run;rows=$seen}|ConvertTo-Json -Compress|Set-Content -LiteralPath $statePath -Encoding UTF8
  Write-Host "OK: Mirror retention checked; $($done.pruned_runs) old/abandoned runs removed." -ForegroundColor Green
 }else{
  Write-Host "OK: Article $TestArticle / $seen joined movements, no date filter. No data sent." -ForegroundColor Green
 }
}finally{if($digest){$digest.Dispose()};$conn.Dispose()}
