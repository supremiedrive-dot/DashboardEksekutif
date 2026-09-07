param([string]$Root = (Get-Location).Path)
Add-Type -AssemblyName System.IO.Compression.FileSystem
function Read-Xml($z,$p) { $e=$z.GetEntry($p); if(!$e){return $null}; $r=New-Object IO.StreamReader($e.Open()); try { [xml]$r.ReadToEnd() } finally {$r.Dispose()} }
$result=@()
foreach($file in Get-ChildItem -LiteralPath "$Root/data/source" -Filter *.xlsx){
 $z=[IO.Compression.ZipFile]::OpenRead($file.FullName)
 try {
 $wb=Read-Xml $z 'xl/workbook.xml'; $rels=Read-Xml $z 'xl/_rels/workbook.xml.rels'; $ss=Read-Xml $z 'xl/sharedStrings.xml'; $strings=@(); if($ss){foreach($si in $ss.sst.si){$strings+= (($si.SelectNodes('.//*[local-name()="t"]') | ForEach-Object {$_.InnerText}) -join '')}}
 $styles=Read-Xml $z 'xl/styles.xml'; $fmts=@{}; foreach($fmt in $styles.styleSheet.numFmts.numFmt){$fmts[[string]$fmt.numFmtId]=[string]$fmt.formatCode}; $styleList=@($styles.styleSheet.cellXfs.xf); $sheets=@(); foreach($s in $wb.workbook.sheets.sheet){$rid=$s.GetAttribute('id','http://schemas.openxmlformats.org/officeDocument/2006/relationships'); $target=($rels.Relationships.Relationship | Where-Object {$_.Id -eq $rid}).Target; if($target.StartsWith('/')){$path=$target.TrimStart('/')}else{$path='xl/'+$target}; $x=Read-Xml $z $path; $cells=@(); foreach($c in $x.worksheet.sheetData.row.c){$value=[string]$c.v; if($c.t -eq 's'){$value=$strings[[int]$value]}elseif($c.t -eq 'inlineStr'){$value=($c.SelectNodes('.//*[local-name()="t"]') | ForEach-Object {$_.InnerText}) -join ''}; if($value -ne '' -or $c.f){$cells += [PSCustomObject]@{ref=[string]$c.r;type=[string]$c.t;style=[string]$c.s;numFmtId=[string]$styleList[[int]$c.s].numFmtId;formatCode=[string]$fmts[[string]$styleList[[int]$c.s].numFmtId];value=$value;formula=$(if($c.f -is [System.Xml.XmlElement]){$c.f.InnerText}else{[string]$c.f});formulaType=$(if($c.f -is [System.Xml.XmlElement]){$c.f.GetAttribute('t')}else{''});sharedIndex=$(if($c.f -is [System.Xml.XmlElement]){$c.f.GetAttribute('si')}else{''})}} }; $sheets += [PSCustomObject]@{name=[string]$s.name;dimension=[string]$x.worksheet.dimension.ref;merges=@($x.worksheet.mergeCells.mergeCell | ForEach-Object {$_.ref});cells=$cells}}
 $result += [PSCustomObject]@{file=$file.Name;sha256=(Get-FileHash -LiteralPath $file.FullName).Hash;sheets=$sheets}
 } finally {$z.Dispose()}
}
$result | ConvertTo-Json -Depth 9 | Set-Content -Encoding UTF8 "$Root/docs/00-workbook-evidence.json"
foreach($w in $result){"WORKBOOK: $($w.file)";foreach($s in $w.sheets){"SHEET: $($s.name) dimension=$($s.dimension) merges=$($s.merges -join ',')"; if($w.file -like 'Kamus*'){ $s.cells | Group-Object {$_.ref -replace '[A-Z]',''} | ForEach-Object {($_.Group | ForEach-Object {"$($_.ref)=$($_.value)"}) -join ' | '}}else{$s.cells | Where-Object { [int]($_.ref -replace '[A-Z]','') -le 6 } | ForEach-Object {"$($_.ref)=$($_.value) formula=$($_.formula)"}; 'REGIONS:'; $s.cells | Where-Object {$_.ref -match '^B\d+$'} | ForEach-Object {"$($_.ref)=$($_.value)"}}}}


