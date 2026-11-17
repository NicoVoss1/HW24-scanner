# HW24: separate, read-only JTL WMS history mirror.
# Default: one-article SELECT probe. -Publish explicitly transfers ALL joined rows
# to the private MariaDB mirror. Does not change JTL data or article snapshot.
param([string]$ConfigFile='C:\HW24\jtl-sync.json',[int]$TestArticle=2594,[switch]$Publish)
$ErrorActionPreference='Stop'
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
if($Publish){
 $endpoint=[Uri]::new([Uri]$config.endpoint,'jtl-wms-history.php')
 if($endpoint.Scheme -ne 'https'){throw 'HTTPS required'}
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
 $batch=New-Object System.Collections.ArrayList
 if($Publish){[void](Send-History @{action='begin';run_id=$run})}
 try{
  while($rd.Read()){
   $expected=[long]$rd.GetValue(10)
   $row=@{bewegung_id=(Read-Value $rd 0);artikel_id=(Read-Value $rd 1);zeitstempel=(Read-Value $rd 2);
    buchungsart_id=(Read-Value $rd 3);buchungsart=(Read-Value $rd 4);menge=(Read-Value $rd 5);
    start_id=(Read-Value $rd 6);lagerplatz_von=(Read-Value $rd 7);ziel_id=(Read-Value $rd 8);lagerplatz_nach=(Read-Value $rd 9)}
   $seen++
   if($Publish){
    [void]$batch.Add($row)
    if($batch.Count -ge 250){
     $response=Send-History @{action='batch';run_id=$run;rows=$batch.ToArray()}
     if($response.accepted -ne $batch.Count){throw 'Incomplete batch'}
     $batch.Clear()
     Write-Progress -Activity 'WMS history' -Status "$seen / $expected"
    }
   }else{
    $row|ConvertTo-Json -Compress|Write-Host
   }
  }
 }finally{$rd.Close()}
 if($seen -ne $expected){throw "Incomplete history read: $seen of $expected"}
 if($Publish){
  if($batch.Count){
   $response=Send-History @{action='batch';run_id=$run;rows=$batch.ToArray()}
   if($response.accepted -ne $batch.Count){throw 'Incomplete final batch'}
  }
  $done=Send-History @{action='finish';run_id=$run;total=$seen}
  if($done.status -ne 'ready' -or $done.rows -ne $seen){throw 'History not ready'}
  Write-Host "OK: History snapshot ready: $seen joined movements, no date filter." -ForegroundColor Green
 }else{
  Write-Host "OK: Article $TestArticle / $seen joined movements, no date filter. No data sent." -ForegroundColor Green
 }
}finally{$conn.Dispose()}
