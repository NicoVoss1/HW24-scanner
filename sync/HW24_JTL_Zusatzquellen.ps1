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
  if($null -eq $settings.sources -or $settings.sources.Count -gt 8){
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
  return ,$output
}
