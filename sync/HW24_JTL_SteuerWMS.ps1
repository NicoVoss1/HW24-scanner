# HW24 · verified read-only tax / WMS article enrichments.
# This file executes only when include_wms_bins/include_tax_de are true
# in the LOCAL Windows source-map configuration. Never writes to JTL.
function Get-HW24TaxAndBinSources {
  param(
    [Parameter(Mandatory=$true)][System.Data.SqlClient.SqlConnection]$Connection,
    [Parameter(Mandatory=$true)][System.Data.DataTable]$ArticleTable,
    [bool]$IncludeBins=$false,
    [bool]$IncludeTax=$false
  )
  $ErrorActionPreference='Stop'
  $result=@{}
  $articleIDs=New-Object System.Collections.ArrayList
  $taxClassByArticle=@{}
  foreach($a in $ArticleTable.Rows){
    if($a.IsNull('kArtikel')){continue}
    $id=[string]$a['kArtikel']
    [void]$articleIDs.Add([int]$a['kArtikel'])
    if(-not $a.IsNull('kSteuerklasse')){$taxClassByArticle[$id]=[string]$a['kSteuerklasse']}
  }
  function Assert-Columns($table,$columns){
    # SQL Server can return zero rows from INFORMATION_SCHEMA for accessible
    # JTL views. Validate the actual readable view schema with TOP (0).
    if($table -notmatch '^[A-Za-z_][A-Za-z0-9_]{0,95}\.[A-Za-z_][A-Za-z0-9_]{0,95}$'){
      throw ('Ungueltiger JTL-Objektname: '+$table)
    }
    $parts=$table.Split('.')
    $cmd=[System.Data.SqlClient.SqlCommand]::new()
    $cmd.Connection=$Connection
    $cmd.CommandTimeout=20
    $cmd.CommandText='SELECT TOP (0) * FROM ['+$parts[0]+'].['+$parts[1]+']'
    $reader=$null
    $names=@{}
    try {
      $reader=$cmd.ExecuteReader([System.Data.CommandBehavior]::SchemaOnly)
      for($idx=0;$idx -lt $reader.FieldCount;$idx++){
        $names[[string]$reader.GetName($idx)]=$true
      }
    } catch {
      throw ('JTL-Objekt '+$table+' nicht lesbar mit dem SQL-Sync-Konto: '+$_.Exception.Message)
    } finally {
      if($null -ne $reader){$reader.Close()}
    }
    $missing=@($columns | Where-Object {-not $names.ContainsKey([string]$_)})
    if($missing.Count -gt 0){
      throw ('Nicht verifizierte JTL-Spalten in '+$table+': '+($missing -join ', ')+
       '. Vorhandene Spalten: '+(@($names.Keys | Sort-Object) -join ', '))
    }
  }
  if($IncludeBins){
    Assert-Columns 'dbo.vWarenlagerPlatzArtikel' @('kArtikel','kWarenLagerPlatz','fAnzahl')
    Assert-Columns 'dbo.tWarenLagerPlatz' @('kWarenLagerPlatz','kWarenLager','cName','nGesperrt','nAuslieferungGesperrt','kWarenLagerPlatzTyp','nStatus','dWmsInventurDatum','kWmsInventur','nPreInvStatus','nInvGezaehlt','cKommentar')
    foreach($id in $articleIDs){
      $key=[string]$id
      if(-not $result.ContainsKey($key)){$result[$key]=@{}}
      $result[$key]['wms_lagerplaetze']=@{
        source='JTL-Wawi SQL Server'
        table='dbo.vWarenlagerPlatzArtikel -> dbo.tWarenLagerPlatz'
        join='kArtikel + kWarenLagerPlatz'
        confirmed=$true
        rows=@()
        truncated=$false
      }
    }
    for($offset=0;$offset -lt $articleIDs.Count;$offset+=100){
      $last=[Math]::Min($offset+99,$articleIDs.Count-1)
      $chunk=@($articleIDs[$offset..$last])
      $cmd=[System.Data.SqlClient.SqlCommand]::new()
      $cmd.Connection=$Connection
      $cmd.CommandTimeout=75
      $p=New-Object System.Collections.ArrayList
      for($i=0;$i -lt $chunk.Count;$i++){
        $name='@a'+$i
        [void]$p.Add($name)
        [void]$cmd.Parameters.Add($name,[System.Data.SqlDbType]::Int)
        $cmd.Parameters[$name].Value=[int]$chunk[$i]
      }
      $queryStart=@'
SELECT p.kArtikel,p.kWarenLagerPlatz,p.fAnzahl,
       l.cName,l.kWarenLager,l.nGesperrt,l.nAuslieferungGesperrt,
       l.kWarenLagerPlatzTyp,l.nStatus,l.dWmsInventurDatum,
       l.kWmsInventur,l.nPreInvStatus,l.nInvGezaehlt,l.cKommentar
FROM dbo.vWarenlagerPlatzArtikel AS p
JOIN dbo.tWarenLagerPlatz AS l ON l.kWarenLagerPlatz=p.kWarenLagerPlatz
WHERE p.kArtikel IN (
'@
      $queryEnd=@'
)
ORDER BY p.kArtikel,l.kWarenLager,l.cName,p.kWarenLagerPlatz
'@
      $cmd.CommandText=$queryStart+($p -join ',')+$queryEnd
      $rd=$cmd.ExecuteReader()
      try{
        while($rd.Read()){
          $key=[string]$rd.GetValue(0)
          if(-not $result.ContainsKey($key)){continue}
          $entry=$result[$key]['wms_lagerplaetze']
          $rows=@($entry['rows'])
          if($rows.Count -ge 100){$entry['truncated']=$true;continue}
          $amount=$rd.GetValue(2)
          $quantity=([IFormattable]$amount).ToString($null,[Globalization.CultureInfo]::InvariantCulture)
          $binID=[string]$rd.GetValue(1)
          $name=if($rd.IsDBNull(3)){$binID}else{([string]$rd.GetValue(3)).Trim()}
          $rows+=@{
            lagerplatz_id=$binID
            lagerplatz=$name
            menge=$quantity
            warenlager_id=[string]$rd.GetValue(4)
            gesperrt=if($rd.IsDBNull(5)){$null}else{[string]$rd.GetValue(5)}
            auslieferung_gesperrt=if($rd.IsDBNull(6)){$null}else{[string]$rd.GetValue(6)}
            lagerplatztyp_id=if($rd.IsDBNull(7)){$null}else{[string]$rd.GetValue(7)}
            status_id=if($rd.IsDBNull(8)){$null}else{[string]$rd.GetValue(8)}
            inventur_datum=if($rd.IsDBNull(9)){$null}else{([DateTime]$rd.GetValue(9)).ToString('yyyy-MM-dd HH:mm:ss',[Globalization.CultureInfo]::InvariantCulture)}
            inventur_id=if($rd.IsDBNull(10)){$null}else{[string]$rd.GetValue(10)}
            vorinventur_status_id=if($rd.IsDBNull(11)){$null}else{[string]$rd.GetValue(11)}
            inventur_gezaehlt=if($rd.IsDBNull(12)){$null}else{[string]$rd.GetValue(12)}
            lagerplatz_hinweis=if($rd.IsDBNull(13)){$null}else{([string]$rd.GetValue(13)).Substring(0,[Math]::Min(160,([string]$rd.GetValue(13)).Length))}

          }
          $entry['rows']=$rows
        }
      }finally{$rd.Close()}
    }
    Write-Host ('HW24 WMS-Fachspiegel vorbereitet: '+$articleIDs.Count+' Artikel; Fachmengen pro Artikel bis 100 Zeilen')
  }
  if($IncludeTax){
    Assert-Columns 'dbo.tSteuersatz' @('kSteuerklasse','kSteuerzone','fSteuersatz','nPrio')
    Assert-Columns 'dbo.tSteuerzone' @('kSteuerzone','kFirma','cName')
    Assert-Columns 'dbo.tSteuerzoneLand' @('kSteuerzone','cISO')
    $cmd=[System.Data.SqlClient.SqlCommand]::new()
    $cmd.Connection=$Connection
    $cmd.CommandTimeout=45
    $cmd.CommandText=@'
SELECT DISTINCT s.kSteuerklasse,z.kSteuerzone,z.kFirma,z.cName,
       s.fSteuersatz,s.nPrio
FROM dbo.tSteuersatz AS s
JOIN dbo.tSteuerzone AS z ON z.kSteuerzone=s.kSteuerzone
JOIN dbo.tSteuerzoneLand AS zl ON zl.kSteuerzone=s.kSteuerzone
WHERE zl.cISO=@land AND s.kSteuerklasse IS NOT NULL
ORDER BY s.kSteuerklasse,z.kFirma,z.kSteuerzone,s.nPrio
'@
    [void]$cmd.Parameters.Add('@land',[System.Data.SqlDbType]::NVarChar,2)
    $cmd.Parameters['@land'].Value='DE'
    $rd=$cmd.ExecuteReader()
    $rates=@{}
    try{
      while($rd.Read()){
        $class=[string]$rd.GetValue(0)
        if(-not $rates.ContainsKey($class)){$rates[$class]=New-Object System.Collections.ArrayList}
        if($rates[$class].Count -ge 50){throw ('Zu viele deutsche Steuerzonensaetze in Klasse '+$class)}
        $rate=$rd.GetValue(4)
        $value=([IFormattable]$rate).ToString($null,[Globalization.CultureInfo]::InvariantCulture)
        [void]$rates[$class].Add(@{
          steuerklasse_id=$class
          steuerzone_id=[string]$rd.GetValue(1)
          firma_id=[string]$rd.GetValue(2)
          steuerzone=if($rd.IsDBNull(3)){$null}else{[string]$rd.GetValue(3)}
          steuersatz_quellwert=$value
          prioritaet=if($rd.IsDBNull(5)){$null}else{[string]$rd.GetValue(5)}
          zielland='DE'
        })
      }
    }finally{$rd.Close()}
    foreach($id in $articleIDs){
      $key=[string]$id
      $class=$taxClassByArticle[$key]
      if(-not $result.ContainsKey($key)){$result[$key]=@{}}
      $values=if($class -and $rates.ContainsKey($class)){@($rates[$class].ToArray())}else{@()}
      $result[$key]['steuer_saetze_de']=@{
        source='JTL-Wawi SQL Server'
        table='dbo.tSteuersatz + dbo.tSteuerzone + dbo.tSteuerzoneLand'
        join='kSteuerklasse + kSteuerzone + kFirma + Land=DE'
        confirmed=$true
        rows=$values
        truncated=$false
        note='Konfigurationswerte je Firma/Steuerzone. Ohne Firm-/Steuerkontext und Wertskalierung kein pauschaler 19-Prozent-Artikelwert.'
      }
    }
    Write-Host ('HW24 Steuerkonfiguration DE gelesen: '+$rates.Count+' Steuerklassen; keine pauschale Prozentsatz-Festlegung')
  }
  return ,$result
}
