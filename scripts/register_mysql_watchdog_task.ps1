# Register a 10-minute MySQL health check.
# Run as Administrator:
#   .\scripts\register_mysql_watchdog_task.ps1

$TaskName = 'BarangayPortalMysqlWatchdog'
$ProjectRoot = 'C:\xampp\htdocs\barangay'
$BatPath = Join-Path $ProjectRoot 'scripts\mysql_health_watchdog.bat'

if (-not (Test-Path $BatPath)) {
    Write-Error "Batch file not found: $BatPath"
    exit 1
}

$Action = New-ScheduledTaskAction -Execute $BatPath -WorkingDirectory $ProjectRoot
# Repeat for ~10 years (MaxValue is rejected by Task Scheduler XML).
$Trigger = New-ScheduledTaskTrigger -Once -At ((Get-Date).AddMinutes(1)) -RepetitionInterval (New-TimeSpan -Minutes 10) -RepetitionDuration (New-TimeSpan -Days 3650)
$Settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable
$Principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest

try {
    Register-ScheduledTask -TaskName $TaskName -Action $Action -Trigger $Trigger -Settings $Settings -Principal $Principal -Force -ErrorAction Stop | Out-Null
} catch {
    Write-Error "Could not register '$TaskName'. Run PowerShell as Administrator. $_"
    exit 1
}

Write-Host "Scheduled task '$TaskName' registered (every 10 minutes)."
Write-Host "Log: C:\xampp\secure\barangay_ops\mysql_watchdog.log"
