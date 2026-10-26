param(
    [string]$PhpPath = 'C:\xampp\php\php.exe'
)

$ErrorActionPreference = 'Stop'
$projectPath = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
$runnerPath = Join-Path $PSScriptRoot 'Start-GoogleContacts.ps1'
if (!(Test-Path -LiteralPath $PhpPath -PathType Leaf)) {
    throw 'No se encontro PHP. Indique -PhpPath con su ubicacion.'
}
$powershellPath = Join-Path $PSHOME 'powershell.exe'
$arguments = '-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File "' + $runnerPath + '" -PhpPath "' + $PhpPath + '"'

$taskName = 'IFE-GoogleContacts'
$existing = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($existing -and @($existing.Actions | Where-Object { $_.Execute -ne $powershellPath -or $_.Arguments -ne $arguments }).Count -gt 0) {
    throw 'Ya existe una tarea diferente con ese nombre. No se reemplazo.'
}
$userName = [Security.Principal.WindowsIdentity]::GetCurrent().Name
$action = New-ScheduledTaskAction -Execute $powershellPath -Argument $arguments -WorkingDirectory $projectPath
$logon = New-ScheduledTaskTrigger -AtLogOn -User $userName
$settings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable `
    -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)
$principal = New-ScheduledTaskPrincipal -UserId $userName -LogonType Interactive -RunLevel Limited
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $logon -Settings $settings `
    -Principal $principal -Description 'Proceso central compartido de Google Contacts para esta instalacion IFE.' -Force | Out-Null
if ((Get-ScheduledTask -TaskName $taskName).State -ne 'Running') {
    Start-ScheduledTask -TaskName $taskName
}
Write-Output 'Tarea IFE-GoogleContacts instalada e iniciada para la sesion de Windows del usuario actual.'
