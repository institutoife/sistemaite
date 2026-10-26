param(
    [string]$OutputDirectory = 'artifacts\banahosting'
)

$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
$rootPrefix = $projectRoot.TrimEnd('\') + '\'
$outputRoot = [IO.Path]::GetFullPath((Join-Path $projectRoot $OutputDirectory))
if (!$outputRoot.StartsWith($rootPrefix, [StringComparison]::OrdinalIgnoreCase)) {
    throw 'La salida debe permanecer dentro del proyecto.'
}
$releaseFiles = ConvertFrom-Json -InputObject (Get-Content -LiteralPath (Join-Path $PSScriptRoot 'release-files.json') -Raw)
if (!$releaseFiles.Count) { throw 'La lista de archivos esta vacia.' }
$fileRecords = @()
foreach ($relative in $releaseFiles) {
    if ($relative -match '(^|/)(\.git|\.agents|\.codex|vendor|node_modules|storage|artifacts)(/|$)' -or
        $relative -eq '.env' -or $relative -match '(?i)client_secret|\.(sql|vcf|pdf|log|key)$') {
        throw "Archivo no permitido en la actualizacion: $relative"
    }
    $source = [IO.Path]::GetFullPath((Join-Path $projectRoot $relative))
    if (!$source.StartsWith($rootPrefix, [StringComparison]::OrdinalIgnoreCase) -or
        !(Test-Path -LiteralPath $source -PathType Leaf)) {
        throw "Archivo fuera del proyecto o ausente: $relative"
    }
    $fileRecords += [ordered]@{ path = $relative; sha256 = (Get-FileHash -LiteralPath $source -Algorithm SHA256).Hash.ToLowerInvariant() }
}
New-Item -ItemType Directory -Path $outputRoot -Force | Out-Null
$zipPath = Join-Path $outputRoot 'ife-google-contactos-banahosting.zip'
# Delete only this explicitly named generated archive, never directories or application data.
if (Test-Path -LiteralPath $zipPath) { Remove-Item -LiteralPath $zipPath }
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$stream = [IO.File]::Open($zipPath, [IO.FileMode]::CreateNew)
$zip = New-Object IO.Compression.ZipArchive($stream, [IO.Compression.ZipArchiveMode]::Create, $false)
try {
    foreach ($record in $fileRecords) {
        [IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, (Join-Path $projectRoot $record.path),
            $record.path.Replace('\', '/'), [IO.Compression.CompressionLevel]::Optimal) | Out-Null
    }
    $baseRevision = (& git -C $projectRoot rev-parse HEAD).Trim()
    $manifest = [ordered]@{
        type = 'incremental-update'; target = 'https://ife.bo'; generated_at_utc = [DateTime]::UtcNow.ToString('o')
        source_base_revision = $baseRevision
        composer_lock_sha256 = (Get-FileHash -LiteralPath (Join-Path $projectRoot 'composer.lock') -Algorithm SHA256).Hash.ToLowerInvariant()
        note = 'Actualizar la instalacion existente. Conservar .env, APP_KEY, base de datos, fotos y archivos del servidor.'
        files = $fileRecords
    }
    $entry = $zip.CreateEntry('release-manifest.json')
    $writer = New-Object IO.StreamWriter($entry.Open(), (New-Object Text.UTF8Encoding($false)))
    try { $writer.Write(($manifest | ConvertTo-Json -Depth 6)) } finally { $writer.Dispose() }
} finally {
    $zip.Dispose()
    $stream.Dispose()
}
$zipHash = (Get-FileHash -LiteralPath $zipPath -Algorithm SHA256).Hash.ToLowerInvariant()
[IO.File]::WriteAllText((Join-Path $outputRoot 'SHA256.txt'), "$zipHash  ife-google-contactos-banahosting.zip`n", (New-Object Text.UTF8Encoding($false)))
Write-Output ('Paquete: ' + $zipPath)
Write-Output ('Archivos incluidos: ' + $fileRecords.Count)
Write-Output ('SHA256: ' + $zipHash)
