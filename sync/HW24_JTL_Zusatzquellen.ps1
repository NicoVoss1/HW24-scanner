# HW24 · verified read-only related JTL article sources (PowerShell 5.1 compatible)
# Dot-source from HW24_JTL_Synchronisieren.ps1 after tArtikel has been read.
# No source is enabled unless it is explicitly mapped in an admin-managed
# LOCAL JSON configuration file and its real SQL schema has been validated.
function Get-HW24RelatedSources {
  param(
    [Parameter(Mandatory=$true)][System.Data.SqlClient.SqlConnection]$Connection,
    [Parameter(Mandatory=$true)][System.Data.DataTable]$ArticleTable,
    [Parameter(Mandatory=$true)][string]$MapFile
  )
  $ErrorActionPreference='Stop'
  if(-not (Test-Path -LiteralPath $MapFile -PathType Leaf)){ throw 'Related-source config file not found' }
  $settings=Get-Content -LiteralPath $MapFile -Raw -Encoding UTF8 | ConvertFrom-Json
  if($null -eq $settings.sources -or $settings.sources.Count -gt 12){
    throw 'Related-source mapping requires a sources list with at most 8 sources'
  }
  $output=@{}
  $validateName='^[A-Za-z_][A-Za-z0-9_]{0,95}$'
  $seenSourceIds=@{}
  foreach($entry in @($settings.sources)) {
    $sourceId=[string]$entry.id
    $schema=[string]$entry.schema
    $table=[string]$entry.table
    $tableColumn=[string]$entry.table_column
    $articleColumn=[string]$entry.article_column
    $fields=@($entry.fields)
    if($sourceId -notmatch '^[a-z][a-z0-9_]{0,40}$' -or
       $schema -cnotmatch $validateName -or $table -cnotmatch $validateName -or
       $tableColumn -cnotmatch $validateName -or
       $articleColumn -cnotmatch $validateName -or
       $fields.Count -lt 1 -or $fields.Count -gt 6 -or
       $seenSourceIds.ContainsKey($sourceId)) {
      throw ('Invalid related-source descriptor: '+$sourceId)
    }
    $seenSourceIds[$sourceId]=$true
    foreach($f in $fields){
      $key=[string]$f
      if($key -cnotmatch $validateName -or $key -match '(?i)password|secret|token|hash|salt|credential|bank|iban|kunde|email|telefon|address|adresse') {
        throw ('Unsafe or unknown field name in source '+$sourceId)
      }
    }
    if(-not $ArticleTable.Columns.Contains($articleColumn)){
      throw ('Article source field not found: '+$articleColumn)
    }
    # Verify schema against SQL Server's actual accessible catalog.
    $columns=@($tableColumn)+$fields
    $check=[System.Data.SqlClient.SqlCommand]::new()
    $check.Connection=$Connection
    $check.CommandTimeout=15
    $check.CommandText='SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@s AND TABLE_NAME=@t'
    [void]$check.Parameters.AddWithValue('@s',$schema)
    [void]$check.Parameters.AddWithValue('@t',$table)
    $reader=$check.ExecuteReader()
    $available=@{}
    try {
      while($reader.Read()){ $available[[string]$reader.GetString(0)]=$true }
    } finally { $reader.Close() }
    foreach($col in $columns) {
      if(-not $available.ContainsKey([string]$col)){ throw ('Related-source field not verified: '+$sourceId+'.'+$col) }
    }
    $identifiers=@{}
    $sourceKeys=@{}
    foreach($a in $ArticleTable.Rows){
      $aid=[string]$a['kArtikel']
      $link=[string]$a[$articleColumn]
      if(-not $aid -or -not $link){continue}
      if(-not $identifiers.ContainsKey($link)){$identifiers[$link]=New-Object System.Collections.ArrayList}
      [void]$identifiers[$link].Add($aid)
      $sourceKeys[$link]=$true
    }
    $keys=@($sourceKeys.Keys)
    $found=@{}
    $truncated=@{}
    for($offset=0;$offset -lt $keys.Count;$offset+=100){
      $last=[Math]::Min($offset+99,$keys.Count-1)
      $part=@($keys[$offset..$last])
      $cmd=[System.Data.SqlClient.SqlCommand]::new()
      $cmd.Connection=$Connection
      $cmd.CommandTimeout=75
      $quoted=@($columns | ForEach-Object { '['+$_+']' })
      $params=New-Object System.Collections.ArrayList
      for($i=0;$i -lt $part.Count;$i++){
        $p='@key'+$i
        [void]$params.Add($p)
        [void]$cmd.Parameters.AddWithValue($p,[string]$part[$i])
      }
      $cmd.CommandText='SELECT '+($quoted -join ',')+' FROM ['+$schema+'].['+$table+'] WHERE ['+$tableColumn+'] IN ('+($params -join ',')+')'
      $rd=$cmd.ExecuteReader()
      try {
        while($rd.Read()){
          if($rd.IsDBNull(0)){continue}
          $key=[string]$rd.GetValue(0)
          if(-not $identifiers.ContainsKey($key)){continue}
          if(-not $found.ContainsKey($key)){$found[$key]=New-Object System.Collections.ArrayList}
          if($found[$key].Count -ge 3){$truncated[$key]=$true;continue}
          $record=@{}
          for($j=0;$j -lt $fields.Count;$j++){
            $column=[string]$fields[$j]
            if($rd.IsDBNull($j+1)){continue}
            $str=[string]$rd.GetValue($j+1)
            if($str.Length -gt 200){$str=$str.Substring(0,200)}
            $record[$column]=$str
          }
          [void]$found[$key].Add($record)
        }
      } finally {$rd.Close()}
    }
    foreach($link in $found.Keys){
      foreach($aid in $identifiers[$link]){
        if(-not $output.ContainsKey($aid)){$output[$aid]=@{}}
        $output[$aid][$sourceId]=@{
          source='JTL-Wawi SQL Server'
          table=$schema+'.'+$table
          join=$articleColumn+' -> '+$tableColumn
          rows=@($found[$link].ToArray())
          truncated=[bool]$truncated.ContainsKey($link)
        }
      }
    }
    Write-Host ('HW24 Zusatzquelle '+$sourceId+' verifiziert: '+$schema+'.'+$table+', '+$found.Count+' Schluessel mit Treffern')
  }

  # One article can have multiple Amazon offers. Match on verified composite
  # seller SKU + seller user, and join configuration by platform as well.
  # An Amazon offer must NEVER silently overwrite the JTL article's standard price.
  if($settings.include_amazon_offers -eq $true) {
    $required=@{
      'dbo.pf_amazon_angebot_mapping'=@('kArtikel','cSellerSKU','kUser');
      'dbo.pf_amazon_angebot'=@('cSellerSKU','kUser','nPlattform','cASIN1','fPrice','nQuantity','cListingId','cFulfillmentChannel');
      'dbo.pf_amazon_angebot_ext'=@('cSellerSKU','kUser','nPlattform','fPreis','nMaxBestand','nB2BPreiseSenden')
    }
    foreach($tableId in $required.Keys){
      $parts=$tableId.Split('.')
      $cmd=[System.Data.SqlClient.SqlCommand]::new()
      $cmd.Connection=$Connection;$cmd.CommandTimeout=20
      $cmd.CommandText='SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME=@table'
      [void]$cmd.Parameters.AddWithValue('@schema',$parts[0])
      [void]$cmd.Parameters.AddWithValue('@table',$parts[1])
      $reader=$cmd.ExecuteReader()
      $available=@{}
      try{while($reader.Read()){$available[[string]$reader.GetString(0)]=$true}}
      finally{$reader.Close()}
      foreach($requiredField in $required[$tableId]){
        if(-not $available.ContainsKey([string]$requiredField)){
          throw ('Amazon field not found in SQL catalog: '+$tableId+'.'+$requiredField)
        }
      }
    }
    $ids=New-Object System.Collections.ArrayList
    foreach($row in $ArticleTable.Rows){
      if(-not $row.IsNull('kArtikel')){[void]$ids.Add([int]$row['kArtikel'])}
    }
    $offerRows=@{}
    $offerTooMany=@{}
    for($offset=0;$offset -lt $ids.Count;$offset+=100){
      $endOffset=[Math]::Min($offset+99,$ids.Count-1)
      $part=@($ids[$offset..$endOffset])
      $command=[System.Data.SqlClient.SqlCommand]::new()
      $command.Connection=$Connection
      $command.CommandTimeout=90
      $parameters=New-Object System.Collections.ArrayList
      for($i=0;$i -lt $part.Count;$i++){
        $param='@article'+$i
        [void]$parameters.Add($param)
        [void]$command.Parameters.Add($param,[System.Data.SqlDbType]::Int)
        $command.Parameters[$param].Value=[int]$part[$i]
      }
      $queryStart=@'
SELECT m.kArtikel,m.cSellerSKU,a.nPlattform,a.cASIN1,a.fPrice,
       a.nQuantity,a.cListingId,a.cFulfillmentChannel,
       e.fPreis AS fPreisKonfig,e.nMaxBestand,e.nB2BPreiseSenden
FROM dbo.pf_amazon_angebot_mapping AS m
JOIN dbo.pf_amazon_angebot AS a
  ON a.cSellerSKU=m.cSellerSKU AND a.kUser=m.kUser
LEFT JOIN dbo.pf_amazon_angebot_ext AS e
  ON e.cSellerSKU=a.cSellerSKU AND e.kUser=a.kUser
  AND e.nPlattform=a.nPlattform
WHERE m.kArtikel IN (
'@
      $queryEnd=@'
)
ORDER BY m.kArtikel,a.nPlattform,m.cSellerSKU
'@
      $command.CommandText=$queryStart+($parameters -join ',')+$queryEnd
      $rd=$command.ExecuteReader()
      try{
        while($rd.Read()){
          $id=[string]$rd.GetValue(0)
          if(-not $offerRows.ContainsKey($id)){$offerRows[$id]=New-Object System.Collections.ArrayList}
          if($offerRows[$id].Count -ge 5){$offerTooMany[$id]=$true;continue}
          $columns=@('seller_sku','plattform','asin','preis_angebot','menge_angebot','listing_id',
                     'fulfillment','preis_konfig','max_bestand','b2b_preise_aktiv')
          $item=@{}
          for($j=0;$j -lt $columns.Count;$j++){
            if($rd.IsDBNull($j+1)){continue}
            $object=$rd.GetValue($j+1)
            if($object -is [DateTime]){
              $value=$object.ToString('yyyy-MM-dd HH:mm:ss',[Globalization.CultureInfo]::InvariantCulture)
            } elseif($object -is [IFormattable]){
              $value=$object.ToString($null,[Globalization.CultureInfo]::InvariantCulture)
            } else {$value=[string]$object}
            if($value.Length -gt 200){$value=$value.Substring(0,200)}
            $item[$columns[$j]]=$value
          }
          [void]$offerRows[$id].Add($item)
        }
      } finally {$rd.Close()}
    }
    foreach($id in $offerRows.Keys){
      if(-not $output.ContainsKey($id)){$output[$id]=@{}}
      $output[$id]['amazon_angebote'] = @{
        source='JTL-Wawi SQL Server'
        table='dbo.pf_amazon_angebot_mapping -> dbo.pf_amazon_angebot / dbo.pf_amazon_angebot_ext'
        join='kArtikel + cSellerSKU + kUser + nPlattform'
        rows=@($offerRows[$id].ToArray())
        truncated=[bool]$offerTooMany.ContainsKey($id)
      }
    }
    Write-Host ('HW24 Amazon-Angebote: '+$offerRows.Count+' Artikel mit konkreter SKU-Zuordnung')
  }
  return ,$output
}
