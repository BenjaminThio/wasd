# Packages the submission folder for upload.
#
# Refuses to run until the PDF report exists, because the brief asks for the
# report in PDF format and a zip that is missing it looks complete but is not.

$ErrorActionPreference = 'Stop'

$folder = Join-Path $PSScriptRoot 'UECS2194_Assignment_P1_G1'
$zip    = Join-Path $PSScriptRoot 'UECS2194_Assignment_P1_G1.zip'
$pdf    = Join-Path $folder 'WASD_Assignment_Report.pdf'

if (-not (Test-Path $folder)) {
    throw "Submission folder not found: $folder"
}

if (-not (Test-Path $pdf)) {
    Write-Host ''
    Write-Host 'The PDF report is missing, so nothing was zipped.' -ForegroundColor Yellow
    Write-Host ''
    Write-Host 'Make it first:'
    Write-Host '  1. Open UECS2194_Assignment_P1_G1\WASD_Assignment_Report.docx in Word'
    Write-Host '  2. If the contents page looks wrong, click it and press F9, then'
    Write-Host '     choose "Update entire table"'
    Write-Host '  3. File > Save As > PDF, saving into the same folder'
    Write-Host '  4. Run this script again'
    Write-Host ''
    exit 1
}

if (Test-Path $zip) { Remove-Item $zip -Force }

Compress-Archive -Path $folder -DestinationPath $zip -CompressionLevel Optimal

$mb = [math]::Round((Get-Item $zip).Length / 1MB, 2)
Write-Host ''
Write-Host "Created: $zip  ($mb MB)" -ForegroundColor Green

# Read the archive back and show what a marker will actually find inside.
Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [System.IO.Compression.ZipFile]::OpenRead($zip)
try {
    $entries = $archive.Entries
    Write-Host ("Entries: " + $entries.Count)

    $required = @(
        'UECS2194_Assignment_P1_G1/README.md',
        'UECS2194_Assignment_P1_G1/WASD_Assignment_Report.pdf',
        'UECS2194_Assignment_P1_G1/database/database.sql',
        'UECS2194_Assignment_P1_G1/WASD_Official_Video.txt',
        'UECS2194_Assignment_P1_G1/wasd/src/index.php',
        'UECS2194_Assignment_P1_G1/wasd/.htaccess'
    )

    Write-Host ''
    Write-Host 'Deliverables:'
    $missing = 0
    foreach ($r in $required) {
        $found = $entries | Where-Object { $_.FullName -eq $r }
        if ($found) {
            Write-Host ("  present  " + $r)
        } else {
            Write-Host ("  MISSING  " + $r) -ForegroundColor Red
            $missing++
        }
    }

    Write-Host ''
    if ($missing -eq 0) {
        Write-Host 'Ready to submit.' -ForegroundColor Green
    } else {
        Write-Host "$missing deliverable(s) missing from the archive." -ForegroundColor Red
    }
} finally {
    $archive.Dispose()
}
