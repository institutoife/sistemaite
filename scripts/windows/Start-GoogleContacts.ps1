param(
    [string]$PhpPath = 'C:\xampp\php\php.exe'
)

$ErrorActionPreference = 'Stop'
$projectPath = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
$artisanPath = Join-Path $projectPath 'artisan'
if (!(Test-Path -LiteralPath $PhpPath -PathType Leaf) -or !(Test-Path -LiteralPath $artisanPath -PathType Leaf)) {
    throw 'No se encontro PHP o artisan. Revise la instalacion del servidor.'
}

# Only one supervisor per installation, including simultaneous manual starts.
$runnerLockPath = Join-Path $projectPath 'storage\framework\google-contacts-runner.lock'
try {
    $runnerLock = [IO.File]::Open($runnerLockPath, [IO.FileMode]::OpenOrCreate, [IO.FileAccess]::ReadWrite, [IO.FileShare]::None)
} catch [IO.IOException] {
    exit 0
}

try {
    while ($true) {
        # Keep credentials in Laravel; never pass tokens or read .env in this script.
        $workerProcess = Start-Process -FilePath $PhpPath -ArgumentList @(('"' + $artisanPath + '"'), 'gcontact:work') `
            -WorkingDirectory $projectPath -WindowStyle Hidden -PassThru -Wait `
            -RedirectStandardOutput (Join-Path $projectPath 'storage\logs\google-contacts-worker.log') `
            -RedirectStandardError (Join-Path $projectPath 'storage\logs\google-contacts-worker-error.log')
        # Reconnect after a database shutdown, queue restart or process failure.
        Start-Sleep -Seconds 10
    }
} finally {
    $runnerLock.Dispose()
}
