# HW24: only checks SELECT privileges of the same restricted SQL user.
# No changes to JTL, permissions, credentials, MariaDB or local JSON.
param([string]$ConfigFile='C:\HW24\jtl-sync.json',
      [string]$MapFile='')
$ErrorActionPreference='Stop'
if(-not (Test-Path -LiteralPath $ConfigFile -PathType Leaf)){throw 'jtl-sync.json fehlt'}
$config=Get-Content -LiteralPath $ConfigFile -Raw -Encoding UTF8 | ConvertFrom-Json
if(-not $MapFile){$MapFile=Join-Path (Split-Path -Parent $ConfigFile) 'jtl-related-sources.json'}
if(-not (Test-Path -LiteralPath $MapFile -PathType Leaf)){throw 'jtl-related-sources.json fehlt'}
$map=Get-Content -LiteralPath $MapFile -Raw -Encoding UTF8 | ConvertFrom-Json
$cred=Import-Clixml -LiteralPath $config.sql_credential_file
if(-not ($cred -is [System.Management.Automation.PSCredential])){throw 'Verschluesselte SQL-Anmeldung kann nicht gelesen werden'}
$sb=New-Object System.Data.SqlClient.SqlConnectionStringBuilder
$sb['Data Source']=[string]$config.sql_server
$sb['Initial Catalog']='eazybusiness'
$sb['Integrated Security']=$false
$sb['User ID']=$cred.UserName
$sb['Password']=$cred.GetNetworkCredential().Password
$sb['Encrypt']=$true
$sb['TrustServerCertificate']=$false
$sb['Connect Timeout']=15
$conn=[System.Data.SqlClient.SqlConnection]::new($sb.ConnectionString)
$names=New-Object System.Collections.ArrayList
foreach($entry in @($map.sources)){
 $schema=[string]$entry.schema;$table=[string]$entry.table
 if($schema -notmatch '^[A-Za-z_][A-Za-z0-9_]{0,95}$' -or
    $table -notmatch '^[A-Za-z_][A-Za-z0-9_]{0,95}$'){
   throw 'Ungültige Objektnamen in der privaten Zusatzquellenkonfiguration'
 }
 [void]$names.Add($schema+'.'+$table)
}
if($map.include_amazon_offers -eq $true){
 foreach($obj in @('dbo.pf_amazon_angebot','dbo.pf_amazon_angebot_ext','dbo.pf_amazon_angebot_mapping')){[void]$names.Add($obj)}
}
foreach($obj in @(
 'dbo.vWarenlagerPlatzArtikel','dbo.tWarenLagerPlatz',
 'dbo.tSteuersatz','dbo.tSteuerzone','dbo.tSteuerzoneLand',
 'dbo.tHersteller','dbo.tWarengruppe','dbo.tArtikel','dbo.tArtikelBeschreibung'
)){[void]$names.Add($obj)}
$unique=@($names | Sort-Object -Unique)
$missing=0;$unknown=0;$available=0
try {
 $conn.Open()
 $identity=[System.Data.SqlClient.SqlCommand]::new(
  'SELECT SUSER_SNAME() AS SqlLogin, USER_NAME() AS DatenbankBenutzer',$conn
 )
 $reader=$identity.ExecuteReader()
 $user=''
 try{if($reader.Read()){$user=[string]$reader.GetString(1);Write-Host ('SQL-Login: '+$reader.GetString(0));Write-Host ('Datenbankbenutzer: '+$user)}}
 finally{$reader.Close()}
 Write-Host 'HW24 SELECT-Berechtigungen (kein Datenbank-Update)' -ForegroundColor Cyan
 $cmd=[System.Data.SqlClient.SqlCommand]::new(
  "SELECT HAS_PERMS_BY_NAME(@name, 'OBJECT', 'SELECT')",$conn
 )
 [void]$cmd.Parameters.Add('@name',[System.Data.SqlDbType]::NVarChar,256)
 foreach($name in $unique){
  $cmd.Parameters['@name'].Value=[string]$name
  $value=$cmd.ExecuteScalar()
  if($null -eq $value -or $value -is [DBNull]){
    $unknown++
    Write-Host ('UNBEKANNT: '+$name+' (Objekt nicht sichtbar oder fehlt)') -ForegroundColor Yellow
  }elseif([int]$value -eq 1){
    $available++
    Write-Host ('OK:        '+$name) -ForegroundColor Green
  }else{
    $missing++
    Write-Host ('FEHLT:     '+$name) -ForegroundColor Red
  }
 }
 Write-Host ('Ergebnis: OK='+$available+' / FEHLT='+$missing+' / UNBEKANNT='+$unknown) -ForegroundColor Cyan
 Write-Host 'Fehlende Rechte durch SQL-Administrator gezielt als GRANT SELECT vergeben. Keine Schreibrechte.' -ForegroundColor Cyan
}finally{$conn.Dispose()}
