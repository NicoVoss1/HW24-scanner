# Run ONCE on the Windows host under the account that will later run the scheduled sync.
# Never store passwords or tokens in GitHub. DPAPI secrets are bound to this account.
param([string]$BaseDirectory='C:\HW24')
$ErrorActionPreference='Stop'
$secretFolder=Join-Path $BaseDirectory 'secrets'
New-Item -ItemType Directory -Force -Path $secretFolder | Out-Null
Write-Host 'HW24 einmalige Zugangseinrichtung. Bitte niemals das SQL-Administratorkonto sa verwenden.'
$jtl=Get-Credential -Message 'JTL SQL-Login mit SELECT auf tArtikel und tArtikelBeschreibung'
$http=Get-Credential -Message 'Plesk Passwortschutz fuer artikelmanager.hairworld24.de'
$jtlPath=Join-Path $secretFolder 'jtl_reader.xml'
$httpPath=Join-Path $secretFolder 'plesk_http.xml'
$jtl | Export-Clixml -LiteralPath $jtlPath
$http | Export-Clixml -LiteralPath $httpPath
$bytes=New-Object byte[] 48
$rng=[Security.Cryptography.RandomNumberGenerator]::Create()
try{$rng.GetBytes($bytes)}finally{$rng.Dispose()}
$token=[Convert]::ToBase64String($bytes)
$secure=ConvertTo-SecureString -String $token -AsPlainText -Force
$tokenPath=Join-Path $secretFolder 'api_token.dpapi'
$secure | ConvertFrom-SecureString | Set-Content -LiteralPath $tokenPath -Encoding UTF8
$sha=[Security.Cryptography.SHA256]::Create()
try{$digest=$sha.ComputeHash([Text.Encoding]::UTF8.GetBytes($token))}finally{$sha.Dispose()}
$hash=[BitConverter]::ToString($digest).Replace('-','').ToLowerInvariant()
$configPath=Join-Path $BaseDirectory 'jtl-sync.json'
$config=@{
 endpoint='https://artikelmanager.hairworld24.de/api/jtl-sync.php'
 sql_server='192.168.178.28,1433'
 sql_credential_file=$jtlPath
 http_credential_file=$httpPath
 api_token_file=$tokenPath
 batch_size=15
 min_rows=2500
}
$config | ConvertTo-Json | Set-Content -LiteralPath $configPath -Encoding UTF8
Write-Host ''
Write-Host 'FERTIG: verschluesselte Anmeldedaten und Konfiguration lokal gespeichert.'
Write-Host ('Konfigurationspfad: '+$configPath)
Write-Host ('SHA256 Token fuer private Plesk-Datei jtl_sync.php: '+$hash)
Write-Host ''
Write-Host 'WICHTIG: Der SHA256-Hash darf in Plesk konfiguriert werden, das eigentliche Token bleibt geheim.'
Write-Host 'Nur unter DEMSELBEN Windows-Benutzer ist die DPAPI-Entschluesselung moeglich.'
Write-Host 'Datei- und Ordnerberechtigungen auf C:\HW24\secrets pruefen.'
Write-Host 'SQL TLS-Zertifikat pruefen; Script noch NICHT als automatischen Task aktivieren.'
